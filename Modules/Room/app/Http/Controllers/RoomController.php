<?php

namespace Modules\Room\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Home\Models\Home;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Http\Requests\UpdateRoomRequest;
use Modules\Room\Models\Room;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::whereHas('home.members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('home')
            ->latest()
            ->paginate(20);

        return view('room::index', compact('rooms'));
    }

    public function create(Request $request): View
    {
        $homes = Home::whereHas('members', fn ($q) => $q->where('user_id', $request->user()->id)
            ->whereIn('role', ['owner', 'manager']))
            ->get();

        $selectedHomeId = $request->get('home_id');

        return view('room::create', compact('homes', 'selectedHomeId'));
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $home = Home::findOrFail($request->validated('home_id'));
        $this->authorizeHomeMember($request, $home);

        $room = Room::create($request->validated());

        return redirect()->route('homes.show', $home)
            ->with('success', 'Room created successfully.');
    }

    public function show(Request $request, Room $room): View
    {
        $this->authorize('view', $room);

        $room->load(['home', 'devices']);

        return view('room::show', compact('room'));
    }

    public function edit(Request $request, Room $room): View
    {
        $this->authorize('update', $room);

        return view('room::edit', compact('room'));
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $this->authorize('update', $room);

        $room->update($request->validated());

        return redirect()->route('rooms.show', $room)
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        $this->authorize('delete', $room);

        $home = $room->home;
        $room->delete();

        return redirect()->route('homes.show', $home)
            ->with('success', 'Room deleted.');
    }

    protected function authorizeHomeMember(Request $request, Home $home): void
    {
        $member = $home->members()->where('user_id', $request->user()->id)->first();

        if (! $member || ! $member->canEdit()) {
            abort(403);
        }
    }
}
