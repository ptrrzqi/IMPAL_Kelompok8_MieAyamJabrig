<?php

namespace Database\Seeders;

use App\Models\MataKuliah;
use App\Models\PengumpulanTugas;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data dummy FR-04 (Main Course) & FR-05 (Mata Kuliah).
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $dosen1 = User::where('username', '19850101201012')->firstOrFail();
        $mhs1   = User::where('username', '103012400346')->firstOrFail();

        $dosen2 = User::updateOrCreate(['username' => '19900215201503'], [
            'name' => 'Ahmad Fauzi, S.Kom., M.Kom.', 'email' => 'dosen2@telkomuniversity.ac.id',
            'role' => User::ROLE_DOSEN, 'prodi' => 'S1 Informatika', 'password' => $password, 'email_verified_at' => now(),
        ]);
        $mhs2 = User::updateOrCreate(['username' => '103012400417'], [
            'name' => 'Citra Mahasiswi Lestari', 'email' => 'mahasiswa2@student.telkomuniversity.ac.id',
            'role' => User::ROLE_MAHASISWA, 'prodi' => 'S1 Informatika', 'password' => $password, 'email_verified_at' => now(),
        ]);
        // Mahasiswa tanpa mata kuliah -> demo halaman "Tidak Terdaftar" (P002)
        User::updateOrCreate(['username' => '103012400022'], [
            'name' => 'Dimas Mahasiswa Baru', 'email' => 'mahasiswa3@student.telkomuniversity.ac.id',
            'role' => User::ROLE_MAHASISWA, 'prodi' => 'S1 Informatika', 'password' => $password, 'email_verified_at' => now(),
        ]);

        $courses = [
            ['CSG3H3', 'Implementasi Perangkat Lunak', $dosen1, '#ed1e28', true,
                'Mata kuliah praktik implementasi rancangan perangkat lunak (SKPL & DPPL) menjadi aplikasi.'],
            ['CSG3I3', 'Pengembangan Aplikasi Web Modern', $dosen1, '#18212f', true,
                'Pengembangan aplikasi web menggunakan framework MVC, REST API, dan deployment container.'],
            ['CSG3J3', 'Basis Data Terdistribusi & Cloud', $dosen2, '#475569', true,
                'Konsep basis data terdistribusi, replikasi, sharding, dan layanan basis data cloud.'],
            ['CSG2A3', 'Algoritma & Pemrograman', $dosen2, '#0f766e', false,
                'Dasar algoritma, struktur kontrol, fungsi, array, dan rekursi.'],
        ];

        $mk = [];
        foreach ($courses as [$kode, $nama, $dosen, $warna, $aktif, $desk]) {
            $mk[$kode] = MataKuliah::updateOrCreate(['kode_mk' => $kode], [
                'nama_mk' => $nama, 'dosen_id' => $dosen->id, 'semester' => $aktif ? 'Ganjil 2026/2027' : 'Genap 2025/2026',
                'deskripsi' => $desk, 'status_aktif' => $aktif, 'warna' => $warna,
            ]);
        }

        // Enrollment
        foreach (['CSG3H3' => 'aktif', 'CSG3I3' => 'aktif', 'CSG3J3' => 'aktif', 'CSG2A3' => 'selesai'] as $kode => $status) {
            $mk[$kode]->mahasiswa()->syncWithoutDetaching([$mhs1->id => ['status_course' => $status]]);
        }
        foreach (['CSG3H3', 'CSG3J3'] as $kode) {
            $mk[$kode]->mahasiswa()->syncWithoutDetaching([$mhs2->id => ['status_course' => 'aktif']]);
        }

        // Materi & tugas per minggu
        $topik = [
            'CSG3H3' => ['Pendahuluan & Review SKPL', 'Arsitektur Boundary-Control-Entity', 'Implementasi Modul Login', 'Pengujian Unit & Integrasi'],
            'CSG3I3' => ['Pengenalan Framework MVC', 'Routing & Controller', 'Blade Template & Bootstrap', 'Deployment dengan Docker'],
            'CSG3J3' => ['Konsep Basis Data Terdistribusi', 'Replikasi & Konsistensi', 'Sharding', 'Database as a Service'],
            'CSG2A3' => ['Pengantar Algoritma', 'Percabangan & Perulangan', 'Fungsi & Prosedur', 'Array & Rekursi'],
        ];

        foreach ($topik as $kode => $list) {
            $course = $mk[$kode];
            if ($course->materi()->exists()) {
                continue; // idempotent
            }
            foreach ($list as $i => $judul) {
                $minggu = $i + 1;
                $path = "materi/{$kode}_minggu{$minggu}.pdf";
                Storage::disk('local')->put($path, $this->pdf("{$course->nama_mk} - Minggu {$minggu}: {$judul}"));

                $course->materi()->create([
                    'minggu_ke' => $minggu, 'judul_materi' => "Materi {$minggu}: {$judul}",
                    'deskripsi' => "Slide perkuliahan minggu ke-{$minggu}.",
                    'file_path' => $path, 'file_name' => "{$kode}_Minggu{$minggu}_" . str_replace([' ', '&'], ['_', 'dan'], $judul) . '.pdf',
                ]);
            }

            // Tugas: minggu 1 sudah lewat, minggu 2-3 sedang dibuka, minggu 4 belum dibuka
            $jadwal = [
                1 => [now()->subDays(21), now()->subDays(14)],
                2 => [now()->subDays(7), now()->addDays(3)],
                3 => [now()->subDays(1), now()->addDays(10)],
                4 => [now()->addDays(7), now()->addDays(14)],
            ];
            foreach ([1, 2, 3, 4] as $minggu) {
                $course->tugas()->create([
                    'minggu_ke' => $minggu, 'judul_tugas' => "Tugas {$minggu}: {$list[$minggu - 1]}",
                    'deskripsi' => "Kerjakan latihan terkait topik \"{$list[$minggu - 1]}\". Unggah dalam format PDF/ZIP (maks. 50 MB).",
                    'waktu_buka' => $jadwal[$minggu][0], 'deadline' => $jadwal[$minggu][1],
                ]);
            }
        }

        // Contoh pengumpulan & penilaian untuk mahasiswa 1
        foreach (['CSG3H3', 'CSG3I3', 'CSG2A3'] as $kode) {
            $tugas1 = $mk[$kode]->tugas()->where('minggu_ke', 1)->first();
            if (! $tugas1 || $tugas1->pengumpulan()->where('mahasiswa_id', $mhs1->id)->exists()) {
                continue;
            }
            $path = "pengumpulan/{$tugas1->id}/{$mhs1->username}_tugas1.pdf";
            Storage::disk('local')->put($path, $this->pdf("Jawaban {$mhs1->name} - {$tugas1->judul_tugas}"));
            PengumpulanTugas::create([
                'tugas_id' => $tugas1->id, 'mahasiswa_id' => $mhs1->id, 'file_tugas' => $path,
                'nama_file' => "{$mhs1->username}_Tugas1.pdf", 'catatan' => 'Berikut jawaban tugas 1.',
                'waktu_kumpul' => $tugas1->deadline->copy()->subDays(2),
                'nilai' => $kode === 'CSG3I3' ? null : 88, 'feedback' => $kode === 'CSG3I3' ? null : 'Bagus, pertahankan.',
            ]);
        }

        foreach ($mk as $course) {
            foreach ($course->mahasiswa as $m) {
                $course->recalculateProgress($m);
            }
        }
        // Mata kuliah yang sudah selesai dianggap 100%
        $mk['CSG2A3']->mahasiswa()->updateExistingPivot($mhs1->id, ['progress' => 100]);
    }

    /** Membuat file PDF satu halaman yang valid (untuk data dummy). */
    private function pdf(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 16 Tf 60 760 Td ({$text}) Tj 0 -30 Td /F1 11 Tf (CeLOE LMS - Telkom University - data dummy prototipe) Tj ET";
        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $out = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $obj) {
            $offsets[] = strlen($out);
            $out .= ($i + 1) . " 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $out .= sprintf("%010d 00000 n \n", $o);
        }

        return $out . "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
