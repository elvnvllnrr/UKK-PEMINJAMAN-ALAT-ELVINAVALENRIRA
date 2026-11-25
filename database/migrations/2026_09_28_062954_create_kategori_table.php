<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Migrasi anonim yang menerapkan perubahan skema database dan pembatalannya. */
return new class extends Migration
{
    /** Membuat tabel kategori alat. */
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /** Mengembalikan perubahan skema pada migrasi ke keadaan sebelumnya. */
    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};
