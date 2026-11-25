<?php

namespace Tests\Feature;

use App\Models\MataKuliah;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FR-04 Main Course & FR-05 Mata Kuliah.
 */
class CourseTest extends TestCase
{
    use RefreshDatabase;

    private User $mhs;
    private User $dosen;
    private MataKuliah $impal;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);
        $this->seed(DatabaseSeeder::class);

        $this->mhs   = User::where('username', '103012400346')->first();
        $this->dosen = User::where('username', '19850101201012')->first();
        $this->impal = MataKuliah::where('kode_mk', 'CSG3H3')->first();
    }

    public function test_dashboard_lists_enrolled_courses_from_database(): void
    {
        $this->actingAs($this->mhs)->get('/dashboard')
            ->assertOk()
            ->assertSee('Implementasi Perangkat Lunak')
            ->assertSee('Algoritma &amp; Pemrograman', false);
    }

    public function test_dashboard_search_and_status_filter(): void
    {
        $this->actingAs($this->mhs)->get('/dashboard?q=Basis')
            ->assertSee('Basis Data Terdistribusi')
            ->assertDontSee('CSG3H3');

        $this->actingAs($this->mhs)->get('/dashboard?status=selesai')
            ->assertSee('Algoritma &amp; Pemrograman', false)
            ->assertDontSee('CSG3H3');
    }

    public function test_unenrolled_student_sees_tidak_terdaftar(): void
    {
        $baru = User::where('username', '103012400022')->first();

        $this->actingAs($baru)->get('/dashboard')->assertSee('Tidak Terdaftar');
        $this->actingAs($baru)->get(route('matakuliah.show', $this->impal))->assertSee('Tidak Terdaftar');
    }

    public function test_student_can_view_course_and_download_materi(): void
    {
        $this->actingAs($this->mhs)->get(route('matakuliah.show', $this->impal))
            ->assertOk()->assertSee('Minggu 1')->assertSee('Unduh');

        $materi = $this->impal->materi()->first();
        $this->actingAs($this->mhs)->get(route('materi.download', $materi))->assertOk();
    }

    public function test_dosen_can_add_materi_and_tugas(): void
    {
        $this->actingAs($this->dosen)->post(route('materi.store', $this->impal), [
            'minggu_ke' => 5, 'judul_materi' => 'Materi 5: Deployment',
            'file' => UploadedFile::fake()->create('slide.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('materi', ['judul_materi' => 'Materi 5: Deployment']);

        $this->actingAs($this->dosen)->post(route('tugas.store', $this->impal), [
            'minggu_ke' => 5, 'judul_tugas' => 'Tugas Besar', 'deskripsi' => 'Kerjakan',
            'waktu_buka' => now()->toDateTimeString(), 'deadline' => now()->addWeek()->toDateTimeString(),
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('tugas', ['judul_tugas' => 'Tugas Besar']);
    }

    public function test_student_cannot_add_materi(): void
    {
        $this->actingAs($this->mhs)->post(route('materi.store', $this->impal), [
            'minggu_ke' => 5, 'judul_materi' => 'X', 'file' => UploadedFile::fake()->create('x.pdf', 10),
        ])->assertForbidden();
    }

    public function test_student_can_submit_open_tugas_and_progress_updates(): void
    {
        $tugas = $this->impal->tugas()->where('minggu_ke', 2)->first(); // sedang dibuka

        $this->actingAs($this->mhs)->post(route('pengumpulan.store', $tugas), [
            'file' => UploadedFile::fake()->create('jawaban.pdf', 200, 'application/pdf'),
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('pengumpulan_tugas', ['tugas_id' => $tugas->id, 'mahasiswa_id' => $this->mhs->id]);
        // 2 dari 4 tugas terkumpul -> 50%
        $this->assertSame(50, (int) $this->impal->mahasiswa()->whereKey($this->mhs->id)->first()->pivot->progress);
    }

    public function test_student_cannot_submit_closed_or_unopened_tugas(): void
    {
        foreach ([1, 4] as $minggu) {
            $tugas = $this->impal->tugas()->where('minggu_ke', $minggu)->first();
            $this->actingAs($this->mhs)->post(route('pengumpulan.store', $tugas), [
                'file' => UploadedFile::fake()->create('x.pdf', 10),
            ])->assertSessionHasErrors('file');
        }
    }

    public function test_dosen_can_grade_submission_and_student_sees_grade(): void
    {
        $tugas = $this->impal->tugas()->where('minggu_ke', 2)->first();
        $this->actingAs($this->mhs)->post(route('pengumpulan.store', $tugas), [
            'file' => UploadedFile::fake()->create('jawaban.pdf', 50),
        ]);
        $p = PengumpulanTugas::where('tugas_id', $tugas->id)->first();

        $this->actingAs($this->dosen)->get(route('tugas.show', $tugas))->assertOk()->assertSee($this->mhs->name);
        $this->actingAs($this->dosen)->patch(route('pengumpulan.nilai', $p), ['nilai' => 92.5, 'feedback' => 'Mantap'])
            ->assertSessionHas('success');

        $this->actingAs($this->mhs)->get(route('tugas.show', $tugas))->assertSee('92.5 / 100')->assertSee('Mantap');
    }

    public function test_other_dosen_cannot_grade(): void
    {
        $dosen2 = User::where('username', '19900215201503')->first();
        $p = PengumpulanTugas::first();

        $this->actingAs($dosen2)->patch(route('pengumpulan.nilai', $p), ['nilai' => 10])->assertForbidden();
    }
}
