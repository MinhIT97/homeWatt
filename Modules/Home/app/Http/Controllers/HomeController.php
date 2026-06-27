<?php

namespace Modules\Home\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Home\Http\Requests\InviteMemberRequest;
use Modules\Home\Http\Requests\StoreHomeRequest;
use Modules\Home\Http\Requests\UpdateHomeRequest;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Home\Services\MemberService;
use Modules\Home\Services\PriceCalculator;

class HomeController extends Controller
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->with(['owner', 'members'])
            ->withCount('rooms')
            ->latest()
            ->paginate(20);

        return view('home::index', compact('homes'));
    }

    public function create(): View
    {
        return view('home::create');
    }

    public function store(StoreHomeRequest $request): RedirectResponse
    {
        $home = DB::transaction(function () use ($request) {
            $home = new Home($request->validated());
            $home->forceFill(['owner_id' => $request->user()->id])->save();

            $this->memberService->createOwnerMembership($home, $request->user());

            return $home;
        });

        return redirect()->route('homes.show', $home)
            ->with('success', __('home.created'));
    }

    public function show(Request $request, Home $home): View
    {
        $this->authorize('view', $home);

        $home->load(['owner', 'members.user', 'rooms']);

        $priceSummary = $this->priceCalculator->calculateHomeTotal($home);
        $roomPrices = $home->rooms()
            ->with('devices:id,room_id,purchase_price,name')
            ->get(['id', 'name', 'price'])
            ->map(fn ($room) => [
                'room' => $room,
                'summary' => $this->priceCalculator->calculateRoomWithDevices($room),
            ]);

        return view('home::show', compact('home', 'priceSummary', 'roomPrices'));
    }

    public function edit(Request $request, Home $home): View
    {
        $this->authorize('update', $home);

        return view('home::edit', compact('home'));
    }

    public function update(UpdateHomeRequest $request, Home $home): RedirectResponse
    {
        $this->authorize('update', $home);

        $home->update($request->validated());

        return redirect()->route('homes.show', $home)
            ->with('success', __('home.updated'));
    }

    public function destroy(Request $request, Home $home): RedirectResponse
    {
        $this->authorize('delete', $home);

        $homeId = $home->id;
        $home->delete();

        AuditLogger::log('home.deleted', ['home_id' => $homeId]);

        return redirect()->route('homes.index')
            ->with('success', __('home.deleted'));
    }

    public function members(Request $request, Home $home): View
    {
        $this->authorize('manageMembers', $home);

        $home->load('members.user');

        return view('home::members', compact('home'));
    }

    public function invite(InviteMemberRequest $request, Home $home): RedirectResponse
    {
        $this->authorize('manageMembers', $home);

        $requestedRole = $request->validated('role');
        $email = $request->validated('email');

        try {
            $membership = $this->memberService->invite($home, $request->user(), $email, $requestedRole);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLogger::log('home.member_invited', [
            'home_id' => $home->id,
            'invited_user_id' => $membership->user_id,
            'role' => $requestedRole,
        ]);

        return back()->with('success', __('home.member_invited'));
    }

    public function removeMember(Request $request, Home $home, HomeMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $home);

        try {
            $this->memberService->remove($home, $member, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLogger::log('home.member_removed', [
            'home_id' => $home->id,
            'removed_user_id' => $member->user_id,
        ]);

        return back()->with('success', __('home.member_removed'));
    }
}
