<?php

namespace App\Http\Controllers;

use App\Models\konfigurasi_shift_kerja;
use App\Models\pegawai;
use App\Models\set_jam_kerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        $pegawai = pegawai::latest()->get();
        return inertia('Admin/listPegawai',['pegawai'=> $pegawai]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(Request $request)
    {
        // sleep(2); // Simulasi delay (boleh dihapus jika tidak diperlukan)

        // store ke table pegawai
        $inputPegawai = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'posisi' => 'required|string|max:100',
            'no_hp' => 'required|string|max:15',
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
        ]); 

        if ($request->hasFile('foto')) {
            // Generate nama unik untuk file 
            $fileName = time() . '_' . $request->file('foto')->getClientOriginalName();
            
            // Folder tujuan
            $destinationPath = storage_path('app/public/pegawai');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Pindahkan file dari folder temp ke folder tujuan
            $request->file('foto')->move($destinationPath, $fileName);

            // Simpan path ke database tanpa 'public/'
            $inputPegawai['foto'] = 'pegawai/' . $fileName;
        }

        // Simpan data pegawai ke database
        pegawai::create($inputPegawai);
        
        // logic store ke table users
        User::create([
            'name' => $inputPegawai['nama_lengkap'],
            'email' => $inputPegawai['email'],
            'password' => bcrypt('12345'),
            'role' => 'operator',
        ]);

        
        
        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil ditambahkan');
    }


    /**
     * Display the specified resource.
     */
    public function show(pegawai $pegawai)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(pegawai $pegawai)
    {
        return Inertia::render('Admin/EditPegawai',['pegawai' => $pegawai]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, pegawai $pegawai)
    {

        $inputPegawai = $request->validate([
            'nama_lengkap' => 'string|max:255',
            'email' => 'email|max:255',
            'posisi' => 'string|max:100',
            'no_hp' => 'string|max:15',
            'foto' => 'image|mimes:jpeg,png,jpg|max:2048',
            'tempat_lahir' => 'string|max:100',
            'tanggal_lahir' => 'date',
        ]); 

        if ($request->hasFile('foto')) {
            // Generate nama unik untuk file 
            $fileName = time() . '_' . $request->file('foto')->getClientOriginalName();
            
            // Folder tujuan
            $destinationPath = storage_path('app/public/pegawai');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Pindahkan file dari folder temp ke folder tujuan
            $request->file('foto')->move($destinationPath, $fileName);

            // Simpan path ke database tanpa 'public/'
            $inputPegawai['foto'] = 'pegawai/' . $fileName;
        }

        // Simpan data pegawai ke database
        $pegawai->update($inputPegawai);

        // Update data di tabel users (jika ada relasi)
        if ($pegawai->user) {
            $pegawai->user->update([
                'name' => $inputPegawai['nama_lengkap'] ?? $pegawai->user->name,
                'email' => $inputPegawai['email'] ?? $pegawai->user->email,
            ]);
        }

        return redirect()->route('pegawai.edit', ['pegawai' => $pegawai->id])->with('success', 'Data pegawai berhasil diperbarui');
    
        // dd($request->all());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(pegawai $pegawai)
    {
        $pegawai->user()->delete();
        $pegawai->delete();

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai dan akun user pegawai berhasil di hapus');

        // dd($pegawai);
    }


    // function untuk set Shift jam kerja pegawai
    public function showSetSchedule(pegawai $pegawai){

        $jadwalShift = konfigurasi_shift_kerja::all();
        $cekShift = set_jam_kerja::where('id', $pegawai->id)->count();
        $shift = set_jam_kerja::where('id', $pegawai->id)->get();

        if($cekShift > 0){
        
            return Inertia::render('Admin/UpdateShiftKerja',['pegawai' => $pegawai, 'jadwalShift' => $jadwalShift,'cekShift' => $cekShift,'shift' => $shift]);
        } else {

            return Inertia::render('Admin/setShiftKerja',['pegawai' => $pegawai, 'jadwalShift' => $jadwalShift,'cekShift' => $cekShift,]);
        }
        
    }

}
