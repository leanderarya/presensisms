<?php

namespace App\Http\Controllers;

use App\Models\pegawai;
use App\Models\presensi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\pengajuan_izin;

class PresensiController extends Controller
{
    //
    public function create()
    {
        $hariini = date("Y-m-d");
        $email = Auth::user()->email;
        $cek = DB::table('presensi')
            ->where('tanggal_presensi', $hariini)
            ->where('email', $email)
            ->count();
    
        return Inertia::render('User/Create', [
            'cek' => $cek,
        ]);
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        $nama = $user->name;
        $kode_pegawai = $user->id;
        $email = $user->email;
        $tanggal_presensi = date("Y-m-d");
        $jam = date("H:i:s");
    
        // Lokasi Kantor -7.023826563310556, 110.50695887209068
        $latitudekantor = -7.020083672655566;
        $longitudekantor = 110.42742316137034;
        // Lokasi Kantor -7.023826563310556, 110.50695887209068 //artefak -7.059935504906368, 110.42837090396569
        $latitudekantor = -7.059935504906368;
        $longitudekantor = 110.42837090396569;
    
        // Lokasi User
        $lokasi = $request->lokasi;
        $lokasiuser = explode(",", $lokasi);
        $latitudeuser = $lokasiuser[0];
        $longitudeuser = $lokasiuser[1];
        $jarak = $this->distance($latitudekantor, $longitudekantor, $latitudeuser, $longitudeuser);
        $radius = round($jarak["meters"]);
    
        $image = $request->image;
    
        // Validasi input
        $request->validate([
            'tipeAbsen' => 'required|string|in:masuk,pulang',
            'image' => 'required|string',
            'lokasi' => 'required|string',
        ]);
    
        $tipeAbsen = $request->input('tipeAbsen');
    
        // Mendapatkan hari ini dalam format bahasa Indonesia
        $hariIni = date('l'); // Mendapatkan hari dalam bahasa Inggris
        $hariIndonesia = [
            "Monday" => "Senin",
            "Tuesday" => "Selasa",
            "Wednesday" => "Rabu",
            "Thursday" => "Kamis",
            "Friday" => "Jumat",
            "Saturday" => "Sabtu",
            "Sunday" => "Minggu",
        ];
        $hariSekarang = $hariIndonesia[$hariIni];

        // Cek apakah pegawai memiliki izin atau sakit yang sudah disetujui untuk hari ini
        $izinDisetujui = DB::table('pengajuan_izin')
            ->where('kode_pegawai', $kode_pegawai)
            ->where('tanggal_izin', $tanggal_presensi)
            ->where('status_approved', 1)
            ->first();

        if ($izinDisetujui) {
            return response()->json([
                'error' => 'Anda tidak perlu presensi!',
                'message' => 'Anda sudah mendapatkan izin ' . ($izinDisetujui->status == 'i' ? 'Izin' : 'Sakit') . '. Tidak perlu melakukan presensi.',
            ], 403);
        }
    
        // Cek hari kerja pegawai di set_jam_kerja
        $cekHariKerja = DB::table('set_jam_kerja')
            ->where('nama', $nama)
            ->where('hari', $hariSekarang)
            ->first();
    
        if (!$cekHariKerja) {
            return response()->json([
                'error' => 'Hari ini bukan hari kerja Anda!',
                'message' => 'Anda tidak dapat melakukan presensi di hari yang tidak dijadwalkan.',
            ], 403);
        }
    
        // Ambil konfigurasi shift kerja berdasarkan kode jam kerja
        $shiftKerja = DB::table('konfigurasi_shift_kerja')
            ->where('kode_jamkerja', $cekHariKerja->kode_jamkerja)
            ->first();
    
        if (!$shiftKerja) {
            return response()->json([
                'error' => 'Konfigurasi shift tidak ditemukan.',
                'message' => 'Silakan pilih jam kerja dahulu.',
            ], 500);
        }
    
        // Validasi jam masuk dan pulang berdasarkan shift kerja
        if ($tipeAbsen === 'masuk' && ($jam < $shiftKerja->awal_jam_masuk)) {
            return response()->json([
                'error' => 'Tidak dalam waktu absensi masuk!',
                'message' => 'Anda hanya bisa absen masuk antara ' . $shiftKerja->awal_jam_masuk . ' - ' . $shiftKerja->jam_masuk,
            ], 403);
        }
    
        if ($tipeAbsen === 'pulang' && $jam < $shiftKerja->jam_pulang) {
            return response()->json([
                'error' => 'Belum waktunya pulang!',
                'message' => 'Anda hanya bisa absen pulang setelah ' . $shiftKerja->jam_pulang,
            ], 403);
        }
    
        // Cek radius lokasi kantor
        if ($radius > 25) {
            return response()->json([
                'error' => 'Anda berada di luar radius kantor!',
                'message' => 'Maaf, Anda tidak dapat melakukan presensi karena berada di luar radius yang diizinkan. (' . $radius . ' meter)',
            ], 403);
        }
    
        // Pisahkan data base64 dan decode gambar
        $image_parts = explode(";base64,", $image);
        if (count($image_parts) < 2) {
            return response()->json(['error' => 'Format gambar tidak valid!'], 400);
        }
    
        $image_base64 = base64_decode($image_parts[1]);
    
        // Menyimpan file gambar
        $folderPath = "uploads/absensi/";
        $emailName = explode('@', $email)[0]; // Mengambil bagian sebelum @
        $formatName = $emailName . "-" . $tanggal_presensi . "-" . $tipeAbsen;
        $fileName = $formatName . ".png";
        $filePath = $folderPath . $fileName;
    
        Storage::disk('public')->put($filePath, $image_base64);
    
        // Cek apakah sudah ada presensi untuk hari ini
        $cek = DB::table('presensi')
            ->where('tanggal_presensi', $tanggal_presensi)
            ->where('email', $email)
            ->first();
    
        if ($cek) {
            // Proses Absen Pulang
            if ($tipeAbsen === 'pulang') {
                $data_pulang = [
                    'jam_out' => $jam,
                    'foto_out' => $fileName,
                    'lokasi_out' => $lokasi,
                ];
                $update = DB::table('presensi')
                    ->where('tanggal_presensi', $tanggal_presensi)
                    ->where('email', $email)
                    ->update($data_pulang);
    
                if ($update) {
                    return response()->json([
                        'message' => 'Presensi Pulang berhasil disimpan!',
                        'file_path' => Storage::url($filePath),
                    ]);
                } else {
                    return response()->json([
                        'error' => 'Gagal menyimpan presensi pulang ke database.',
                    ], 500);
                }
            }
        } else {
            // Proses Absen Masuk
            if ($tipeAbsen === 'masuk') {
                $data = [
                    'nama' => $nama,
                    'kode_pegawai' => $kode_pegawai,
                    'email' => $email,
                    'tanggal_presensi' => $tanggal_presensi,
                    'jam_in' => $jam,
                    'foto_in' => $fileName,
                    'lokasi_in' => $lokasi,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $simpan = DB::table('presensi')->insert($data);
    
                if ($simpan) {
                    return response()->json([
                        'message' => 'Presensi Masuk berhasil disimpan!',
                        'file_path' => Storage::url($filePath),
                    ]);
                } else {
                    return response()->json([
                        'error' => 'Gagal menyimpan presensi masuk ke database.',
                    ], 500);
                }
            }
        }
    
        return response()->json(['error' => 'Tipe absen tidak valid.'], 400);
    }

    //Menghitung Jarak
    public function distance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return compact('meters');

    }

    // 
    public function editprofile(){
        $user = Auth::user();
        $email = $user->email;
        $pegawai = DB::table('pegawais')->where('email', $email)->first();
        return Inertia::render('User/Profile',[
            'pegawai' => $pegawai,
            'successMessage' => session('success'),
            'errorMessage' => session('error'),
    ]);
    }



    public function updateprofile(Request $request)
    {
        $user = Auth::user();
    
        // Validasi input
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255', // Nama lengkap wajib
            'no_hp' => 'required|string|max:20',        // Nomor HP wajib
            'password' => 'nullable|string|min:6',      // Password opsional, minimal 6 karakter
        ]);
    
        // Update data di tabel pegawais
        $updatePegawai = DB::table('pegawais')
            ->where('email', $user->email)
            ->update([
                'nama_lengkap' => $request->nama_lengkap,
                'no_hp' => $request->no_hp,
            ]);
    
        // Update data di tabel users
        $user->name = $request->nama_lengkap; // Update nama lengkap di tabel `users`
    
        // Jika password diisi, hash dan simpan
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
    
        $updateUser = $user->save(); // Simpan perubahan di tabel `users`
    
        // Redirect dengan pesan sukses atau error
        if ($updatePegawai || $updateUser) {
            return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
        } else {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui profil.');
        }
    }

    // Histori Absensi
    public function histori(){
        $namabulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        return Inertia::render('User/Histori',[
            'namabulan' => $namabulan,
    ]);
    }

    public function getHistori(Request $request)
    {
        try {
            $bulan = $request->bulan;
            $tahun = $request->tahun;
            $user = Auth::user();
            $kode_pegawai = $user->id;
    
            // Ambil data presensi
            $presensi = DB::table('presensi')
                ->whereRaw('MONTH(tanggal_presensi) = ?', [$bulan])
                ->whereRaw('YEAR(tanggal_presensi) = ?', [$tahun])
                ->where('kode_pegawai', $kode_pegawai)
                ->orderBy('tanggal_presensi')
                ->get();
    
            // Ambil data pengajuan izin hanya yang disetujui (status_approved = 1)
            $izin = DB::table('pengajuan_izin')
                ->select(
                    'tanggal_izin AS tanggal_presensi',
                    'status',
                    'keterangan',
                    'status_approved',
                    DB::raw('NULL as jam_in'),
                    DB::raw('NULL as jam_out')
                )
                ->whereRaw('MONTH(tanggal_izin) = ?', [$bulan])
                ->whereRaw('YEAR(tanggal_izin) = ?', [$tahun])
                ->where('kode_pegawai', $kode_pegawai)
                ->where('status_approved', 1) // Filter hanya yang disetujui
                ->orderBy('tanggal_izin')
                ->get();
    
            // Gabungkan hasil presensi dan izin
            $histori = collect($presensi)->merge($izin)->sortBy('tanggal_presensi')->values();
    
            return response()->json($histori);
        } catch (\Exception $e) {
            // Debug error
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function izin() {
        $user = Auth::user();
        $kode_pegawai = $user->id;
    
        $dataizin = DB::table('pengajuan_izin')
            ->where('kode_pegawai', $kode_pegawai)
            ->orderBy('tanggal_izin', 'asc') // Mengurutkan berdasarkan tanggal izin secara ascending
            ->get();
        
        return Inertia::render('User/Izin', [
            'dataizin' => $dataizin
        ])->with([
            'successMessage' => session('successMessage'),
            'errorMessage' => session('errorMessage'),
        ]);
    }

    public function batalkanIzin($id)
    {
        // Ambil data izin berdasarkan ID
        $izin = DB::table('pengajuan_izin')->where('id', $id)->first();
    
        // Cek apakah izin ditemukan
        if (!$izin) {
            return response()->json(['error' => 'Izin tidak ditemukan!'], 404);
        }
    
        // Cek apakah izin masih pending (status_approved = 0)
        if ($izin->status_approved != 0) {
            return response()->json([
                'error' => 'Izin tidak dapat dibatalkan!',
                'message' => 'Izin sudah disetujui atau ditolak.'
            ], 403);
        }
    
        // Hapus izin jika masih pending
        DB::table('pengajuan_izin')->where('id', $id)->delete();
    
        return response()->json([
            'message' => 'Izin berhasil dibatalkan!',
            'success' => true
        ], 200);
    }

    public function buatizin(){
        return Inertia::render('User/BuatIzin',[
    ]);
    }

    public function storeizin(Request $request)
    {
        $user = Auth::user();
        $kode_pegawai = $user->id;
        $status = $request->input('status'); // Ambil status izin
        
        //  Validasi input
        $request->validate([
            'tanggal_izin' => 'required|date',
            'status' => 'required|in:i,s', // 'i' untuk izin, 's' untuk sakit
            'keterangan' => 'required|string|max:255',
            'file' => ($status === 's') ? 'required|file|mimes:pdf|max:2048' : 'nullable|file|mimes:pdf|max:2048',
        ], [
            'file.required' => 'Bukti surat keterangan sakit wajib diunggah jika memilih sakit.',
            'file.mimes' => 'File harus berupa PDF.',
            'file.max' => 'Ukuran file maksimal 2MB.',
        ]);

        //  Cek apakah izin sudah ada untuk tanggal yang sama
        $izinSudahAda = DB::table('pengajuan_izin')
            ->where('kode_pegawai', $kode_pegawai)
            ->where('tanggal_izin', $request->tanggal_izin)
            ->exists();

        if ($izinSudahAda) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah mengajukan izin untuk tanggal ini!',
            ], 400);
        }

        try {
            $filePath = null;

            //  Upload file hanya jika diunggah
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = 'izin_' . $kode_pegawai . '_' . now()->format('YmdHis') . '.pdf'; // Hanya terima PDF
                $filePath = $file->storeAs('uploads/izin', $filename, 'public');
            }

            //  Simpan data izin ke database
            DB::table('pengajuan_izin')->insert([
                'kode_pegawai' => $kode_pegawai,
                'tanggal_izin' => $request->tanggal_izin,
                'status' => $status,
                'keterangan' => $request->keterangan,
                'file_path' => $filePath, // Path file jika ada
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Izin berhasil diajukan.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Presensi Monitoring // Controller Admin
    public function presensiMonitoring()
    {   
        $presensi = presensi::latest()->get();

        $statusPresensi = DB::table('presensi as p')
                ->leftJoin('set_jam_kerja as s', function ($join) {
                $join->on('p.kode_pegawai', '=', 's.id')
                    ->whereRaw("LOWER(s.hari) = LOWER(
                        CASE 
                            WHEN DAYNAME(p.tanggal_presensi) = 'Monday' THEN 'Senin'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Tuesday' THEN 'Selasa'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Wednesday' THEN 'Rabu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Thursday' THEN 'Kamis'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Friday' THEN 'Jumat'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Saturday' THEN 'Sabtu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Sunday' THEN 'Minggu'
                        END
                    )"); 
            })
            ->leftJoin('konfigurasi_shift_kerja as k', 's.kode_jamkerja', '=', 'k.kode_jamkerja')
            ->select(
                'p.nama',
                'p.jam_in',
                'p.jam_out',
                'p.tanggal_presensi',
                's.hari AS shift_hari',
                's.kode_jamkerja',
                'k.nama_jamkerja',
                'k.jam_pulang',
                DB::raw("COALESCE(k.jam_masuk, 'Tidak ada data') AS akhir_jam_masuk")
            )
            ->orderBy('p.tanggal_presensi', 'asc')
            ->get();


        return Inertia::render('Admin/MonitoringPresensi',['presensi' => $presensi,'statusPresensi' => $statusPresensi]);
    }

    public function laporan()
    {   
        $namabulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        $namaPegawai = pegawai::orderBy('nama_lengkap')->get();
        return Inertia::render('Admin/LaporanPresensi',['namabulan' => $namabulan, 'namaPegawai' => $namaPegawai]);
    }

    public function cetakLaporanPegawai(Request $request)
    {
        try {
            // Ambil parameter dari query string
            $bulan = $request->query('bulan');
            $tahun = $request->query('tahun');
            $kode_pegawai = $request->query('idPegawai');

            // Query data presensi
            $histori = DB::table('presensi')
            ->whereRaw('MONTH(tanggal_presensi) = ?', [$bulan])
            ->whereRaw('YEAR(tanggal_presensi) = ?', [$tahun])
            ->where('kode_pegawai', $kode_pegawai)
            ->join('pegawais', 'presensi.kode_pegawai', '=', 'pegawais.id') 
            ->select('presensi.*', 'pegawais.posisi','pegawais.foto','pegawais.no_hp')
            ->orderBy('tanggal_presensi')
            ->get();



            $statusPresensi = DB::table('presensi as p')
                ->leftJoin('set_jam_kerja as s', function ($join) {
                $join->on('p.kode_pegawai', '=', 's.id')
                    ->whereRaw("LOWER(s.hari) = LOWER(
                        CASE 
                            WHEN DAYNAME(p.tanggal_presensi) = 'Monday' THEN 'Senin'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Tuesday' THEN 'Selasa'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Wednesday' THEN 'Rabu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Thursday' THEN 'Kamis'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Friday' THEN 'Jumat'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Saturday' THEN 'Sabtu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Sunday' THEN 'Minggu'
                        END
                    )"); 
            })
            ->leftJoin('konfigurasi_shift_kerja as k', 's.kode_jamkerja', '=', 'k.kode_jamkerja')
            ->select(
                'p.nama',
                'p.jam_in',
                'p.jam_out',
                'p.tanggal_presensi',
                's.hari AS shift_hari',
                's.kode_jamkerja',
                'k.jam_pulang',
                DB::raw("COALESCE(k.jam_masuk, 'Tidak ada data') AS akhir_jam_masuk")
            )
            ->where('p.kode_pegawai', $kode_pegawai)
            ->orderBy('p.tanggal_presensi', 'asc')
            ->get();

            // Jika tidak ada data, tampilkan pesan di halaman cetak
            if ($histori->isEmpty()) {
                return Inertia::render('Admin/CetakLaporan', [
                    'histori' => [],
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'statusPresensi' => $statusPresensi,
                    'error' => 'Tidak ada data untuk bulan dan tahun yang dipilih.',
                ]);
            }

            // Kirim data ke view untuk dicetak
            return Inertia::render('Admin/CetakLaporan', [
                'histori' => $histori,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'statusPresensi' => $statusPresensi,

            ]);
        } catch (\Exception $e) {
            // Tampilkan error jika terjadi masalah
            return Inertia::render('Admin/CetakLaporan', [
                'histori' => [],
                'bulan' => null,
                'tahun' => null,
                'statusPresensi' => null,

                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ]);
        }
    }

   public function exportExcel(Request $request)
    {
        try {
            // Validasi input tanpa exists
            $request->validate([
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2000',
                'idPegawai' => 'required|integer',
            ]);

            $bulan = $request->bulan;
            $tahun = $request->tahun;
            $idPegawai = $request->idPegawai;

            // Ambil data presensi
            $dataPresensi = presensi::where('kode_pegawai', $idPegawai)
                ->whereYear('tanggal_presensi', $tahun)
                ->whereMonth('tanggal_presensi', $bulan)
                ->orderBy('tanggal_presensi', 'asc')
                ->get();

            $statusPresensi = DB::table('presensi as p')
                ->leftJoin('set_jam_kerja as s', function ($join) {
                $join->on('p.kode_pegawai', '=', 's.id')
                    ->whereRaw("LOWER(s.hari) = LOWER(
                        CASE 
                            WHEN DAYNAME(p.tanggal_presensi) = 'Monday' THEN 'Senin'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Tuesday' THEN 'Selasa'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Wednesday' THEN 'Rabu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Thursday' THEN 'Kamis'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Friday' THEN 'Jumat'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Saturday' THEN 'Sabtu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Sunday' THEN 'Minggu'
                        END
                    )"); 
            })
            ->leftJoin('konfigurasi_shift_kerja as k', 's.kode_jamkerja', '=', 'k.kode_jamkerja')
            ->select(
                'p.nama',
                'p.jam_in',
                'p.jam_out',
                'p.tanggal_presensi',
                's.hari AS shift_hari',
                's.kode_jamkerja',
                DB::raw("COALESCE(k.jam_masuk, 'Tidak ada data') AS akhir_jam_masuk")
            )
            ->where('p.kode_pegawai', $idPegawai)
            ->orderBy('p.tanggal_presensi', 'asc')
            ->get();

            if ($dataPresensi->isEmpty()) {
                return response()->json(['error' => 'Tidak ada data presensi'], 404);
            }
            if ($statusPresensi->isEmpty()) {
                return response()->json(['error' => 'Tidak ada data presensi'], 404);
            }

           return response()->json([
                'dataPresensi' => $dataPresensi,
                'statusPresensi' => $statusPresensi,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function showPageRekap()
    {   
        $namabulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        return Inertia::render('Admin/RekapPresensi', ['namabulan' => $namabulan]);
    }

    public function getRekapPresensi(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        
        $subQuery = DB::table('set_jam_kerja')
            ->join('konfigurasi_shift_kerja', 'konfigurasi_shift_kerja.kode_jamkerja', '=', 'set_jam_kerja.kode_jamkerja')
            ->select(
                'set_jam_kerja.id',
                'set_jam_kerja.hari',
                'konfigurasi_shift_kerja.jam_masuk'
            );

        $rekapPresensi = DB::table('presensi')
                ->whereRaw('MONTH(tanggal_presensi) = ?', [$bulan])
                ->whereRaw('YEAR(tanggal_presensi) = ?', [$tahun])
                ->join('pegawais', 'presensi.kode_pegawai', '=', 'pegawais.id')
                ->leftJoinSub($subQuery, 'shift_data', function ($join) {
                    $join->on('pegawais.id', '=', 'shift_data.id')
                        ->whereRaw("LOWER(shift_data.hari) = LOWER(
                            CASE 
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Monday' THEN 'Senin'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Tuesday' THEN 'Selasa'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Wednesday' THEN 'Rabu'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Thursday' THEN 'Kamis'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Friday' THEN 'Jumat'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Saturday' THEN 'Sabtu'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Sunday' THEN 'Minggu'
                            END
                        )");
                })
                ->leftJoin('pengajuan_izin', 'presensi.kode_pegawai', '=', 'pengajuan_izin.kode_pegawai')
                ->select(
                    'presensi.id',
                    'presensi.kode_pegawai',
                    'presensi.tanggal_presensi',
                    'presensi.jam_in',
                    'presensi.jam_out',
                    'pegawais.nama_lengkap',
                    'pegawais.posisi',
                    'pegawais.foto',
                    'pegawais.no_hp',
                    DB::raw('SUM(CASE WHEN pengajuan_izin.status = "i" AND pengajuan_izin.status_approved = 1 THEN 1 ELSE 0 END) AS total_izin'),
                    DB::raw('SUM(CASE WHEN pengajuan_izin.status = "s" AND pengajuan_izin.status_approved = 1 THEN 1 ELSE 0 END) AS total_sakit')
                )
                ->groupBy(
                    'presensi.id',
                    'presensi.kode_pegawai',
                    'presensi.tanggal_presensi',
                    'presensi.jam_in',
                    'presensi.jam_out',
                    'pegawais.nama_lengkap',
                    'pegawais.posisi',
                    'pegawais.foto',
                    'pegawais.no_hp'
                )
                ->orderBy('presensi.tanggal_presensi')
                ->get();





        $statusPresensi = DB::table('presensi as p')
            ->leftJoin('set_jam_kerja as s', function ($join) {
                $join->on('p.kode_pegawai', '=', 's.id')
                    ->whereRaw("LOWER(s.hari) = LOWER(
                        CASE 
                            WHEN DAYNAME(p.tanggal_presensi) = 'Monday' THEN 'Senin'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Tuesday' THEN 'Selasa'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Wednesday' THEN 'Rabu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Thursday' THEN 'Kamis'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Friday' THEN 'Jumat'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Saturday' THEN 'Sabtu'
                            WHEN DAYNAME(p.tanggal_presensi) = 'Sunday' THEN 'Minggu'
                        END
                    )");
            })
            ->leftJoin('konfigurasi_shift_kerja as k', 's.kode_jamkerja', '=', 'k.kode_jamkerja')
            ->select(
                'p.kode_pegawai',
                'p.nama',
                'p.jam_in',
                'p.jam_out',
                'p.tanggal_presensi',
                's.hari AS shift_hari',
                's.kode_jamkerja',
                DB::raw("COALESCE(k.jam_masuk, 'Tidak ada data') AS akhir_jam_masuk"),
                DB::raw("CASE WHEN TIME(p.jam_in) > TIME(k.jam_masuk) THEN 1 ELSE 0 END AS terlambat")
            )
            ->orderBy('p.tanggal_presensi', 'asc')
            ->get();

        // Mengelompokkan data berdasarkan pegawai dan menghitung jumlah keterlambatan
        $rekapKeterlambatan = $statusPresensi->groupBy('kode_pegawai')->map(function ($items) {
            return [
                'nama' => $items->first()->nama,
                'jumlah_keterlambatan' => $items->sum('terlambat'),
                'total_presensi' => $items->count()
            ];
        });



        // dd($rekapPresensi, $rekapKeterlambatan);

        return Inertia::render('Admin/CetakRekap', ['rekapPresensi' => $rekapPresensi,'rekapKeterlambatan' => $rekapKeterlambatan, 'bulan' => $bulan,'tahun' => $tahun]);
    }

    public function getRekapExcel(Request $request)
    {
        try {
            $request->validate([
                'bulan' => 'required|integer',
                'tahun' => 'required|integer'
            ]);

            $bulan = $request->bulan;
            $tahun = $request->tahun;

            $subQuery = DB::table('set_jam_kerja')
            ->join('konfigurasi_shift_kerja', 'konfigurasi_shift_kerja.kode_jamkerja', '=', 'set_jam_kerja.kode_jamkerja')
            ->select(
                'set_jam_kerja.id',
                'set_jam_kerja.hari',
                'konfigurasi_shift_kerja.jam_masuk'
            );

            $rekapPresensi = DB::table('presensi')
                ->whereRaw('MONTH(tanggal_presensi) = ?', [$bulan])
                ->whereRaw('YEAR(tanggal_presensi) = ?', [$tahun])
                ->join('pegawais', 'presensi.kode_pegawai', '=', 'pegawais.id')
                ->leftJoinSub($subQuery, 'shift_data', function ($join) {
                    $join->on('pegawais.id', '=', 'shift_data.id')
                        ->whereRaw("LOWER(shift_data.hari) = LOWER(
                            CASE 
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Monday' THEN 'Senin'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Tuesday' THEN 'Selasa'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Wednesday' THEN 'Rabu'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Thursday' THEN 'Kamis'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Friday' THEN 'Jumat'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Saturday' THEN 'Sabtu'
                                WHEN DAYNAME(presensi.tanggal_presensi) = 'Sunday' THEN 'Minggu'
                            END
                        )");
                })
                ->leftJoin('pengajuan_izin', 'presensi.kode_pegawai', '=', 'pengajuan_izin.kode_pegawai')
                ->select(
                    'presensi.id',
                    'presensi.kode_pegawai',
                    'presensi.tanggal_presensi',
                    'presensi.jam_in',
                    'presensi.jam_out',
                    'pegawais.nama_lengkap',
                    'pegawais.posisi',
                    'pegawais.foto',
                    'pegawais.no_hp',
                    DB::raw('SUM(CASE WHEN pengajuan_izin.status = "i" AND pengajuan_izin.status_approved = 1 THEN 1 ELSE 0 END) AS total_izin'),
                    DB::raw('SUM(CASE WHEN pengajuan_izin.status = "s" AND pengajuan_izin.status_approved = 1 THEN 1 ELSE 0 END) AS total_sakit')
                )
                ->groupBy(
                    'presensi.id',
                    'presensi.kode_pegawai',
                    'presensi.tanggal_presensi',
                    'presensi.jam_in',
                    'presensi.jam_out',
                    'pegawais.nama_lengkap',
                    'pegawais.posisi',
                    'pegawais.foto',
                    'pegawais.no_hp'
                )
                ->orderBy('presensi.tanggal_presensi')
                ->get();





            $statusPresensi = DB::table('presensi as p')
                ->leftJoin('set_jam_kerja as s', function ($join) {
                    $join->on('p.kode_pegawai', '=', 's.id')
                        ->whereRaw("LOWER(s.hari) = LOWER(
                            CASE 
                                WHEN DAYNAME(p.tanggal_presensi) = 'Monday' THEN 'Senin'
                                WHEN DAYNAME(p.tanggal_presensi) = 'Tuesday' THEN 'Selasa'
                                WHEN DAYNAME(p.tanggal_presensi) = 'Wednesday' THEN 'Rabu'
                                WHEN DAYNAME(p.tanggal_presensi) = 'Thursday' THEN 'Kamis'
                                WHEN DAYNAME(p.tanggal_presensi) = 'Friday' THEN 'Jumat'
                                WHEN DAYNAME(p.tanggal_presensi) = 'Saturday' THEN 'Sabtu'
                                WHEN DAYNAME(p.tanggal_presensi) = 'Sunday' THEN 'Minggu'
                            END
                        )");
                })
                ->leftJoin('konfigurasi_shift_kerja as k', 's.kode_jamkerja', '=', 'k.kode_jamkerja')
                ->select(
                    'p.kode_pegawai',
                    'p.nama',
                    'p.jam_in',
                    'p.jam_out',
                    'p.tanggal_presensi',
                    's.hari AS shift_hari',
                    's.kode_jamkerja',
                    DB::raw("COALESCE(k.jam_masuk, 'Tidak ada data') AS akhir_jam_masuk"),
                    DB::raw("CASE WHEN TIME(p.jam_in) > TIME(k.jam_masuk) THEN 1 ELSE 0 END AS terlambat")
                )
                ->orderBy('p.tanggal_presensi', 'asc')
                ->get();

                // dd($rekapPresensi);
            // Mengelompokkan data berdasarkan pegawai dan menghitung jumlah keterlambatan
            $rekapKeterlambatan = $statusPresensi->groupBy('kode_pegawai')->map(function ($items) {
                return [
                    'nama' => $items->first()->nama,
                    'jumlah_keterlambatan' => $items->sum('terlambat'),
                    'total_presensi' => $items->count()
                ];
            });

            
            if ($rekapPresensi->isEmpty()) {
                return response()->json(['error' => 'Tidak ada data presensi']);
            }
            
            if ($rekapKeterlambatan->isEmpty()){
                return response()->json(['error' => ' Tidak ada data keterlambatan presensi']);
            }
            
            return response()->json([
                'rekapPresensi' => $rekapPresensi,
                'rekapKeterlambatan' => $rekapKeterlambatan
            ]);

        } catch (\Throwable $th) {
            return response()->json(['error', $th->getMessage()],500);
        }
    }

    public function showIzinSakit()
    {   
         $dataIzinSakit = pengajuan_izin::with('namaPengaju')->get()->map(function ($dataIzinSakit) {
            return [
                'id' => $dataIzinSakit->id,
                'tanggal_izin' => $dataIzinSakit->tanggal_izin,
                'status' => $dataIzinSakit->status,
                'keterangan' => $dataIzinSakit->keterangan,
                'status_approved' => $dataIzinSakit->status_approved,
                'namaPengaju' => $dataIzinSakit->namaPengaju->nama_lengkap ?? 'Tidak di temukan' ,
                'file_path' => $dataIzinSakit->file_path ? Storage::url($dataIzinSakit->file_path) : null,
            ];
        });

        return Inertia::render('Admin/IzinSakit',['dataIzinSakit' => $dataIzinSakit]);
    }

    public function approvalIzin(Request $request, $id)
    {
        $request->validate([
            'status_approved' => 'required',
        ]);

        $izinSakit = pengajuan_izin::find($id);

        if (!$izinSakit) {
            return response()->json(['message' => 'Data tidk ditemukan'], 404);
        }

        $izinSakit->status_approved = $request->status_approved;

        $izinSakit->save();

        return response()->json(['message' => 'Status berhasil diperbarui'], 200);
    }

    public function showSuratIzin ($id)
    {
        $suratIzin = pengajuan_izin::where('id', $id)
                ->whereNotNull('file_path')
                ->firstOrFail();

        $filepath = storage_path('app/public/') . $suratIzin->file_path;

        return response()->download($filepath);
    }
}
