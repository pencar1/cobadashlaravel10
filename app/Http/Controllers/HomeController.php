<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\User;
use App\Models\Barang;
use App\Models\Ruangan;
use App\Models\Transportasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;


class HomeController extends Controller
{
    public function dashboard(){
        $dataPeminjaman = Peminjaman::whereIn('status', ['diproses', 'disetujui'])->with(['user', 'barang', 'transportasi', 'ruangan'])->orderBy('idpeminjaman', 'desc')->get();

        $userCount = User::count();
        $barangCount = Barang::count();
        $ruanganCount = Ruangan::count();
        $transportasiCount = Transportasi::count();
        return view('dashboard', compact('dataPeminjaman', 'userCount','barangCount','ruanganCount','transportasiCount'));
    }

    public function editstp($id)
    {
        $data = Peminjaman::with(['barang', 'transportasi', 'ruangan'])->find($id);
        if (!$data) {
            return redirect()->route('admin.peminjaman')->withErrors('Data tidak ditemukan.');
        }

        $jenisAset = $data->getJenisAset();
        $namaAset = $data->getAsetName();

        return view('editstp', compact('data', 'jenisAset', 'namaAset'));
    }


    public function updatestp(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggalpeminjaman' => 'required|date',
            'lampiran'          => 'nullable|mimes:jpeg,png,jpg,gif,pdf,docx|max:2048',
            'jumlahaset'        => 'required|integer|min:1',
            'status'            => 'required|in:diproses,ditolak,disetujui,dipinjam',
            'alasanpenolakan'   => 'nullable|string|max:50',
            'jenisaset'         => 'required|in:barang,transportasi,ruangan',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $peminjaman = Peminjaman::find($id);
        if (!$peminjaman) {
            return redirect()->route('admin.peminjaman')->withErrors('Data tidak ditemukan.');
        }

        $peminjaman->tanggalpeminjaman = $request->input('tanggalpeminjaman');
        $peminjaman->status = $request->input('status');

        // Update alasanpenolakan only if the status is 'ditolak'
        if ($request->input('status') == 'ditolak') {
            $peminjaman->alasanpenolakan = $request->input('alasanpenolakan');
        } else {
            $peminjaman->alasanpenolakan = null;
        }

        // Jika status berubah menjadi 'dipinjam', kurangi jumlah aset dari stok
        if ($request->input('status') == 'dipinjam') {
            // Mendapatkan jenis aset yang terkait dengan peminjaman
            $jenisaset = null;
            if ($peminjaman->idbarang) {
                $jenisaset = 'barang';
            } elseif ($peminjaman->idtransportasi) {
                $jenisaset = 'transportasi';
            } elseif ($peminjaman->idruangan) {
                $jenisaset = 'ruangan';
            }

            // Mengurangi stok aset yang terkait dengan peminjaman yang dipinjam
            switch ($jenisaset) {
                case 'barang':
                    $barang = Barang::find($peminjaman->idbarang);
                    if ($barang) {
                        $barang->kurangiStokb($peminjaman->jumlahaset);
                    }
                    break;
                case 'transportasi':
                    $transportasi = Transportasi::find($peminjaman->idtransportasi);
                    if ($transportasi) {
                        $transportasi->kurangiStokt($peminjaman->jumlahaset);
                    }
                    break;
                case 'ruangan':
                    $ruangan = Ruangan::find($peminjaman->idruangan);
                    if ($ruangan) {
                        $ruangan->kurangiStokr($peminjaman->jumlahaset);
                    }
                    break;
                default:
                    break;
            }
        }

        // Handle lampiran file if exists
        if ($request->hasFile('lampiran')) {
            if ($peminjaman->lampiran) {
                $oldFilePath = public_path('lampiran/' . $peminjaman->lampiran);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            $lampiran = $request->file('lampiran');
            $lampiranName = time() . '_' . $lampiran->getClientOriginalName();
            $lampiran->move(public_path('lampiran'), $lampiranName);
            $peminjaman->lampiran = $lampiranName;
        }

        $peminjaman->jumlahaset = $request->input('jumlahaset');
        $peminjaman->save();

        return redirect()->route('admin.dashboard')->with('success', 'Data berhasil diperbarui.');
    }


    // public function index(){

    //     $data = User::get();

    //     return view('index', compact('data'));
    // }

    // public function profil(){
    //     $user = Auth::user(); // Mendapatkan pengguna yang sedang login
    //     return view('admin.profil', compact('user'));
    // }

    // public function updateProfil(Request $request){
    //     $user = Auth::user(); // Mendapatkan pengguna yang sedang login

    //     // Aturan validasi dengan pengecualian email pengguna saat ini
    //     $validator = Validator::make($request->all(), [
    //         'nama'          => '|string|max:50',
    //         'prodi'         => '|string|max:50',
    //         'nim'           => '|string|max:16',
    //         'nohp'          => '|string|max:16',
    //         'organisasi'    => '|string|max:50',
    //         'email' => [
    //             '',
    //             'email',
    //             'max:255',
    //             Rule::unique('users', 'email')->ignore($user->id),
    //         ],
    //         'password' => [
    //         'nullable',
    //         'string',
    //         'min:8',
    //         'regex:/[a-z]/', // at least one lowercase letter
    //         'regex:/[A-Z]/', // at least one uppercase letter
    //     ],
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withInput()->withErrors($validator);
    //     }

    //     $user->nama = $request->input('nama');
    //     $user->prodi = $request->input('prodi');
    //     $user->nim = $request->input('nim');
    //     $user->nohp = $request->input('nohp');
    //     $user->organisasi = $request->input('organisasi');
    //     $user->email = $request->input('email');

    //     if ($request->password) {
    //         $user->password = Hash::make($request->input('password'));
    //     }

    //     $user->save();

    //     return redirect()->route('admin.profil')->with('success', 'Profil berhasil diperbarui.');
    // }


    // public function create(){
    //     return view('create');
    // }

    // public function store(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'nama'          => 'required|string|max:50',
    //         'prodi'         => 'required|string|max:50',
    //         'nim'           => 'required|string|max:16',
    //         'nohp'          => 'required|string|max:16',
    //         'organisasi'    => 'required|string|max:50',
    //         'email'         => 'required|email|max:255|unique:users,email',
    //         'password'      => [
    //             'required',
    //             'string',
    //             'min:8',
    //             'regex:/[a-z]/', // at least one lowercase letter
    //             'regex:/[A-Z]/', // at least one uppercase letter
    //         ],
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withInput()->withErrors($validator);
    //     }

    //     $data['nama']           = $request->input('nama');
    //     $data['prodi']          = $request->input('prodi');
    //     $data['nim']            = $request->input('nim');
    //     $data['nohp']           = $request->input('nohp');
    //     $data['organisasi']     = $request->input('organisasi');
    //     $data['email']          = $request->input('email');
    //     $data['password']       = Hash::make($request->input('password'));

    //     User::create($data);

    //     return redirect()->route('admin.index')->with('success', 'Data berhasil ditambahkan.');
    // }


    // public function edit(Request $request,$id){
    //     $data = User::find($id);

    //     return view('edit', compact('data'));
    // }

    // public function update(Request $request, $id)
    // {
    //     // Ambil data pengguna yang ada berdasarkan ID
    //     $user = User::findOrFail($id);

    //     // Aturan validasi dengan pengecualian email pengguna saat ini
    //     $validator = Validator::make($request->all(), [
    //         'nama'          => 'required|string|max:50',
    //         'prodi'         => 'required|string|max:50',
    //         'nim'           => 'required|string|max:16',
    //         'nohp'          => 'required|string|max:16',
    //         'organisasi'    => 'required|string|max:50',
    //         'email' => [
    //             'required',
    //             'email',
    //             'max:255',
    //             Rule::unique('users', 'email')->ignore($user->id),
    //         ],
    //         'password' => [
    //             'nullable',
    //             'string',
    //             'min:8',
    //             'regex:/[a-z]/', // setidaknya satu huruf kecil
    //             'regex:/[A-Z]/', // setidaknya satu huruf besar
    //         ],
    //         'role' => 'required|string', // Menambahkan validasi untuk role
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withInput()->withErrors($validator);
    //     }

    //     $data['nama']           = $request->input('nama');
    //     $data['prodi']          = $request->input('prodi');
    //     $data['nim']            = $request->input('nim');
    //     $data['nohp']           = $request->input('nohp');
    //     $data['organisasi']     = $request->input('organisasi');
    //     $data['email']          = $request->input('email');
    //     $data['role']           = $request->input('role'); // Menambahkan update role

    //     if ($request->password) {
    //         $data['password'] = Hash::make($request->input('password'));
    //     }

    //     User::whereId($id)->update($data);

    //     return redirect()->route('admin.index')->with('success', 'Data berhasil diperbarui.');
    // }



    // public function delete(Request $request,$id){
    //     $data = User::find($id);

    //     if($data){
    //         $data->delete();
    //     }

    //     return redirect()->route('admin.index')->with('success', 'Data berhasil dihapus.');
    // }
}
