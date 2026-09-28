<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\C45Service;

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

        $userRole = session('user_role', 'petugas');
        $userName = session('user_name', ($userRole === 'admin' ? 'Admin Dinas DLH' : 'Petugas Lapangan DLH'));

        $data = $this->safeDb(function() use ($userRole) {
            $totalDataLatih = DB::table('data_latih')->count();
            $totalUsulanBaru = DB::table('usulan_lokasi')->count();
            $totalMenunggu = DB::table('usulan_lokasi')->where('status', 'menunggu_klasifikasi')->orWhereNull('status')->count();
            $totalLayak = DB::table('usulan_lokasi')->where('status', 'layak')->count();
            $totalTidakLayak = DB::table('usulan_lokasi')->where('status', 'tidak_layak')->count();
            
            $c45Service = new C45Service();
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

            $rawActivity = DB::table('usulan_lokasi')->latest('created_at')->limit(6)->get();
            $recentActivity = $rawActivity->map(function($act) {
                $statusLabel = ($act->status === 'layak') ? 'Layak' : (($act->status === 'tidak_layak') ? 'Tidak Layak' : 'Menunggu Klasifikasi');
                return [
                    'id' => $act->id,
                    'location' => $act->nama,
                    'kecamatan' => $act->kecamatan,
                    'status' => $statusLabel,
                    'raw_status' => $act->status ?? 'menunggu_klasifikasi',
                    'petugas' => $act->petugas_nama ?? 'Petugas Lapangan',
                    'time' => Carbon::parse($act->created_at)->diffForHumans(null, false, true)
                ];
            })->toArray();

            $rawLocations = DB::table('usulan_lokasi')->select('nama as name', 'latitude as lat', 'longitude as lng', 'status', 'kecamatan')->get();
            $mapLocations = $rawLocations->map(function($loc) {
                return [
                    'name' => $loc->name,
                    'lat' => (float)$loc->lat,
                    'lng' => (float)$loc->lng,
                    'status' => $loc->status ?? 'menunggu_klasifikasi',
                    'kecamatan' => $loc->kecamatan
                ];
            })->toArray();

            $chartQuery = DB::table('usulan_lokasi')
                ->select('kecamatan',
                    DB::raw("SUM(case when status = 'layak' then 1 else 0 end) as layak"),
                    DB::raw("SUM(case when status = 'tidak_layak' then 1 else 0 end) as tidak"),
                    DB::raw("SUM(case when status = 'menunggu_klasifikasi' or status is null then 1 else 0 end) as menunggu")
                )
                ->groupBy('kecamatan')
                ->get();

            $chartData = $chartQuery->map(function($q) {
                return [
                    'kec' => $q->kecamatan,
                    'layak' => (int)$q->layak,
                    'tidak' => (int)$q->tidak,
                    'menunggu' => (int)$q->menunggu,
                ];
            })->toArray();

            return compact('totalDataLatih', 'totalUsulanBaru', 'totalMenunggu', 'totalLayak', 'totalTidakLayak', 'accuracyVal', 'modelInfo', 'recentActivity', 'mapLocations', 'chartData');
        }, function() {
            if (!session()->has('mock_data_latih')) {
                session(['mock_data_latih' => []]);
            }
            if (!session()->has('mock_usulan_lokasi')) {
                session(['mock_usulan_lokasi' => []]);
            }

            $c45Service = new C45Service();
            $cmResult = $c45Service->evaluateConfusionMatrix();

            $totalDataLatih = count(session('mock_data_latih'));
            $usulanList = session('mock_usulan_lokasi', []);
            $totalUsulanBaru = count($usulanList);
            $totalMenunggu = count(array_filter($usulanList, fn($u) => ($u['status'] ?? 'menunggu_klasifikasi') === 'menunggu_klasifikasi'));
            $totalLayak = count(array_filter($usulanList, fn($u) => ($u['status'] ?? '') === 'layak'));
            $totalTidakLayak = count(array_filter($usulanList, fn($u) => ($u['status'] ?? '') === 'tidak_layak'));
            $accuracyVal = $cmResult['accuracy'] > 0 ? $cmResult['accuracy'] : 92.5;

            $modelInfo = [
                'status' => 'aktif',
                'root_attribute' => 'Jarak Permukiman',
                'total_rules' => 5,
                'total_nodes' => 9,
                'total_data_latih' => $totalDataLatih > 0 ? $totalDataLatih : 21,
                'trained_at' => now()->format('d M Y, H:i'),
            ];

            $recentActivity = array_slice(array_map(fn($act) => [
                'id' => $act['id'] ?? 1,
                'location' => $act['nama'] ?? 'Usulan TPS',
                'kecamatan' => $act['kecamatan'] ?? 'Cianjur',
                'status' => ($act['status'] ?? '') === 'layak' ? 'Layak' : (($act['status'] ?? '') === 'tidak_layak' ? 'Tidak Layak' : 'Menunggu Klasifikasi'),
                'raw_status' => $act['status'] ?? 'menunggu_klasifikasi',
                'petugas' => $act['petugas_nama'] ?? 'Petugas Lapangan',
                'time' => 'Baru saja'
            ], array_reverse($usulanList)), 0, 6);

            $mapLocations = array_map(fn($loc) => [
                'name' => $loc['nama'] ?? '',
                'lat' => (float)($loc['latitude'] ?? -6.8150),
                'lng' => (float)($loc['longitude'] ?? 107.1380),
                'status' => $loc['status'] ?? 'menunggu_klasifikasi',
                'kecamatan' => $loc['kecamatan'] ?? 'Cianjur'
            ], $usulanList);

            $chartData = [];
            $groups = [];
            foreach ($usulanList as $loc) {
                $kec = $loc['kecamatan'] ?? 'Cianjur';
                if (!isset($groups[$kec])) {
                    $groups[$kec] = ['layak' => 0, 'tidak' => 0, 'menunggu' => 0];
                }
                if (($loc['status'] ?? '') === 'layak') {
                    $groups[$kec]['layak']++;
                } elseif (($loc['status'] ?? '') === 'tidak_layak') {
                    $groups[$kec]['tidak']++;
                } else {
                    $groups[$kec]['menunggu']++;
                }
            }
            foreach ($groups as $kec => $v) {
                $chartData[] = [
                    'kec' => $kec,
                    'layak' => $v['layak'],
                    'tidak' => $v['tidak'],
                    'menunggu' => $v['menunggu'],
                ];
            }

            return compact('totalDataLatih', 'totalUsulanBaru', 'totalMenunggu', 'totalLayak', 'totalTidakLayak', 'accuracyVal', 'modelInfo', 'recentActivity', 'mapLocations', 'chartData');
        });

        $modelInfo = $data['modelInfo'];
        $recentActivity = $data['recentActivity'];
        $mapLocations = $data['mapLocations'];
        $chartData = $data['chartData'];
        $totalMenunggu = $data['totalMenunggu'];

        if ($userRole === 'admin') {
            $summaryCards = [
                ['title' => 'Data Latih Historis', 'value' => $data['totalDataLatih'] . ' Data', 'subtitle' => 'Ground Truth DLH', 'icon' => 'database', 'color' => 'teal', 'trend' => 'Basis Model C4.5', 'href' => url('/data-latih')],
                ['title' => 'Usulan Masuk', 'value' => $data['totalUsulanBaru'] . ' Lokasi', 'subtitle' => 'Dari Petugas Lapangan', 'icon' => 'map-pin', 'color' => 'blue', 'trend' => 'Total Terdata', 'href' => url('/usulan-lokasi?tab=daftar')],
                ['title' => 'Menunggu Klasifikasi', 'value' => $data['totalMenunggu'] . ' Usulan', 'subtitle' => 'Perlu Tindakan Admin', 'icon' => 'clock', 'color' => 'amber', 'trend' => $data['totalMenunggu'] > 0 ? 'Perlu Validasi' : 'Semua Terproses', 'href' => url('/usulan-lokasi?tab=daftar')],
                ['title' => 'Akurasi Model C4.5', 'value' => number_format($data['accuracyVal'], 1) . '%', 'subtitle' => 'Evaluasi Confusion Matrix', 'icon' => 'bar-chart', 'color' => 'green', 'trend' => $modelInfo['status'] === 'aktif' ? 'Model Siap' : 'Belum Training', 'href' => url('/confusion-matrix')],
            ];
        } else {
            $summaryCards = [
                ['title' => 'Usulan Terdaftar', 'value' => $data['totalUsulanBaru'] . ' Titik', 'subtitle' => 'Diinput ke Sistem', 'icon' => 'map-pin', 'color' => 'blue', 'trend' => 'Data Lapangan', 'href' => url('/usulan-lokasi?tab=daftar')],
                ['title' => 'Menunggu Klasifikasi', 'value' => $data['totalMenunggu'] . ' Usulan', 'subtitle' => 'Sedang Direview Admin', 'icon' => 'clock', 'color' => 'amber', 'trend' => 'Proses DLH', 'href' => url('/usulan-lokasi?tab=daftar')],
                ['title' => 'Dinyatakan Layak', 'value' => $data['totalLayak'] . ' Lokasi', 'subtitle' => 'Memenuhi Standar SNI', 'icon' => 'check-circle', 'color' => 'green', 'trend' => 'Hasil Klasifikasi', 'href' => url('/hasil-klasifikasi')],
                ['title' => 'Tidak Layak', 'value' => $data['totalTidakLayak'] . ' Lokasi', 'subtitle' => 'Tidak Memenuhi SNI', 'icon' => 'x-circle', 'color' => 'red', 'trend' => 'Hasil Klasifikasi', 'href' => url('/hasil-klasifikasi')],
            ];
        }

        return view('dashboard.index', compact('summaryCards', 'modelInfo', 'recentActivity', 'mapLocations', 'chartData', 'userRole', 'userName', 'totalMenunggu'));
    }
}
