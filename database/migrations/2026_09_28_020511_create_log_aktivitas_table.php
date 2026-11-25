<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Migrasi anonim yang menerapkan perubahan skema database dan pembatalannya. */
return new class extends Migration
{
    /** Membuat tabel log_aktivitas beserta kolom utama yang digunakan aplikasi. */
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aktivitas'); // Contoh: 'Login', 'Tambah Alat', dll
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /** Mengembalikan perubahan skema pada migrasi ke keadaan sebelumnya. */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};