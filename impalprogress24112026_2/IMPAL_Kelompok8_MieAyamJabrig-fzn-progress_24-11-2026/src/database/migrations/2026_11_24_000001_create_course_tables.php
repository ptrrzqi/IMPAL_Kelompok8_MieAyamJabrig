<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entity layer FR-04 (Main Course) & FR-05 (Mata Kuliah).
 * Tabel: mata_kuliah, main_course, materi, tugas, pengumpulan_tugas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mk', 20)->unique();
            $table->string('nama_mk');
            $table->foreignId('dosen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('semester', 50);
            $table->text('deskripsi')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->string('warna', 7)->default('#ed1e28');
            $table->timestamps();
        });

        // Enrollment mahasiswa ke mata kuliah
        Schema::create('main_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('matakuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->string('status_course', 20)->default('aktif')->comment('aktif, selesai');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'matakuliah_id']);
        });

        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matakuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->unsignedTinyInteger('minggu_ke');
            $table->string('judul_materi');
            $table->text('deskripsi')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();
        });

        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matakuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->unsignedTinyInteger('minggu_ke');
            $table->string('judul_tugas');
            $table->text('deskripsi')->nullable();
            $table->dateTime('waktu_buka');
            $table->dateTime('deadline');
            $table->string('file_lampiran')->nullable();
            $table->string('nama_lampiran')->nullable();
            $table->timestamps();
        });

        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_tugas');
            $table->string('nama_file');
            $table->text('catatan')->nullable();
            $table->dateTime('waktu_kumpul');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->unique(['tugas_id', 'mahasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumpulan_tugas');
        Schema::dropIfExists('tugas');
        Schema::dropIfExists('materi');
        Schema::dropIfExists('main_course');
        Schema::dropIfExists('mata_kuliah');
    }
};
