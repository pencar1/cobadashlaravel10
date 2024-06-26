@extends('layout.layoutstaf')

@section('content')
<div class="page-inner">
    <div class="page-header">
        <h4 class="page-title">Menu Ruangan</h4>

    </div>
    <div class="page-body">
        <div class="row">
            <div class="col-md-12">
                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">Ruangan</h4>
                            <a href="{{ route('staf.ruangan.tambah') }}" class="btn btn-primary btn-round ml-auto">
                                <i class="fa fa-plus"></i>
                                Tambah
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="add-row" class="display table table-striped table-hover" >
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Ruangan</th>
                                        <th>Deskripsi</th>
                                        <th>Foto</th>
                                        <th style="width: 10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $d)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $d->namaruangan }}</td>
                                        <td>{{ $d->deskripsiruangan }}</td>
                                        <td class="text-center">
                                            @if ($d->foto)
                                                <img src="{{ asset('images/ruangan/' . $d->foto) }}" alt="Foto Ruangan" style="max-width: 100px; margin: 10px auto; border: 2px solid #ccc;">
                                            @else
                                                Tidak ada foto
                                            @endif
                                        </td>
                                        <td>
                                            <div class="form-button-action">
                                                <a href="{{ route('staf.ruangan.edit', ['id' => $d->idruangan]) }}" data-toggle="tooltip" title="Ubah Ruangan" class="btn btn-link btn-primary btn-lg">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('staf.ruangan.delete',['id' => $d->idruangan])}}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" data-toggle="tooltip" title="Hapus Ruangan" class="btn btn-link btn-danger deleteButton">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil elemen pesan
        var messageElement = document.querySelector('.alert');

        // Tunggu 3 detik, lalu sembunyikan pesan
        setTimeout(function() {
            if (messageElement) {
                messageElement.style.display = 'none';
            }
        }, 4000); // Waktu dalam milidetik (di sini 3000 milidetik = 3 detik)
    });
</script>
@endsection
