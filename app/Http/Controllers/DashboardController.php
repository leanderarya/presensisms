<?php

namespace App\Http\Controllers;

use App\Models\pegawai;
use App\Models\pengajuan_izin;
use App\Models\konfigurasi_shift_kerja;
use App\Models\set_jam_kerja;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    // Dashboard User
    public function index(){
        $hariini = date("Y-m-d");
        $bulanini = date("m") * 1;
        $tahunini = date("Y");
        $email = Auth::user()->email;
        $hariSekarang = date('l');
    
        // Konversi hari Inggris ke bahasa Indonesia
        $hariIndo = [
            "Sunday" => "Minggu",
            "Monday" => "Senin",
            "Tuesday" => "Selasa",
            "Wednesday" => "Rabu",
            "Thursday" => "Kamis",
            "Friday" => "Jumat",
            "Saturday" => "Sabtu"
        ];
        $hariIniIndo = $hariIndo[$hariSekarang];
    
        // Ambil data user dari tabel pegawais
        $user = DB::table('pegawais')->where('email', $email)->first();
        $user2 = Auth::user();
        $kode_pegawai = $user2->id;

        // Pengecekan posisi 
        $posisi = $user->posisi;
        $kodeJamKerja = null;

        // Tentukan kode jam kerja berdasarkan posisi
        switch ($posisi) {
            case 'staff':
                $kodeJamKerja = 'ST';
                break;
            case 'security':
                $kodeJamKerja = 'SC';
                break;
            case 'cleaning service':
                $kodeJamKerja = 'CS';
                break;
            case 'operator':
                $kodeJamKerja = 'OP';
                break;
            default:
                $kodeJamKerja = null;
                break;
        }

        // Ambil shift kerja berdasarkan kode jam kerja
        $shiftKerja = null;
        if ($kodeJamKerja) {
            $shiftKerja = DB::table('konfigurasi_shift_kerja')
                ->where('kode_jamkerja', 'LIKE', "$kodeJamKerja%")
                ->get();
        }
        
        // Ambil data presensi hari ini
        $presensihariini = DB::table('presensi')
            ->where('email', $email)
            ->where('tanggal_presensi', $hariini)
            ->first();
    
        // Ambil histori presensi bulan ini termasuk izin/sakit
        $historibulanini = DB::table('presensi')
            ->where('email', $email)
            ->whereRaw('MONTH(tanggal_presensi) = ?', [$bulanini])
            ->whereRaw('YEAR(tanggal_presensi) = ?', [$tahunini])
            ->select(
                'tanggal_presensi',
                'jam_in',
                'jam_out',
                DB::raw("'h' as status"),
                DB::raw("NULL as keterangan")
            )
            ->unionAll(
                DB::table('pengajuan_izin')
                    ->where('kode_pegawai', $user->id)
                    ->where('status_approved', 1)
                    ->whereRaw('MONTH(tanggal_izin) = ?', [$bulanini])
                    ->whereRaw('YEAR(tanggal_izin) = ?', [$tahunini])
                    ->select(
                        'tanggal_izin as tanggal_presensi',
                        DB::raw("NULL as jam_in"),
                        DB::raw("NULL as jam_out"),
                        'status',
                        'keterangan'
                    )
            )
            ->orderBy('tanggal_presensi', 'desc')
            ->get();
    
        // Ambil shift kerja pegawai untuk seluruh minggu
        $jadwalMingguan = DB::table('set_jam_kerja')
        ->join('konfigurasi_shift_kerja', 'set_jam_kerja.kode_jamkerja', '=', 'konfigurasi_shift_kerja.kode_jamkerja')
        ->select('set_jam_kerja.hari', 'konfigurasi_shift_kerja.*', 'set_jam_kerja.updated_at')
        ->orderByRaw("FIELD(set_jam_kerja.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
        ->get();
    
        $updatedAtTerbaru = DB::table('set_jam_kerja')
            ->where('id', $kode_pegawai)  // Sesuaikan dengan kolom id user jika ada
            ->max('updated_at');
    
        // Rekap presensi bulanan
        $rekappresensi = DB::table('presensi')
            ->selectRaw('COUNT(email) as hadir, SUM(IF(jam_in > "07:00",1,0)) as terlambat')
            ->where('email', $email)
            ->whereRaw('MONTH(tanggal_presensi) = ?', [$bulanini])
            ->whereRaw('YEAR(tanggal_presensi) = ?', [$tahunini])
            ->first();
    
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    
        // Rekap Izin / Sakit
        $rekapizin = DB::table('pengajuan_izin')
            ->selectRaw('
                SUM(IF(status="i",1,0)) as jmlizin, 
                SUM(IF(status="s",1,0)) as jmlsakit
            ')
            ->where('kode_pegawai', $kode_pegawai)
            ->whereRaw('MONTH(tanggal_izin) = ?', [$bulanini])
            ->whereRaw('YEAR(tanggal_izin) = ?', [$tahunini])
            ->where('status_approved', 1)
            ->first();

        // Shift Kerja Terisi
        $shiftKerjaTerisi = set_jam_kerja::where('id', auth()->user()->id)->get();

        // Ambil data shift kerja pegawai untuk hari ini
        $hariIni = date('l');
        $hariIndo = [
            "Sunday" => "Minggu",
            "Monday" => "Senin",
            "Tuesday" => "Selasa",
            "Wednesday" => "Rabu",
            "Thursday" => "Kamis",
            "Friday" => "Jumat",
            "Saturday" => "Sabtu"
        ];
        $hariIniIndo = $hariIndo[$hariIni];

        $shift = DB::table('set_jam_kerja')
            ->join('konfigurasi_shift_kerja', 'set_jam_kerja.kode_jamkerja', '=', 'konfigurasi_shift_kerja.kode_jamkerja')
            ->where('set_jam_kerja.id', auth()->user()->id)
            ->where('set_jam_kerja.hari', $hariIniIndo)
            ->select('konfigurasi_shift_kerja.*')
            ->first();
    
        return Inertia::render('User/Dashboard', [
            'shiftKerja' => $shiftKerja,
            'presensihariini' => $presensihariini,
            'historibulanini' => $historibulanini,
            'namabulan' => $namabulan,
            'bulanini' => $bulanini,
            'tahunini' => $tahunini,
            'rekapPresensi' => $rekappresensi,
            'user' => $user,
            'rekapizin' => $rekapizin,
            'jadwalMingguan' => $jadwalMingguan,
            'updatedAtTerbaru' => $updatedAtTerbaru,
            'shiftKerjaTerisi' => $shiftKerjaTerisi,
            'posisi' => $posisi,
            'shift' => $shift,
        ]);
    }

    public function setShiftKerja(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'nama' => 'required|string|max:255',
            'shift' => 'required|array',
            'shift.*' => 'nullable|string|max:255',
        ]);
    
        $id = $validated['id'];
        $nama = $validated['nama'];
        $shiftData = $validated['shift'];
    
        try {
            // Hapus data lama jika ada
            set_jam_kerja::where('id', $id)->delete();
    
            // Simpan shift baru
            foreach ($shiftData as $day => $kodeJamKerja) {
                if ($kodeJamKerja) {
                    set_jam_kerja::create([
                        'id' => $id,
                        'nama' => $nama,
                        'hari' => ucfirst($day),
                        'kode_jamkerja' => $kodeJamKerja,
                    ]);
                }
            }
    
            // Mengembalikan respons sukses untuk Inertia
            return back()->with('success', 'Shift berhasil disimpan!');
        } catch (\Exception $e) {
            \Log::error("Error menyimpan shift: " . $e->getMessage());
    
            // Mengembalikan respons error untuk Inertia
            return back()->with('error', 'Terjadi kesalahan saat menyimpan shift kerja.');
        }
    }


    // Dashboard Admin
    public function dashboardAdmin()
    {   
        $totalPegawai = pegawai::count();
        $tanggalTahunHariIni = now()->toDateString();
        $hariIni = now()->locale('id')->translatedFormat('l');

        // Filter data izin dan sakit berdasarkan tanggal hari ini
        $totalIzin = pengajuan_izin::where('status', 'i')
            ->whereDate('tanggal_izin', $tanggalTahunHariIni)
            ->count();

        $totalSakit = pengajuan_izin::where('status', 's')
            ->whereDate('tanggal_izin', $tanggalTahunHariIni)
            ->count();

        // Rekap Presensi
        $rekappresensi = DB::table('presensi as p')
            ->join('set_jam_kerja as s', 'p.kode_pegawai', '=', 's.id')
            ->join('konfigurasi_shift_kerja as k', 's.kode_jamkerja', '=', 'k.kode_jamkerja')
            ->selectRaw('
                COUNT(p.id) as total_hadir,
                SUM(IF(p.jam_in > k.jam_masuk, 1, 0)) as terlambat,
                SUM(IF(p.jam_in <= k.jam_masuk, 1, 0)) as tepat_waktu
            ')
            ->whereDate('p.tanggal_presensi', $tanggalTahunHariIni)
            ->where('s.hari', '=', $hariIni)
            ->first();

        return Inertia::render('Dashboard', [
            'rekappresensi' => $rekappresensi,
            'totalPegawai' => $totalPegawai,
            'totalIzin' => $totalIzin,
            'totalSakit' => $totalSakit,
        ]);
    }

}
