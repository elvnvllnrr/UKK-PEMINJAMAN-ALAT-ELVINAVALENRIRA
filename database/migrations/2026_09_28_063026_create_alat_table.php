<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Migrasi anonim yang menerapkan perubahan skema database dan pembatalannya. */
return new class extends Migration
{
    /** Membuat tabel alat beserta kolom utama yang digunakan aplikasi. */
    public function up(): void
    {
        Schema::create('alat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->string('nama_alat');
            $table->string('kode_alat')->unique();
            $table->text('deskripsi')->nullable();
            $table->integer('stok')->default(0);
            $table->enum('kondisi', ['Baik', 'Rusak Ringan', 'Rusak Berat'])->default('Baik');
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /** Mengembalikan perubahan skema pada migrasi ke keadaan sebelumnya. */
    public function down(): void
    {
        Schema::dropIfExists('alat');
    }
};
