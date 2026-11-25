<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;

/** Menampilkan riwayat aktivitas sistem untuk administrator. */
class LogAktivitasController extends Controller
{
    /** Menampilkan log terbaru dengan identitas pengguna dan paginasi. */
    public function index()
    {
        $logs = LogAktivitas::with('user')->latest()->paginate(20);

        return view('admin.log-aktivitas.index', compact('logs'));
    }
}
