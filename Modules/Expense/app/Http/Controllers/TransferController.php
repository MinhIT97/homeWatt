<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Expense\Http\Requests\StoreTransferRequest;
use Modules\Expense\Models\Transfer;
use Modules\Expense\Services\TransferService;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class TransferController extends Controller
{
    public function __construct(private readonly TransferService $transferService) {}

    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $transfers = Transfer::whereHas('home.members', fn ($q) => $q->where('user_id', $userId))
            ->with(['fromWallet', 'toWallet', 'user'])
            ->latest('occurred_at')
            ->paginate(20);

        return view('expense::transfer.index', compact('transfers'));
    }

    public function create(Request $request): View
    {
        $userId = $request->user()->id;

        $wallets = Wallet::whereHas('home.members', fn ($q) => $q->where('user_id', $userId)
            ->whereIn('role', ['owner', 'manager']))
            ->where('is_archived', false)
            ->with('home')
            ->get()
            ->groupBy('home_id');

        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId)
            ->whereIn('role', ['owner', 'manager']))->get();

        $selectedHomeId = $request->get('home_id') ?? $homes->first()?->id;

        return view('expense::transfer.create', compact('wallets', 'homes', 'selectedHomeId'));
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        try {
            $transfer = $this->transferService->createTransfer($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        AuditLogger::log('transfer.created', [
            'transfer_id' => $transfer->id,
            'home_id' => $transfer->home_id,
            'amount' => $transfer->amount,
        ]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', __('expense.transfer_created'));
    }

    public function show(Request $request, Transfer $transfer): View
    {
        $this->authorize('view', $transfer);

        $transfer->load(['fromWallet', 'toWallet', 'user', 'expenses']);

        return view('expense::transfer.show', compact('transfer'));
    }

    public function destroy(Request $request, Transfer $transfer): RedirectResponse
    {
        $this->authorize('view', $transfer);

        $transferId = $transfer->id;
        $homeId = $transfer->home_id;

        $this->transferService->reverseTransfer($transfer);

        AuditLogger::log('transfer.reversed', [
            'transfer_id' => $transferId,
            'home_id' => $homeId,
        ]);

        return redirect()->route('transfers.index')
            ->with('success', __('expense.transfer_reversed'));
    }
}
