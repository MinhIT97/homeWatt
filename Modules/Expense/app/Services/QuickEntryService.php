<?php

namespace Modules\Expense\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\ExpenseQuickEntryHabit;
use Modules\Expense\Models\ExpenseRecurringTransaction;
use Modules\Expense\Models\ExpenseTransactionTemplate;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Wallet\Models\Wallet;

class QuickEntryService
{
    public function __construct(
        private readonly TelegramParserService $parser,
        private readonly ExpenseService $expenseService,
        private readonly TransferService $transferService,
    ) {}

    public function preview(User $user, string $text, ?int $preferredHomeId = null): array
    {
        $lines = $this->transactionLines($text);
        if ($lines === []) {
            throw ValidationException::withMessages(['text' => 'Vui lòng nhập nội dung giao dịch.']);
        }

        if (count($lines) > 10) {
            throw ValidationException::withMessages(['text' => 'Mỗi lần nhập nhanh tối đa 10 dòng.']);
        }

        $items = collect($lines)
            ->map(fn (string $line) => $this->previewLine($user, $line, $preferredHomeId))
            ->values()
            ->all();

        $homeId = $items[0]['home_id'] ?? $preferredHomeId;

        return [
            'items' => $items,
            'options' => $homeId ? $this->optionsForHome($user, (int) $homeId) : [],
            'templates' => $homeId ? $this->templatesForHome($user, (int) $homeId) : [],
        ];
    }

    public function previewTemplate(User $user, int $templateId, string|float|int $amount, ?int $preferredHomeId = null): array
    {
        $template = ExpenseTransactionTemplate::with(['wallet', 'category', 'fromWallet', 'toWallet'])
            ->findOrFail($templateId);

        $this->assertCanEditHome($user, (int) $template->home_id);

        if ($preferredHomeId && (int) $template->home_id !== $preferredHomeId) {
            throw ValidationException::withMessages(['template_id' => 'Mẫu giao dịch không thuộc nhà đang chọn.']);
        }

        $parsedAmount = $this->parseAmount($amount);
        if ($parsedAmount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Số tiền không hợp lệ.']);
        }

        $item = [
            'ok' => true,
            'source' => 'template',
            'line' => $template->name,
            'mode' => $template->type === 'transfer' ? 'transfer' : 'transaction',
            'home_id' => (int) $template->home_id,
            'home_name' => $template->home?->name,
            'type' => $template->type === 'income' ? Expense::TYPE_INCOME : Expense::TYPE_EXPENSE,
            'amount' => $parsedAmount,
            'amount_label' => $this->money($parsedAmount),
            'description' => $template->description ?: $template->name,
            'notes' => $template->notes,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'occurred_at_input' => now()->format('Y-m-d\TH:i'),
            'wallet_id' => $template->wallet_id,
            'wallet_name' => $template->wallet?->name,
            'category_id' => $template->category_id,
            'category_name' => $template->category?->name,
            'category_group' => $template->category?->category_group,
            'from_wallet_id' => $template->from_wallet_id,
            'from_wallet_name' => $template->fromWallet?->name,
            'to_wallet_id' => $template->to_wallet_id,
            'to_wallet_name' => $template->toWallet?->name,
            'fee' => 0,
            'counterparty' => null,
            'warnings' => [],
        ];

        if ($item['mode'] === 'transaction' && (! $item['wallet_id'] || ! $item['category_id'])) {
            $default = $this->defaultWallet($template->home_id);
            $category = $this->fallbackCategory($template->home_id, $item['type']);
            $item['wallet_id'] ??= $default?->id;
            $item['wallet_name'] ??= $default?->name;
            $item['category_id'] ??= $category?->id;
            $item['category_name'] ??= $category?->name;
            $item['category_group'] ??= $category?->category_group;
        }

        $item['duplicate'] = $this->findDuplicate($item);

        return [
            'items' => [$item],
            'options' => $this->optionsForHome($user, (int) $template->home_id),
            'templates' => $this->templatesForHome($user, (int) $template->home_id),
        ];
    }

    public function storeItems(User $user, array $items, bool $force = false): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Chưa có giao dịch nào để lưu.']);
        }

        if (count($items) > 10) {
            throw ValidationException::withMessages(['items' => 'Mỗi lần lưu tối đa 10 giao dịch.']);
        }

        $results = [];
        foreach ($items as $item) {
            $results[] = $this->storeItem($user, $item, $force);
        }

        return $results;
    }

    public function storeItem(User $user, array $item, bool $force = false): array
    {
        $normalized = $this->normalizeEditableItem($user, $item);
        $duplicate = $this->findDuplicate($normalized);

        if ($duplicate && ! $force) {
            return [
                'stored' => false,
                'duplicate' => $duplicate,
                'item' => $normalized,
            ];
        }

        if ($normalized['mode'] === 'transfer') {
            $transfer = $this->transferService->createTransfer([
                'home_id' => $normalized['home_id'],
                'from_wallet_id' => $normalized['from_wallet_id'],
                'to_wallet_id' => $normalized['to_wallet_id'],
                'amount' => $normalized['amount'],
                'fee' => $normalized['fee'] ?? 0,
                'description' => $normalized['description'],
                'occurred_at' => $normalized['occurred_at'],
            ], $user);

            return [
                'stored' => true,
                'mode' => 'transfer',
                'id' => $transfer->id,
                'label' => 'Đã chuyển '.$this->money((float) $transfer->amount).' đ',
            ];
        }

        $expense = $this->expenseService->createExpense([
            'home_id' => $normalized['home_id'],
            'wallet_id' => $normalized['wallet_id'],
            'category_id' => $normalized['category_id'],
            'type' => $normalized['type'],
            'amount' => $normalized['amount'],
            'description' => $normalized['description'],
            'notes' => $normalized['notes'] ?? null,
            'occurred_at' => $normalized['occurred_at'],
        ], $user);

        $this->rememberHabit($user, $expense);

        return [
            'stored' => true,
            'mode' => 'transaction',
            'id' => $expense->id,
            'label' => 'Đã lưu '.$this->money((float) $expense->amount).' đ',
        ];
    }

    public function createRecurring(User $user, array $data): ExpenseRecurringTransaction
    {
        $homeId = (int) ($data['home_id'] ?? 0);
        $this->assertCanEditHome($user, $homeId);

        $wallet = Wallet::where('home_id', $homeId)->findOrFail((int) ($data['wallet_id'] ?? 0));
        $category = ExpenseCategory::where('home_id', $homeId)->findOrFail((int) ($data['category_id'] ?? 0));
        $type = (string) ($data['type'] ?? Expense::TYPE_EXPENSE);

        if (! in_array($type, Expense::TYPES, true) || $category->type !== $type) {
            throw ValidationException::withMessages(['category_id' => 'Danh mục không khớp loại giao dịch.']);
        }

        $frequency = (string) ($data['frequency'] ?? ExpenseRecurringTransaction::FREQUENCY_MONTHLY);
        if (! in_array($frequency, ExpenseRecurringTransaction::FREQUENCIES, true)) {
            throw ValidationException::withMessages(['frequency' => 'Tần suất không hợp lệ.']);
        }

        $startDate = Carbon::parse($data['start_date'] ?? now()->toDateString())->startOfDay();

        return ExpenseRecurringTransaction::create([
            'user_id' => $user->id,
            'home_id' => $homeId,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'name' => Str::limit((string) ($data['name'] ?? $data['description'] ?? 'Giao dịch định kỳ'), 255, ''),
            'type' => $type,
            'amount' => $this->parseAmount($data['amount'] ?? 0),
            'frequency' => $frequency,
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'start_date' => $startDate->toDateString(),
            'next_due_date' => Carbon::parse($data['next_due_date'] ?? $startDate)->toDateString(),
            'is_active' => true,
        ]);
    }

    public function generateDueRecurring(?Carbon $asOf = null): int
    {
        $asOf ??= now();
        $generated = 0;

        ExpenseRecurringTransaction::query()
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', $asOf->toDateString())
            ->with(['user'])
            ->chunkById(50, function (Collection $items) use (&$generated, $asOf) {
                foreach ($items as $recurring) {
                    $expense = $this->expenseService->createExpense([
                        'home_id' => $recurring->home_id,
                        'wallet_id' => $recurring->wallet_id,
                        'category_id' => $recurring->category_id,
                        'type' => $recurring->type,
                        'amount' => (float) $recurring->amount,
                        'description' => $recurring->description ?: $recurring->name,
                        'notes' => trim(($recurring->notes ? $recurring->notes."\n" : '').'Tạo tự động từ giao dịch định kỳ #'.$recurring->id),
                        'occurred_at' => Carbon::parse($recurring->next_due_date)->setTimeFrom($asOf)->toDateTimeString(),
                    ], $recurring->user);

                    $this->rememberHabit($recurring->user, $expense);

                    $recurring->forceFill([
                        'last_generated_at' => now(),
                        'next_due_date' => $this->nextDueDate(Carbon::parse($recurring->next_due_date), $recurring->frequency)->toDateString(),
                    ])->save();

                    $generated++;
                }
            });

        return $generated;
    }

    public function templatesForHome(User $user, int $homeId): array
    {
        $this->assertCanEditHome($user, $homeId);
        $this->ensureDefaultTemplates($homeId);

        return ExpenseTransactionTemplate::query()
            ->where('home_id', $homeId)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->with(['wallet', 'category', 'fromWallet', 'toWallet'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseTransactionTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'icon' => $template->icon ?: '✨',
                'description' => $template->description,
                'wallet_id' => $template->wallet_id,
                'wallet_name' => $template->wallet?->name,
                'category_id' => $template->category_id,
                'category_name' => $template->category?->name,
                'from_wallet_id' => $template->from_wallet_id,
                'from_wallet_name' => $template->fromWallet?->name,
                'to_wallet_id' => $template->to_wallet_id,
                'to_wallet_name' => $template->toWallet?->name,
            ])
            ->values()
            ->all();
    }

    public function optionsForHome(User $user, int $homeId): array
    {
        $this->assertCanEditHome($user, $homeId);

        $homes = $this->editableMemberships($user)
            ->map(fn (HomeMember $membership) => [
                'id' => $membership->home_id,
                'name' => $membership->home?->name ?? 'Nhà',
            ])
            ->values()
            ->all();

        $wallets = Wallet::where('home_id', $homeId)
            ->where('is_archived', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $wallet) => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type,
                'balance' => (float) $wallet->calculatedBalance(),
            ])
            ->values()
            ->all();

        $categories = ExpenseCategory::where('home_id', $homeId)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ExpenseCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'category_group' => $category->category_group,
                'icon' => $category->icon,
                'parent_id' => $category->parent_id,
            ])
            ->values()
            ->all();

        return compact('homes', 'wallets', 'categories');
    }

    public function findDuplicate(array $item): ?array
    {
        $occurredAt = Carbon::parse($item['occurred_at'] ?? now());
        $createdSince = now()->subMinutes(5);
        $description = trim((string) ($item['description'] ?? ''));

        if (($item['mode'] ?? 'transaction') === 'transfer') {
            $duplicate = Transfer::query()
                ->where('home_id', (int) $item['home_id'])
                ->where('from_wallet_id', (int) $item['from_wallet_id'])
                ->where('to_wallet_id', (int) $item['to_wallet_id'])
                ->where('amount', (float) $item['amount'])
                ->where('created_at', '>=', $createdSince)
                ->whereBetween('occurred_at', [$occurredAt->copy()->subMinutes(5), $occurredAt->copy()->addMinutes(5)])
                ->when($description !== '', fn ($query) => $query->where('description', $description))
                ->latest()
                ->first();

            return $duplicate ? [
                'mode' => 'transfer',
                'id' => $duplicate->id,
                'message' => 'Có giao dịch chuyển ví giống hệt vừa được lưu trong 5 phút gần đây.',
            ] : null;
        }

        $duplicate = Expense::query()
            ->whereNull('transfer_id')
            ->where('home_id', (int) $item['home_id'])
            ->where('wallet_id', (int) $item['wallet_id'])
            ->where('type', (string) $item['type'])
            ->where('amount', (float) $item['amount'])
            ->where('created_at', '>=', $createdSince)
            ->whereBetween('occurred_at', [$occurredAt->copy()->subMinutes(5), $occurredAt->copy()->addMinutes(5)])
            ->when($description !== '', fn ($query) => $query->where('description', $description))
            ->latest()
            ->first();

        return $duplicate ? [
            'mode' => 'transaction',
            'id' => $duplicate->id,
            'message' => 'Có giao dịch giống hệt vừa được lưu trong 5 phút gần đây.',
        ] : null;
    }

    public function parseAmount(string|float|int|null $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        if (! preg_match('/(\d+(?:[.,]\d+)?)\s*(k|m|tr|triệu|trieu)?/iu', $text, $matches)) {
            return 0.0;
        }

        $amount = (float) str_replace(',', '.', $matches[1]);
        $unit = mb_strtolower($matches[2] ?? '', 'UTF-8');

        return match ($unit) {
            'k' => $amount * 1000,
            'm', 'tr', 'triệu', 'trieu' => $amount * 1000000,
            default => $amount,
        };
    }

    public function previewLine(User $user, string $line, ?int $preferredHomeId = null): array
    {
        $memberships = $this->editableMemberships($user);
        if ($memberships->isEmpty()) {
            throw ValidationException::withMessages(['home_id' => 'Bạn chưa có quyền nhập liệu ở nhà nào.']);
        }

        $homeIds = $memberships->pluck('home_id');
        $allWallets = Wallet::whereIn('home_id', $homeIds)
            ->where('is_archived', false)
            ->get()
            ->sortByDesc(fn (Wallet $wallet) => mb_strlen($wallet->name, 'UTF-8'))
            ->values();

        if ($allWallets->isEmpty()) {
            throw ValidationException::withMessages(['wallet_id' => 'Nhà của bạn chưa có ví để ghi nhận giao dịch.']);
        }

        $dateExtraction = $this->extractOccurredAt($line);
        $cleanLine = $dateExtraction['text'];
        $occurredAt = $dateExtraction['occurred_at'];

        $extracted = $this->extractWallets($cleanLine, $allWallets);
        $modifiedText = $extracted['text'];
        $matchedWallets = collect($extracted['matched_wallets']);

        $selectedHome = null;
        if ($matchedWallets->isNotEmpty()) {
            $selectedHome = $memberships->firstWhere('home_id', $matchedWallets->first()->home_id)?->home;
        }

        if (! $selectedHome && $preferredHomeId && $homeIds->contains((int) $preferredHomeId)) {
            $selectedHome = $memberships->firstWhere('home_id', (int) $preferredHomeId)?->home;
        }

        $selectedHome ??= $memberships->first()?->home;

        $parsed = $this->parser->parse($modifiedText, (int) $selectedHome->id);
        if (! $parsed) {
            return [
                'ok' => false,
                'line' => $line,
                'home_id' => $selectedHome->id,
                'home_name' => $selectedHome->name,
                'warnings' => ['Không nhận ra số tiền hoặc cú pháp giao dịch.'],
            ];
        }

        $homeWallets = $allWallets->where('home_id', $selectedHome->id)->values();

        if ($parsed['type'] === 'transfer') {
            $item = $this->previewTransferLine($line, $modifiedText, $parsed, $selectedHome, $homeWallets, $matchedWallets, $occurredAt);
            $item['duplicate'] = $this->findDuplicate($item);

            return $item;
        }

        $selectedWallet = $matchedWallets->first(fn (Wallet $wallet) => (int) $wallet->home_id === (int) $selectedHome->id);
        $habit = null;

        if (! $selectedWallet) {
            $habit = $this->habitFor($user, (int) $selectedHome->id, $line, $parsed);
            if ($habit?->wallet && ! $habit->wallet->is_archived) {
                $selectedWallet = $habit->wallet;
            }
        }

        $selectedWallet ??= $this->defaultWallet((int) $selectedHome->id);

        $category = ExpenseCategory::where('home_id', $selectedHome->id)->find($parsed['category_id']);
        if ($habit?->category && ($parsed['category_name'] ?? null) === 'Khác') {
            $category = $habit->category;
        }
        $category ??= $this->fallbackCategory((int) $selectedHome->id, $parsed['type']);

        $item = [
            'ok' => true,
            'source' => 'text',
            'line' => $line,
            'mode' => 'transaction',
            'home_id' => (int) $selectedHome->id,
            'home_name' => $selectedHome->name,
            'type' => $parsed['type'],
            'amount' => (float) $parsed['amount'],
            'amount_label' => $this->money((float) $parsed['amount']),
            'description' => $parsed['description'],
            'notes' => null,
            'occurred_at' => $occurredAt->toDateTimeString(),
            'occurred_at_input' => $occurredAt->format('Y-m-d\TH:i'),
            'wallet_id' => $selectedWallet?->id,
            'wallet_name' => $selectedWallet?->name,
            'category_id' => $category?->id,
            'category_name' => $category?->name ?? $parsed['category_name'],
            'category_group' => $category?->category_group ?? $parsed['category_group'] ?? null,
            'counterparty' => $parsed['counterparty'] ?? null,
            'habit_suggestion' => $habit ? 'Theo thói quen: '.$habit->category?->name.' + '.$habit->wallet?->name : null,
            'warnings' => [],
        ];

        if (! $item['wallet_id']) {
            $item['warnings'][] = 'Chưa chọn được ví.';
        }
        if (! $item['category_id']) {
            $item['warnings'][] = 'Chưa chọn được danh mục.';
        }

        $item['duplicate'] = $this->findDuplicate($item);

        return $item;
    }

    private function previewTransferLine(string $line, string $modifiedText, array $parsed, Home $home, Collection $homeWallets, Collection $matchedWallets, Carbon $occurredAt): array
    {
        $fromWallet = null;
        $toWallet = null;
        $modifiedTextLower = mb_strtolower($modifiedText, 'UTF-8');

        if ($matchedWallets->count() >= 2) {
            if (preg_match('/\{wallet_(\d+)\}\s*(?:sang|đến|den|qua|vào|vao|->)\s*\{wallet_(\d+)\}/iu', $modifiedTextLower, $matches)) {
                $fromWallet = $matchedWallets->get((int) $matches[1]);
                $toWallet = $matchedWallets->get((int) $matches[2]);
            } elseif (preg_match('/(?:sang|đến|den|qua|vào|vao|->)\s*\{wallet_(\d+)\}\s*(?:từ|tu)\s*\{wallet_(\d+)\}/iu', $modifiedTextLower, $matches)) {
                $toWallet = $matchedWallets->get((int) $matches[1]);
                $fromWallet = $matchedWallets->get((int) $matches[2]);
            } else {
                $fromWallet = $matchedWallets->get(0);
                $toWallet = $matchedWallets->get(1);
            }
        } elseif ($matchedWallets->count() === 1) {
            $wallet = $matchedWallets->first();
            if (preg_match('/(?:từ|tu)\s*\{wallet_0\}/iu', $modifiedTextLower)) {
                $fromWallet = $wallet;
            } else {
                $toWallet = $wallet;
            }
        }

        $fromWallet = $fromWallet && (int) $fromWallet->home_id === (int) $home->id ? $fromWallet : null;
        $toWallet = $toWallet && (int) $toWallet->home_id === (int) $home->id ? $toWallet : null;

        $defaultWallet = $this->defaultWallet((int) $home->id);
        $fromWallet ??= $defaultWallet;
        $toWallet ??= $homeWallets->first(fn (Wallet $wallet) => $fromWallet && $wallet->id !== $fromWallet->id) ?: $defaultWallet;

        $item = [
            'ok' => true,
            'source' => 'text',
            'line' => $line,
            'mode' => 'transfer',
            'home_id' => (int) $home->id,
            'home_name' => $home->name,
            'type' => 'transfer',
            'amount' => (float) $parsed['amount'],
            'amount_label' => $this->money((float) $parsed['amount']),
            'description' => $parsed['description'],
            'notes' => null,
            'occurred_at' => $occurredAt->toDateTimeString(),
            'occurred_at_input' => $occurredAt->format('Y-m-d\TH:i'),
            'from_wallet_id' => $fromWallet?->id,
            'from_wallet_name' => $fromWallet?->name,
            'to_wallet_id' => $toWallet?->id,
            'to_wallet_name' => $toWallet?->name,
            'fee' => 0,
            'category_group' => ExpenseCategory::GROUP_TRANSFER,
            'warnings' => [],
        ];

        if (! $item['from_wallet_id'] || ! $item['to_wallet_id']) {
            $item['warnings'][] = 'Chưa chọn đủ ví nguồn/ví nhận.';
        } elseif ($item['from_wallet_id'] === $item['to_wallet_id']) {
            $item['warnings'][] = 'Ví nguồn và ví nhận đang trùng nhau.';
        }

        return $item;
    }

    private function normalizeEditableItem(User $user, array $item): array
    {
        $homeId = (int) ($item['home_id'] ?? 0);
        $this->assertCanEditHome($user, $homeId);

        $mode = ($item['mode'] ?? 'transaction') === 'transfer' ? 'transfer' : 'transaction';
        $amount = $this->parseAmount($item['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Số tiền không hợp lệ.']);
        }

        $occurredAt = Carbon::parse($item['occurred_at'] ?? $item['occurred_at_input'] ?? now());
        if ($occurredAt->isFuture()) {
            throw ValidationException::withMessages(['occurred_at' => 'Ngày giao dịch không được ở tương lai.']);
        }

        $description = Str::limit(trim((string) ($item['description'] ?? '')), 255, '');

        if ($mode === 'transfer') {
            $from = Wallet::where('home_id', $homeId)->where('is_archived', false)->findOrFail((int) ($item['from_wallet_id'] ?? 0));
            $to = Wallet::where('home_id', $homeId)->where('is_archived', false)->findOrFail((int) ($item['to_wallet_id'] ?? 0));

            if ($from->id === $to->id) {
                throw ValidationException::withMessages(['to_wallet_id' => 'Ví nhận phải khác ví nguồn.']);
            }

            return [
                'mode' => 'transfer',
                'home_id' => $homeId,
                'from_wallet_id' => $from->id,
                'to_wallet_id' => $to->id,
                'amount' => $amount,
                'fee' => $this->parseAmount($item['fee'] ?? 0),
                'description' => $description ?: 'Chuyển ví',
                'occurred_at' => $occurredAt->toDateTimeString(),
            ];
        }

        $type = (string) ($item['type'] ?? Expense::TYPE_EXPENSE);
        if (! in_array($type, Expense::TYPES, true)) {
            throw ValidationException::withMessages(['type' => 'Loại giao dịch không hợp lệ.']);
        }

        $wallet = Wallet::where('home_id', $homeId)->where('is_archived', false)->findOrFail((int) ($item['wallet_id'] ?? 0));
        $category = ExpenseCategory::where('home_id', $homeId)->findOrFail((int) ($item['category_id'] ?? 0));

        if ($category->type !== $type) {
            throw ValidationException::withMessages(['category_id' => 'Danh mục không khớp loại giao dịch.']);
        }

        return [
            'mode' => 'transaction',
            'home_id' => $homeId,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description ?: $category->name,
            'notes' => $item['notes'] ?? null,
            'occurred_at' => $occurredAt->toDateTimeString(),
        ];
    }

    private function rememberHabit(User $user, Expense $expense): void
    {
        if ($expense->belongsToTransfer()) {
            return;
        }

        $keywords = $this->keywordsFor($expense->description ?: $expense->category?->name ?: '');
        foreach (array_slice($keywords, 0, 3) as $keyword) {
            $habit = ExpenseQuickEntryHabit::firstOrNew([
                'user_id' => $user->id,
                'home_id' => $expense->home_id,
                'keyword' => $keyword,
                'wallet_id' => $expense->wallet_id,
                'category_id' => $expense->category_id,
            ]);

            $habit->usage_count = (int) $habit->usage_count + 1;
            $habit->last_used_at = now();
            $habit->save();
        }
    }

    private function habitFor(User $user, int $homeId, string $line, array $parsed): ?ExpenseQuickEntryHabit
    {
        $keywords = $this->keywordsFor(($parsed['description'] ?? '').' '.$line);
        if ($keywords === []) {
            return null;
        }

        return ExpenseQuickEntryHabit::query()
            ->where('user_id', $user->id)
            ->where('home_id', $homeId)
            ->whereIn('keyword', $keywords)
            ->with(['wallet', 'category'])
            ->orderByDesc('usage_count')
            ->orderByDesc('last_used_at')
            ->first();
    }

    private function keywordsFor(string $text): array
    {
        $normalized = $this->asciiFold($text);
        $normalized = preg_replace('/\d+(?:[.,]\d+)?\s*(?:k|m|tr|trieu)?/iu', ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9\s]/iu', ' ', $normalized);

        $stopWords = [
            'chi', 'tieu', 'thu', 'nhan', 'mua', 'pay', 'out', 'in',
            'cho', 'vay', 'muon', 'tra', 'no', 'tu', 'sang', 'den', 'vao',
            'tai', 'khoan', 'vi', 'tk', 'bang', 'hom', 'qua', 'ngay',
        ];

        return collect(preg_split('/\s+/u', trim($normalized)) ?: [])
            ->filter(fn (string $token) => mb_strlen($token, 'UTF-8') >= 3 && ! in_array($token, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }

    private function asciiFold(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        return strtr($text, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        ]);
    }

    private function extractOccurredAt(string $text): array
    {
        $occurredAt = now();
        $cleanText = $text;

        if (preg_match('/(?:^|\s)(hôm qua|hom qua)(?:\s|$)/iu', $cleanText, $matches)) {
            $occurredAt = now()->subDay();
            $cleanText = trim(str_replace($matches[0], ' ', $cleanText));
        } elseif (preg_match('/(?:ngày|ngay)\s+(\d{1,2})[\/.-](\d{1,2})(?:[\/.-](\d{2,4}))?/iu', $cleanText, $matches)) {
            $occurredAt = $this->buildOccurredAtFromDateParts((int) $matches[1], (int) $matches[2], $matches[3] ?? null);
            $cleanText = trim(str_replace($matches[0], ' ', $cleanText));
        } elseif (preg_match('/(?:^|\s)(\d{1,2})[\/.-](\d{1,2})(?:[\/.-](\d{2,4}))?(?:\s|$)/u', $cleanText, $matches)) {
            $occurredAt = $this->buildOccurredAtFromDateParts((int) $matches[1], (int) $matches[2], $matches[3] ?? null);
            $cleanText = trim(str_replace($matches[0], ' ', $cleanText));
        }

        return [
            'text' => preg_replace('/\s{2,}/u', ' ', $cleanText) ?: $text,
            'occurred_at' => $occurredAt,
        ];
    }

    private function buildOccurredAtFromDateParts(int $day, int $month, ?string $year): Carbon
    {
        $fullYear = $year ? (int) $year : now()->year;
        if ($fullYear < 100) {
            $fullYear += 2000;
        }

        try {
            $date = Carbon::create($fullYear, $month, $day, now()->hour, now()->minute, now()->second);
        } catch (\Throwable) {
            return now();
        }

        if (! $year && $date->isFuture()) {
            $date->subYear();
        }

        return $date->isFuture() ? now() : $date;
    }

    private function extractWallets(string $text, Collection $allWallets): array
    {
        $matchedWallets = [];
        $placeholderIndex = 0;
        $candidates = [];

        foreach ($allWallets as $wallet) {
            $walletNameLower = mb_strtolower($wallet->name, 'UTF-8');
            $walletNameNoSpaces = str_replace(' ', '', $walletNameLower);

            $matchCandidates = [
                $wallet->name,
                $walletNameLower,
                $walletNameNoSpaces,
                'tài khoản '.$walletNameLower,
                'tài khoản '.$walletNameNoSpaces,
                'taikhoan '.$walletNameLower,
                'taikhoan '.$walletNameNoSpaces,
                'tk '.$walletNameLower,
                'tk '.$walletNameNoSpaces,
                'ví '.$walletNameLower,
                'ví '.$walletNameNoSpaces,
                'vi '.$walletNameLower,
                'vi '.$walletNameNoSpaces,
            ];

            if (str_contains($walletNameLower, 'techcombank')) {
                array_push($matchCandidates, 'techcombank', 'tech', 'tcb', 'ví thấu chi tech', 'thấu chi techcombank', 'thấu chi tech');
            }
            if (str_contains($walletNameLower, 'vietcombank')) {
                array_push($matchCandidates, 'vietcombank', 'vcb');
            }
            if (str_contains($walletNameLower, 'momo')) {
                $matchCandidates[] = 'momo';
            }
            if (str_contains($walletNameLower, 'tiền mặt') || str_contains($walletNameLower, 'tien mat')) {
                array_push($matchCandidates, 'tien mat', 'tiền mặt', 'tm');
            }
            if (str_contains($walletNameLower, 'vpbank') || str_contains($walletNameLower, 'vp bank')) {
                array_push($matchCandidates, 'vpbank', 'vp bank', 'vp');
            }

            foreach (array_unique(array_filter($matchCandidates)) as $candidate) {
                $candidates[] = [
                    'wallet' => $wallet,
                    'candidate' => mb_strtolower($candidate, 'UTF-8'),
                ];
            }
        }

        usort($candidates, fn ($a, $b) => mb_strlen($b['candidate'], 'UTF-8') <=> mb_strlen($a['candidate'], 'UTF-8'));

        $modifiedOriginal = $text;
        foreach ($candidates as $item) {
            $candidateLower = $item['candidate'];
            $modifiedLower = mb_strtolower($modifiedOriginal, 'UTF-8');

            if (($pos = mb_strpos($modifiedLower, $candidateLower, 0, 'UTF-8')) === false) {
                continue;
            }

            $placeholder = "{wallet_{$placeholderIndex}}";
            $matchedWallets[$placeholderIndex] = $item['wallet'];
            $modifiedOriginal = mb_substr($modifiedOriginal, 0, $pos, 'UTF-8')
                .$placeholder
                .mb_substr($modifiedOriginal, $pos + mb_strlen($candidateLower, 'UTF-8'), null, 'UTF-8');
            $placeholderIndex++;
        }

        return [
            'text' => $modifiedOriginal,
            'matched_wallets' => $matchedWallets,
        ];
    }

    private function transactionLines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $text) ?: []),
            fn (string $line) => $line !== ''
        ));
    }

    private function editableMemberships(User $user): Collection
    {
        return $user->homeMembers()
            ->with('home')
            ->get()
            ->filter(fn (HomeMember $membership) => $membership->canEdit())
            ->values();
    }

    private function assertCanEditHome(User $user, int $homeId): void
    {
        if ($homeId <= 0 || ! $user->homeMembers()->where('home_id', $homeId)->whereIn('role', HomeMember::EDITOR_ROLES)->exists()) {
            abort(403, 'Bạn không có quyền nhập liệu cho nhà này.');
        }
    }

    private function defaultWallet(int $homeId): ?Wallet
    {
        $wallets = Wallet::where('home_id', $homeId)->where('is_archived', false)->get();

        return $wallets->first(fn (Wallet $wallet) => str_contains(mb_strtolower($wallet->name, 'UTF-8'), 'tiền mặt'))
            ?: $wallets->first(fn (Wallet $wallet) => str_contains(mb_strtolower($wallet->name, 'UTF-8'), 'chính'))
            ?: $wallets->first();
    }

    private function fallbackCategory(int $homeId, string $type): ?ExpenseCategory
    {
        return ExpenseCategory::where('home_id', $homeId)
            ->where('type', $type)
            ->where(function ($query) {
                $query->where('category_group', ExpenseCategory::GROUP_OTHER)
                    ->orWhere('name', 'Khác')
                    ->orWhere('name', 'Thu nhập khác');
            })
            ->orderByRaw("CASE WHEN name IN ('Khác', 'Thu nhập khác') THEN 0 ELSE 1 END")
            ->first()
            ?: ExpenseCategory::where('home_id', $homeId)->where('type', $type)->first();
    }

    private function ensureDefaultTemplates(int $homeId): void
    {
        if (ExpenseTransactionTemplate::where('home_id', $homeId)->whereNull('user_id')->where('is_system', true)->exists()) {
            return;
        }

        $wallet = $this->defaultWallet($homeId);
        $bankWallet = Wallet::where('home_id', $homeId)
            ->where('is_archived', false)
            ->where('type', Wallet::TYPE_BANK)
            ->first() ?: $wallet;

        $definitions = [
            ['Ăn trưa', 'expense', ['Ăn trưa', 'Ăn uống'], $wallet?->id, 'Ăn trưa', '🍜'],
            ['Đổ xăng', 'expense', ['Xăng xe', 'Đi lại'], $bankWallet?->id, 'Đổ xăng', '⛽'],
            ['Internet', 'expense', ['Internet & TV', 'Hóa đơn', 'Nhà cửa'], $bankWallet?->id, 'Internet', '🌐'],
            ['Tiền điện', 'expense', ['Tiền điện', 'Nhà cửa', 'Hóa đơn'], $bankWallet?->id, 'Tiền điện', '⚡'],
            ['Cafe', 'expense', ['Cafe', 'Ăn uống'], $wallet?->id, 'Cafe', '☕'],
        ];

        foreach ($definitions as $index => [$name, $type, $categoryNames, $walletId, $description, $icon]) {
            $category = $this->categoryByNames($homeId, $type, $categoryNames) ?: $this->fallbackCategory($homeId, $type);

            ExpenseTransactionTemplate::create([
                'home_id' => $homeId,
                'user_id' => null,
                'wallet_id' => $walletId,
                'category_id' => $category?->id,
                'name' => $name,
                'type' => $type,
                'description' => $description,
                'icon' => $icon,
                'is_system' => true,
                'sort_order' => $index,
            ]);
        }
    }

    private function categoryByNames(int $homeId, string $type, array $names): ?ExpenseCategory
    {
        foreach ($names as $name) {
            $category = ExpenseCategory::where('home_id', $homeId)
                ->where('type', $type)
                ->where('name', $name)
                ->first();

            if ($category) {
                return $category;
            }
        }

        return null;
    }

    private function nextDueDate(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            ExpenseRecurringTransaction::FREQUENCY_WEEKLY => $date->copy()->addWeek(),
            ExpenseRecurringTransaction::FREQUENCY_YEARLY => $date->copy()->addYearNoOverflow(),
            default => $date->copy()->addMonthNoOverflow(),
        };
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
