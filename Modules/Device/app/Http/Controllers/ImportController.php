<?php

namespace Modules\Device\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Device\Imports\DevicesImport;
use Modules\Room\Models\Room;

class ImportController extends Controller
{
    public function showForm(): View
    {
        return view('device::import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        $room = Room::findOrFail($request->integer('room_id'));
        $this->authorize('update', $room);

        Excel::import(
            new DevicesImport($room->id),
            $request->file('file')
        );

        return redirect()->route('devices.index')
            ->with('success', __('device.import_completed'));
    }
}
