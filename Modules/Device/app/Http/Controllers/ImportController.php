<?php

namespace Modules\Device\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Device\Imports\DevicesImport;

class ImportController extends Controller
{
    public function showForm(): \Illuminate\View\View
    {
        return view('device::import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        Excel::import(
            new DevicesImport($request->room_id),
            $request->file('file')
        );

        return redirect()->route('devices.index')
            ->with('success', 'Import completed.');
    }
}
