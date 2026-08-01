<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('reset')) {
            try {
                DB::table('data_latih')->truncate();
                DB::table('usulan_lokasi')->truncate();
                DB::table('evaluasi_model')->truncate();
                DB::table('c45_model')->update(['status' => 'nonaktif']);
                session()->forget(['mock_data_latih', 'mock_usulan_lokasi']);
                return redirect('/dashboard')->with('success', 'Database & data usulan berhasil dikosongkan.');
            } catch (\Exception $e) {
                session()->forget(['mock_data_latih', 'mock_usulan_lokasi']);
                return redirect('/dashboard')->with('success', 'Session mockup data berhasil dikosongkan.');
            }
        }

        $data = $this->safeDb(function() {
            $totalDataLatih = DB::table('data_latih')->count();
            $totalUsulanBaru = DB::table('usulan_lokasi')->count();
            
            $c45Service = new \App\Services\C45Service();
            $cmResult = $c45Service->evaluateConfusionMatrix();
            $accuracyVal = $cmResult['accuracy'] ?? 0;

            // Get Model C4.5 Info
            $activeModel = $c45Service->getActiveModel();
            $modelInfo = [
                'status' => $activeModel ? 'aktif' : 'belum_dibentuk',
                'root_attribute' => $activeModel->root_attribute ?? '-',
                'total_rules' => $activeModel->total_rules ?? 0,
                'total_nodes' => $activeModel->total_nodes ?? 0,
                'total_data_latih' => $activeModel->total_data_latih ?? $totalDataLatih,
                'trained_at' => $activeModel ? Carbon::parse($activeModel->trained_at)->format('d M Y, H:i') : '-',
            ];

            $rawActivity = DB::table('usulan_lokasi')->latest('created_at')->limit(5)->get();
            $recentActivity = $rawActivity->map(function($act) {
                $statusLabel = $act->status === 'layak' ? 'Layak' : ($act->status === 'tidak_layak' ? 'Tidak Layak' : 'Diproses');
                return [
                    'location' => $act->nama,
                    'status' => $statusLabel,
                    'time' => Carbon::parse($act->created_at)->diffForHumans(null, false, true)
                ];
            })->toArray();

            $rawLocations = DB::table('usulan_lokasi')->select('nama as name', 'latitude as lat', 'longitude as lng', 'status')->get();
            $mapLocations = $rawLocations->map(function($loc) {
                return [
                    'name' => $loc->name,
                    'lat' => (float)$loc->lat,
                    'lng' => (float)$loc->lng,
                    'status' => $loc->status
                ];
            })->toArray();

            $chartQuery = DB::table('usulan_lokasi')
                ->select('kecamatan',
                    DB::raw("SUM(case when status = 'layak' then 1 else 0 end) as layak"),
                    DB::raw("SUM(case when status = 'tidak_layak' then 1 else 0 end) as tidak")
                )
                ->groupBy('kecamatan')
                ->get();

            $chartData = $chartQuery->map(function($q) {
                return [
                    'kec' => $q->kecamatan,
                    'layak' => (int)$q->layak,
                    'tidak' => (int)$q->tidak
                ];
            })->toArray();

            return compact('totalDataLatih', 'totalUsulanBaru', 'accuracyVal', 'modelInfo', 'recentActivity', 'mapLocations', 'chartData');
        }, function() {
            // Seed mock lists if empty
            if (!session()->has('mock_data_latih')) {
                session(['mock_data_latih' => []]);
            }
            if (!session()->has('mock_usulan_lokasi')) {
                session(['mock_usulan_lokasi' => []]);
            }

            $c45Service = new \App\Services\C45Service();
            $cmResult = $c45Service->evaluateConfusionMatrix();

            $totalDataLatih = count(session('mock_data_latih'));
            $usulanList = session('mock_usulan_lokasi');
            $totalUsulanBaru = count($usulanList);
            $accuracyVal = $cmResult['accuracy'] > 0 ? $cmResult['accuracy'] : 0;

            $modelInfo = [
                'status' => 'belum_dibentuk',
                'root_attribute' => '-',
                'total_rules' => 0,
                'total_nodes' => 0,
                'total_data_latih' => $totalDataLatih,
                'trained_at' => '-',
            ];

            $recentActivity = array_slice(array_map(fn($act) => [
                'location' => $act['nama'] ?? '',
                'status' => ($act['status'] ?? '') === 'layak' ? 'Layak' : (($act['status'] ?? '') === 'tidak_layak' ? 'Tidak Layak' : 'Diproses'),
                'time' => 'Baru saja'
            ], array_reverse($usulanList)), 0, 5);

            $mapLocations = array_map(fn($loc) => [
                'name' => $loc['nama'] ?? '',
                'lat' => (float)($loc['latitude'] ?? 0),
                'lng' => (float)($loc['longitude'] ?? 0),
                'status' => $loc['status'] ?? 'proses'
            ], $usulanList);

            $chartData = [];
            $groups = [];
            foreach ($usulanList as $loc) {
                $kec = $loc['kecamatan'] ?? 'Cianjur';
                if (!isset($groups[$kec])) {
                    $groups[$kec] = ['layak' => 0, 'tidak' => 0];
                }
                if (($loc['status'] ?? '') === 'layak') {
                    $groups[$kec]['layak']++;
                } elseif (($loc['status'] ?? '') === 'tidak_layak') {
                    $groups[$kec]['tidak']++;
                }
            }
            foreach ($groups as $kec => $v) {
                $chartData[] = [
                    'kec' => $kec,
                    'layak' => $v['layak'],
                    'tidak' => $v['tidak']
                ];
            }

            return compact('totalDataLatih', 'totalUsulanBaru', 'accuracyVal', 'modelInfo', 'recentActivity', 'mapLocations', 'chartData');
        });

        $modelInfo = $data['modelInfo'];

        $summaryCards = $data['summaryCards'] ?? [
            ['title' => 'Total Data Latih', 'value' => $data['totalDataLatih'] . ' TPS', 'subtitle' => 'Kabupaten Cianjur', 'icon' => 'database', 'color' => 'teal', 'trend' => 'Data Historis DLH', 'href' => url('/data-latih')],
            ['title' => 'Usulan Baru', 'value' => $data['totalUsulanBaru'], 'subtitle' => 'Menunggu Verifikasi', 'icon' => 'map-pin', 'color' => 'amber', 'trend' => 'Realtime DB', 'href' => url('/usulan-lokasi')],
            ['title' => 'Akurasi Model', 'value' => number_format($data['accuracyVal'], 1) . '%', 'subtitle' => 'Algoritma C4.5', 'icon' => 'bar-chart', 'color' => 'green', 'trend' => $modelInfo['status'] === 'aktif' ? 'Model Aktif' : 'Belum Training', 'href' => url('/confusion-matrix')],
        ];

        $recentActivity = $data['recentActivity'];
        $mapLocations = $data['mapLocations'];
        $chartData = $data['chartData'];

        return view('dashboard.index', compact('summaryCards', 'modelInfo', 'recentActivity', 'mapLocations', 'chartData'));
    }
}
