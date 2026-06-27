<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Wallet\Http\Requests\StoreWalletRequest;
use Modules\Wallet\Http\Requests\UpdateWalletRequest;
use Modules\Wallet\Models\Wallet;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $walletQuery = Wallet::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->with(['home'])
            ->where('is_archived', false)
            ->orderBy('sort_order')
            ->orderBy('name');

        $wallets = $walletQuery->paginate(20);

        // Calculate totals from all wallets (not just current page)
        $allWallets = $walletQuery->get();
        $totalBalance = (float) $allWallets->sum(fn ($w) => $w->netBalance());

        $totalOpening = (float) $allWallets->sum(function ($w) {
            if ($w->type === 'credit_card') {
                return 0.0;
            }
            return (float) $w->opening_balance;
        });

        $creditCardDebt = (float) $allWallets->sum(function ($w) {
            if ($w->type === 'credit_card') {
                return $w->netBalance();
            }
            return 0.0;
        });
        $homeCurrency = $allWallets->first()?->home?->currency ?? 'VND';

        return view('wallet::index', compact('wallets', 'totalBalance', 'totalOpening', 'creditCardDebt', 'homeCurrency'));
    }

    public function create(Request $request): View
    {
        $homes = $request->user()
            ->homeMembers()
            ->with('home')
            ->whereIn('role', ['owner', 'manager'])
            ->get()
            ->pluck('home');

        $selectedHomeId = $request->get('home_id');

        return view('wallet::create', compact('homes', 'selectedHomeId'));
    }

    public function store(StoreWalletRequest $request): RedirectResponse
    {
        $wallet = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['balance'] = $data['opening_balance'];
            $data['sort_order'] = $data['sort_order'] ?? 0;
            $data['currency'] = $data['currency'] ?? 'VND';

            return Wallet::create($data);
        });

        AuditLogger::log('wallet.created', [
            'wallet_id' => $wallet->id,
            'home_id' => $wallet->home_id,
            'opening_balance' => $wallet->opening_balance,
        ]);

        return redirect()->route('wallets.show', $wallet)
            ->with('success', __('wallet.created'));
    }

    public function show(Request $request, Wallet $wallet): View
    {
        $this->authorize('view', $wallet);

        $currentBalance = $wallet->calculatedBalance();

        $expenses = $wallet->expenses()
            ->whereNull('transfer_id')
            ->with('category')
            ->latest('occurred_at')
            ->limit(30)
            ->get()
            ->map(fn($item) => [
                'type' => $item->type,
                'amount' => (float) $item->amount,
                'description' => $item->description ?: $item->category?->name,
                'icon' => $item->category?->icon ?? '📝',
                'category_name' => $item->category?->name,
                'occurred_at' => $item->occurred_at,
            ]);

        $transfersOut = $wallet->transfersFrom()
            ->with('toWallet')
            ->latest('occurred_at')
            ->limit(30)
            ->get()
            ->map(fn($item) => [
                'type' => 'expense',
                'amount' => (float) $item->amount,
                'description' => 'Chuyển đến ' . ($item->toWallet?->name ?? 'Ví khác'),
                'icon' => '📤',
                'category_name' => 'Chuyển khoản',
                'occurred_at' => $item->occurred_at,
            ]);

        $transfersIn = $wallet->transfersTo()
            ->with('fromWallet')
            ->latest('occurred_at')
            ->limit(30)
            ->get()
            ->map(fn($item) => [
                'type' => 'income',
                'amount' => (float) $item->amount,
                'description' => 'Nhận từ ' . ($item->fromWallet?->name ?? 'Ví khác'),
                'icon' => '📥',
                'category_name' => 'Chuyển khoản',
                'occurred_at' => $item->occurred_at,
            ]);

        $recentExpenses = $expenses->concat($transfersOut)->concat($transfersIn)
            ->sortByDesc('occurred_at')
            ->take(30);

        return view('wallet::show', compact('wallet', 'currentBalance', 'recentExpenses'));
    }

    public function edit(Request $request, Wallet $wallet): View
    {
        $this->authorize('update', $wallet);

        return view('wallet::edit', compact('wallet'));
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('update', $wallet);

        DB::transaction(function () use ($request, $wallet) {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            if (! $locked) {
                abort(404);
            }

            $data = $request->validated();

            // If opening_balance changes, adjust balance field accordingly
            if (isset($data['opening_balance']) && (float) $data['opening_balance'] !== (float) $locked->opening_balance) {
                $delta = (float) $data['opening_balance'] - (float) $locked->opening_balance;
                $data['balance'] = (float) $locked->balance + $delta;
            }

            $locked->update($data);
        });

        AuditLogger::log('wallet.updated', [
            'wallet_id' => $wallet->id,
            'home_id' => $wallet->home_id,
        ]);

        return redirect()->route('wallets.show', $wallet)
            ->with('success', __('wallet.updated'));
    }

    public function destroy(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('delete', $wallet);

        DB::transaction(function () use ($wallet) {
            $locked = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            if (! $locked) {
                abort(404);
            }

            if (! $locked->canDelete()) {
                throw new \RuntimeException(__('wallet.cannot_delete_with_balance'));
            }

            $locked->delete();

            AuditLogger::log('wallet.deleted', [
                'wallet_id' => $locked->id,
                'home_id' => $locked->home_id,
            ]);
        });

        return redirect()->route('wallets.index')
            ->with('success', __('wallet.deleted'));
    }

    public function archive(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('archive', $wallet);

        $wallet->archive();

        AuditLogger::log('wallet.archived', [
            'wallet_id' => $wallet->id,
            'home_id' => $wallet->home_id,
        ]);

        return back()->with('success', __('wallet.archived'));
    }

    public function restore(Request $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('archive', $wallet);

        $wallet->unarchive();

        AuditLogger::log('wallet.restored', [
            'wallet_id' => $wallet->id,
            'home_id' => $wallet->home_id,
        ]);

        return back()->with('success', __('wallet.restored'));
    }
}
