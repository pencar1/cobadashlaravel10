<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Peminjaman extends Controller
{
    public function peminjaman(){
        return view('admin/peminjaman');
    }
}
