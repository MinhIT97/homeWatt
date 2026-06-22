<?php

namespace Modules\Home\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Home\Http\Requests\InviteMemberRequest;
use Modules\Home\Http\Requests\StoreHomeRequest;
use Modules\Home\Http\Requests\UpdateHomeRequest;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('owner')
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
        $home = Home::create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $request->user()->id,
            'role' => 'owner',
        ]);

        return redirect()->route('homes.show', $home)
            ->with('success', 'Home created successfully.');
    }

    public function show(Request $request, Home $home): View
    {
        $this->authorize('view', $home);

        $home->load(['owner', 'members.user', 'rooms']);

        return view('home::show', compact('home'));
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
            ->with('success', 'Home updated successfully.');
    }

    public function destroy(Request $request, Home $home): RedirectResponse
    {
        $this->authorize('delete', $home);

        $home->delete();

        return redirect()->route('homes.index')
            ->with('success', 'Home deleted.');
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

        $user = User::where('email', $request->validated('email'))->firstOrFail();

        if ($home->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'User is already a member.');
        }

        HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $user->id,
            'role' => $request->validated('role'),
        ]);

        return back()->with('success', 'Member invited successfully.');
    }

    public function removeMember(Request $request, Home $home, HomeMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $home);

        if ($member->home_id !== $home->id) {
            abort(404);
        }

        if ($member->role === 'owner') {
            return back()->with('error', 'Cannot remove the owner.');
        }

        $member->delete();

        return back()->with('success', 'Member removed.');
    }
}
