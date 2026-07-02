<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Expense\Models\ExpenseRecurringTransaction;
use Modules\Expense\Services\QuickEntryService;

class QuickEntryController extends Controller
{
    public function __construct(private readonly QuickEntryService $quickEntryService) {}

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required_without:template_id', 'nullable', 'string', 'max:5000'],
            'home_id' => ['nullable', 'integer', 'exists:homes,id'],
            'template_id' => ['nullable', 'integer', 'exists:expense_transaction_templates,id'],
            'amount' => ['nullable'],
        ]);

        if (! empty($validated['template_id'])) {
            if (! array_key_exists('amount', $validated)) {
                throw ValidationException::withMessages(['amount' => 'Vui lòng nhập số tiền cho mẫu giao dịch.']);
            }

            $payload = $this->quickEntryService->previewTemplate(
                $request->user(),
                (int) $validated['template_id'],
                $validated['amount'],
                isset($validated['home_id']) ? (int) $validated['home_id'] : null,
            );
        } else {
            $payload = $this->quickEntryService->preview(
                $request->user(),
                (string) $validated['text'],
                isset($validated['home_id']) ? (int) $validated['home_id'] : null,
            );
        }

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.mode' => ['required', 'string', Rule::in(['transaction', 'transfer'])],
            'items.*.home_id' => ['required', 'integer', 'exists:homes,id'],
            'items.*.type' => ['nullable', 'string'],
            'items.*.wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'items.*.category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'items.*.from_wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'items.*.to_wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'items.*.amount' => ['required'],
            'items.*.fee' => ['nullable'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'items.*.occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
            'items.*.occurred_at_input' => ['nullable', 'date', 'before_or_equal:now'],
            'force' => ['nullable', 'boolean'],
        ]);

        try {
            $results = $this->quickEntryService->storeItems(
                $request->user(),
                $validated['items'],
                (bool) ($validated['force'] ?? false),
            );
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }

        $hasDuplicate = collect($results)->contains(fn (array $result) => ! ($result['stored'] ?? false) && isset($result['duplicate']));

        return response()->json([
            'ok' => ! $hasDuplicate,
            'duplicate' => $hasDuplicate,
            'results' => $results,
        ], $hasDuplicate ? 409 : 200);
    }

    public function templates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'home_id' => ['required', 'integer', 'exists:homes,id'],
        ]);

        return response()->json([
            'templates' => $this->quickEntryService->templatesForHome($request->user(), (int) $validated['home_id']),
            'options' => $this->quickEntryService->optionsForHome($request->user(), (int) $validated['home_id']),
        ]);
    }

    public function recurring(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'home_id' => ['required', 'integer', 'exists:homes,id'],
        ]);

        $this->quickEntryService->optionsForHome($request->user(), (int) $validated['home_id']);

        $items = ExpenseRecurringTransaction::query()
            ->where('home_id', (int) $validated['home_id'])
            ->where('user_id', $request->user()->id)
            ->with(['wallet', 'category'])
            ->latest()
            ->take(20)
            ->get()
            ->map(fn (ExpenseRecurringTransaction $recurring) => [
                'id' => $recurring->id,
                'name' => $recurring->name,
                'type' => $recurring->type,
                'amount' => (float) $recurring->amount,
                'wallet_name' => $recurring->wallet?->name,
                'category_name' => $recurring->category?->name,
                'frequency' => $recurring->frequency,
                'next_due_date' => $recurring->next_due_date?->format('Y-m-d'),
                'is_active' => $recurring->is_active,
            ]);

        return response()->json(['items' => $items]);
    }

    public function storeRecurring(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'home_id' => ['required', 'integer', 'exists:homes,id'],
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['expense', 'income'])],
            'amount' => ['required'],
            'frequency' => ['required', 'string', Rule::in(ExpenseRecurringTransaction::FREQUENCIES)],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'next_due_date' => ['nullable', 'date'],
        ]);

        $recurring = $this->quickEntryService->createRecurring($request->user(), $validated);

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $recurring->id,
                'name' => $recurring->name,
                'next_due_date' => $recurring->next_due_date?->format('Y-m-d'),
            ],
        ], 201);
    }

    public function destroyRecurring(Request $request, ExpenseRecurringTransaction $recurring): JsonResponse
    {
        abort_unless((int) $recurring->user_id === (int) $request->user()->id, 403);
        $this->quickEntryService->optionsForHome($request->user(), (int) $recurring->home_id);

        $recurring->forceFill(['is_active' => false])->save();

        return response()->json(['ok' => true]);
    }
}
