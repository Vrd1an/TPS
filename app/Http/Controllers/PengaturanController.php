<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function index()
    {
        $settings = session('settings', [
            'accuracy_threshold' => 80,
            'min_split_size' => 2,
            'min_confidence' => 75,
            'qgis_url' => 'http://localhost/cgi-bin/qgis_mapserv.fcgi',
            'qgis_project' => '/maps/sig_tps.qgs',
            'admin_name' => 'Admin Dinas',
            'admin_email' => 'admin@cianjurkab.go.id',
            'admin_agency' => 'DLH Kab. Cianjur'
        ]);

        return view('pengaturan.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'accuracy_threshold' => 'required|integer|min:0|max:100',
            'min_split_size' => 'required|integer|min:2',
            'min_confidence' => 'required|integer|min:0|max:100',
            'qgis_url' => 'required|url',
            'qgis_project' => 'required|string',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_agency' => 'required|string|max:255',
        ]);

        session(['settings' => $validated]);

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
