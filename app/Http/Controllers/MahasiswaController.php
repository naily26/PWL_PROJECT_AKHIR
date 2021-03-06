<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\Request;
use Aws\S3\S3Client;

class MahasiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        //fungsi eloquent menampilkan data menggunakan pagination
        $mahasiswa = Mahasiswa::with('kelas')->get();
        $page = Mahasiswa::orderBy('nim', 'asc')->paginate(6);
        return view('mahasiswa.index', ['mahasiswa' => $mahasiswa, 'page' => $page]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $kelas = Kelas::all(); //mendapatkan data dari tabel kelas
        return view('mahasiswa.create', ['kelas' => $kelas]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //melakukan validasi data
        $request->validate([
            'nim' => 'required',
            'nama' => 'required',
            'kelas' => 'required',
            'foto' => 'required',
            'jenis_kelamin' => 'required',
            'no_handphone' => 'required',
            'alamat' => 'required',
        ]);
        $user = new User;
        $user->name = $request->nama;
        $user->email = $request->nim.'@gmail.com';
        $user->password = bcrypt($request->nim);
        $user->roles = 'mahasiswa';
        $user->save();

        $image = $request->file('foto');
        if ($image) {
            // $image_name = $request->file('foto')->store('images', 'public');
            $file = $request->file('foto');
            $file_name = $file->getClientOriginalName();
            $folder = 'foto_mahasiswa';

            $endpoint = 'https://objectstorage.ap-sydney-1.oraclecloud.com/p/WkE8UWMfyl17I3UTnN5vgQbZA5bvfU4JJVRjq8aunfA0__52dy41DQ3C3sWpdKVb/n/sdg6cgxigeov/b/utscc/o/';

            $s3 = new S3Client([
                        'region'  => 'ap-sydney-1',
                        'version' => 'latest',
                        'credentials' => [
                            'key'    => '51bbbf9327bf9c4a392bee28ac2e9ddc5bc0d192',
                            'secret' => 'clw+ErDdMn8xyc0PQbB2IUIyeg4lnsqlzdLXCflTqQ0='
                        ],
                        'bucket_endpoint' => true,
                        'endpoint' => $endpoint
                    ]);

                    $s3->putObject([
                        'Bucket' => $folder,
                        'Key' => $file_name,
                        'SourceFile' => $file,
                        'StorageClass' => 'REDUCED_REDUNDANCY'
                    ]);
                    $resultUrlImage =  $endpoint . $folder . '/' . $file_name;
 

        }
        // dd($request->all());
        $mahasiswa = new Mahasiswa;
        $mahasiswa->id_user = $user->id;
        $mahasiswa->nim = $request->get('nim');
        $mahasiswa->nama = $request->get('nama');
        $mahasiswa->foto = $resultUrlImage ;
        $mahasiswa->jenis_kelamin = $request->get('jenis_kelamin');
        $mahasiswa->no_handphone = $request->get('no_handphone');
        $mahasiswa->alamat = $request->get('alamat');

        $kelas = new Kelas;
        $kelas->id = $request->get('kelas');
        //fungsi eloquent untuk menambah data dengan relasi belongsTo
        $mahasiswa->kelas()->associate($kelas);

        $mahasiswa->save();
        return redirect()->route('mahasiswa.index')
            ->with('success', 'Mahasiswa Berhasil Ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Mahasiswa  $mahasiswa
     * @return \Illuminate\Http\Response
     */
    public function show(Mahasiswa $mahasiswa)
    {
        return view('mahasiswa.detail', compact('mahasiswa'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Mahasiswa  $mahasiswa
     * @return \Illuminate\Http\Response
     */
    public function edit(Mahasiswa $mahasiswa)
    {
        $kelas = Kelas::all();
        //menampilkan detail data dengan menemukan berdasarkan Nim Mahasiswa untuk diedit
        return view('mahasiswa.edit', compact('mahasiswa','kelas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Mahasiswa  $mahasiswa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //melakukan validasi data
        // $request->validate([
        //     'nim' => 'required',
        //     'nama' => 'required',
        //     'kelas' => 'required',
        //     'foto' => 'required',
        //     'jenis_kelamin' => 'required',
        //     'no_handphone' => 'required',
        //     'alamat' => 'required',
        // ]);
        
        
        // dd($request->all());
        $mahasiswa = Mahasiswa::find($id);
        if ($mahasiswa->foto && file_exists(storage_path('app/public/' . $mahasiswa->foto))) {
            \Storage::delete('public/' . $mahasiswa->foto);
        }
        if($request->file('foto')!=null){
            $image_name = $request->file('foto')->store('images', 'public');
            $mahasiswa->foto = $image_name;
        }
       
        $mahasiswa->nim = $request->get('nim');
        $mahasiswa->nama = $request->get('nama');
        $mahasiswa->jenis_kelamin = $request->get('jenis_kelamin');
        $mahasiswa->no_handphone = $request->get('no_handphone');
        $mahasiswa->alamat = $request->get('alamat');
        $mahasiswa->kelas_id = $request->get('kelas');
        $mahasiswa->save();
        //jika data berhasil diupdate, akan kembali ke halaman utama
        return redirect()->route('mahasiswa.index')
        ->with('success', 'Mahasiswa Berhasil Diupdate');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Mahasiswa  $mahasiswa
     * @return \Illuminate\Http\Response
     */
    public function destroy(Mahasiswa $mahasiswa)
    {
        //fungsi eloquent untuk menghapus data
        $mahasiswa->delete();
        return redirect()->route('mahasiswa.index')
            ->with('success', 'Mahasiswa Berhasil Dihapus');
    }
}
