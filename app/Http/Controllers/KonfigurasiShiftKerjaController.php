<?php

namespace App\Http\Controllers;

use App\Models\konfigurasi_shift_kerja;
use App\Models\set_jam_kerja;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KonfigurasiShiftKerjaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        $jadwalShift = konfigurasi_shift_kerja::all();
        return Inertia::render('Admin/KonfigurasiJamkerja',['jadwalShift' => $jadwalShift]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $dataShift = $request->validate([
            'kode_jamkerja' => 'required',
            'nama_jamkerja' => 'required',
            'awal_jam_masuk' => 'required',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
        ]);

        if(konfigurasi_shift_kerja::where('kode_jamkerja', $dataShift['kode_jamkerja'])->exists()){
             return back()->withErrors(['kode_jamkerja' => 'Kode jam kerja sudah digunakan'])->withInput();
        }

        sleep(2);

        konfigurasi_shift_kerja::create($dataShift);

        return redirect()->route('konfigurasi.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(konfigurasi_shift_kerja $konfigurasi_shift_kerja)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $konfigurasi_shift_kerja = konfigurasi_shift_kerja::findOrFail($id);
        return Inertia::render('Admin/EditKonfigurasiJamkerja',['konfigurasi_shift_kerja' => $konfigurasi_shift_kerja]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $konfigurasi_shift_kerja = konfigurasi_shift_kerja::find($id);
    
        if (!$konfigurasi_shift_kerja) {
            abort(404, 'Data tidak ditemukan');
        }

        $dataShift = $request->validate([
            'kode_jamkerja' => 'required',
            'nama_jamkerja' => 'required',
            'awal_jam_masuk' => 'required',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
        ]);

        $konfigurasi_shift_kerja->update($dataShift);

        return redirect()->route('konfigurasi.index')->with(['message' => 'Data berhasil diperbarui']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $konfigurasi_shift_kerja = konfigurasi_shift_kerja::find($id);

        if (!$konfigurasi_shift_kerja) {
            return redirect()->route('konfigurasi.index')->withErrors('Data tidak ditemukan.');
        }

        $konfigurasi_shift_kerja->delete();
        return redirect()->route('konfigurasi.index')->with('success', 'Data berhasil dihapus.');
    }


    public function setJamkerja(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'nama' => 'required|string|max:255',
            'shift' => 'required|array',
            'shift.*' => 'nullable|string|max:255'
        ]);

        $id = $validated['id'];
        $nama = $validated['nama'];
        $shiftData = $validated['shift'];

        foreach ($shiftData as $day => $kodeJamKerja) {
            if ($kodeJamKerja) {
                set_jam_kerja::create([
                    'id' => $id,
                    'nama' => $nama,
                    'hari' => ucfirst($day),
                    'kode_jamkerja' => $kodeJamKerja,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Shift berhasil disimpan!');
    }

    public function updateJamkerja(Request $request, $id)
    {   
        // dd($request->all());

        $validated = $request->validate([
            'id' => 'required',
            'nama' => 'required|string|max:255',
            'shift' => 'required|array',
            'shift.*' => 'nullable|string|max:255'
        ]);

        $id = $validated['id'];
        $nama = $validated['nama'];
        $shiftData = $validated['shift'];

        // Hapus shift lama untuk ID tertentu
        set_jam_kerja::where('id', $id)->delete();

        // Loop untuk menyimpan data shift baru
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

        // Kembalikan response
        return redirect()->back()->with('success', 'Shift berhasil diperbarui!');
    }

    

}
