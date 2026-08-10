<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentSegment;
use App\Models\Question;
use App\Models\QuestionPackage;
use App\Models\User;
use App\Services\AssessmentSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_cms_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin_mekanik']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Admin');
    }

    public function test_non_admin_can_not_open_cms_routes(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.questions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_delete_package_that_still_has_unused_questions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);
        $package = QuestionPackage::create([
            'name' => 'Paket HR Hapus Unused',
            'type' => QuestionPackage::TYPE_HR,
            'level' => 'Admin General',
            'is_active' => true,
        ]);
        $question = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'HR',
            'difficulty' => 'basic',
            'text' => 'Soal ikut hapus',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.packages.destroy', $package))
            ->assertRedirect()
            ->assertSessionHas('status', 'Paket berhasil dihapus. 1 soal ikut dihapus, 0 soal dinonaktifkan karena sudah punya riwayat.');

        $this->assertDatabaseMissing('question_packages', ['id' => $package->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_admin_can_delete_package_and_deactivate_used_questions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);
        $participant = User::factory()->create(['role' => User::ROLE_USER]);
        $package = QuestionPackage::create([
            'name' => 'Paket HR Hapus Used',
            'type' => QuestionPackage::TYPE_HR,
            'level' => 'Admin General',
            'is_active' => true,
        ]);
        $question = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'HR',
            'difficulty' => 'basic',
            'text' => 'Soal punya riwayat',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $assessment = Assessment::create([
            'user_id' => $participant->id,
            'question_package_id' => $package->id,
            'total_questions' => 1,
            'started_at' => now(),
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $question->id,
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.packages.destroy', $package))
            ->assertRedirect()
            ->assertSessionHas('status', 'Paket berhasil dihapus. 0 soal ikut dihapus, 1 soal dinonaktifkan karena sudah punya riwayat.');

        $this->assertDatabaseMissing('question_packages', ['id' => $package->id]);
        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_package_id' => null,
            'is_active' => false,
        ]);
    }

    public function test_admin_hr_can_create_admin_general_package(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);

        $this->actingAs($admin)
            ->post(route('admin.packages.store'), [
                'name' => 'Screening HR Admin General',
                'type' => QuestionPackage::TYPE_HR,
                'level' => 'Admin General',
                'description' => 'Paket HR general.',
                'is_active' => '1',
                'min_score_pertimbangan' => 60,
                'min_score_lolos' => 70,
            ])
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHas('status', 'Paket berhasil ditambahkan.');

        $this->assertDatabaseHas('question_packages', [
            'name' => 'Screening HR Admin General',
            'type' => QuestionPackage::TYPE_HR,
            'level' => 'Admin General',
        ]);
    }

    public function test_user_can_start_random_assessment_from_active_questions(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        for ($i = 1; $i <= 12; $i++) {
            Question::create([
                'category' => 'Mechanic',
                'difficulty' => 'basic',
                'text' => 'Pertanyaan '.$i,
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_option' => 'a',
                'is_active' => true,
            ]);
        }

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $this->assertSame(1, Assessment::count());
        $this->assertSame(12, AssessmentAnswer::count());

        $this->actingAs($user)
            ->get(route('assessment.show', Assessment::first()))
            ->assertOk()
            ->assertSee('Assessment Mechanic');
    }

    public function test_mechanic_true_false_question_is_auto_scored(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_MEKANIK]);
        $package = QuestionPackage::create([
            'name' => 'Paket Mekanik Benar Salah',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'question_package_id' => $package->id,
                'type' => Question::TYPE_TRUE_FALSE,
                'category' => 'Engine',
                'difficulty' => 'basic',
                'text' => 'Filter udara tersumbat dapat menurunkan performa engine.',
                'correct_option' => 'a',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.packages.questions', $package->id));

        $question = Question::where('question_package_id', $package->id)->firstOrFail();
        $this->assertSame(Question::TYPE_TRUE_FALSE, $question->type);
        $this->assertSame('Benar', $question->option_a);
        $this->assertSame('Salah', $question->option_b);
        $this->assertNull($question->option_c);
        $this->assertNull($question->option_d);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $assessment = Assessment::firstOrFail();
        $answer = $assessment->answers()->firstOrFail();

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), [
                'answers' => [
                    $answer->id => 'a',
                ],
            ])
            ->assertRedirect(route('assessment.result', $assessment));

        $assessment->refresh();
        $answer->refresh();

        $this->assertTrue($answer->is_correct);
        $this->assertSame(1, $assessment->correct_answers);
        $this->assertEquals(100.0, (float) $assessment->score);
    }

    public function test_hr_assessment_uses_all_active_questions_by_default(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Screening HR Test',
            'type' => QuestionPackage::TYPE_HR,
            'level' => 'Admin General',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            Question::create([
                'question_package_id' => $package->id,
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'category' => 'HR',
                'difficulty' => 'basic',
                'text' => 'Pertanyaan HR '.$i,
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_option' => 'a',
                'points' => 1,
                'is_active' => true,
            ]);
        }

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $assessment = Assessment::firstOrFail();

        $this->assertSame(15, $assessment->total_questions);
        $this->assertSame(15, AssessmentAnswer::where('assessment_id', $assessment->id)->count());
    }

    public function test_user_dashboard_disables_start_when_attempts_are_used(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'max_attempts' => 1,
        ]);

        Assessment::create([
            'user_id' => $user->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Batas Percobaan Terpakai')
            ->assertSee('Percobaan Habis')
            ->assertDontSee('Mulai Assessment');
    }

    public function test_user_dashboard_shows_pending_review_for_she_result(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Review Dashboard',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'max_attempts' => 2,
        ]);

        Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'status' => Assessment::STATUS_PENDING_REVIEW,
            'total_questions' => 72,
            'correct_answers' => 0,
            'score' => 0,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Review SHE')
            ->assertSee('Essay/Portfolio SHE sedang dinilai admin.');
    }

    public function test_user_dashboard_shows_positive_access_days_for_future_expiry(): void
    {
        $this->travelTo(now()->startOfDay());

        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'assessment_access_expires_at' => now()->addDays(7),
            'max_attempts' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Akses: Sisa 7 hari')
            ->assertDontSee('Akses: Telah berakhir');
    }

    public function test_unsubmitted_she_result_redirects_to_active_assessment(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Belum Selesai',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'total_questions' => 3,
            'started_at' => now(),
            'duration_minutes' => 105,
            'ends_at' => now()->addMinutes(105),
        ]);
        AssessmentSegment::create([
            'assessment_id' => $assessment->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'duration_minutes' => 30,
            'order_index' => 0,
            'status' => AssessmentSegment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('assessment.result', $assessment))
            ->assertRedirect(route('assessment.show', $assessment))
            ->assertSessionHas('status', 'Assessment belum selesai. Lanjutkan pengerjaan terlebih dahulu.');
    }

    public function test_she_result_summary_counts_only_multiple_choice_questions(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Ringkasan',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'status' => Assessment::STATUS_PENDING_REVIEW,
            'total_questions' => 4,
            'correct_answers' => 1,
            'score' => 50,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $correctQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'PG benar',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $wrongQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'PG salah',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $essayQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_ESSAY,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'Essay SHE',
            'is_active' => true,
        ]);
        $uploadQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_UPLOAD,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'Upload portfolio',
            'is_active' => true,
        ]);

        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $correctQuestion->id,
            'position' => 1,
            'selected_option' => 'a',
            'is_correct' => true,
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $wrongQuestion->id,
            'position' => 2,
            'selected_option' => 'b',
            'is_correct' => false,
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $essayQuestion->id,
            'position' => 3,
            'answer_text' => 'Jawaban essay',
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $uploadQuestion->id,
            'position' => 4,
            'file_path' => 'assessment-uploads/sample.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('assessment.result', $assessment))
            ->assertOk()
            ->assertSee('PG Benar')
            ->assertDontSee('Jawaban Benar')
            ->assertSee('1<span class="text-sm font-medium text-gray-400">/2</span>', false)
            ->assertDontSee('1<span class="text-sm font-medium text-gray-400">/4</span>', false);
    }

    public function test_admin_she_review_can_see_uploaded_portfolio_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('assessment-uploads/sample.pdf', 'portfolio file');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_SHE,
        ]);
        $package = QuestionPackage::create([
            'name' => 'Paket SHE File Review',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'status' => Assessment::STATUS_PENDING_REVIEW,
            'total_questions' => 1,
            'correct_answers' => 0,
            'score' => 0,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        $uploadQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_UPLOAD,
            'category' => 'SHE Portfolio',
            'difficulty' => 'basic',
            'text' => 'Upload portfolio SHE',
            'is_active' => true,
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $uploadQuestion->id,
            'position' => 1,
            'file_path' => 'assessment-uploads/sample.pdf',
        ]);

        $fileUrl = route('files.show', 'assessment-uploads/sample.pdf');

        $this->actingAs($admin)
            ->get(route('admin.she-review.show', $assessment))
            ->assertOk()
            ->assertSee('File yang diupload:')
            ->assertSee('sample.pdf')
            ->assertSee('Lihat File')
            ->assertSee('Download')
            ->assertSee($fileUrl, false)
            ->assertSee($fileUrl.'?download=1', false);

        $this->actingAs($admin)
            ->get($fileUrl)
            ->assertOk();

        $this->actingAs($admin)
            ->get($fileUrl.'?download=1')
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_file_route_can_read_storage_prefixed_question_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('question-images/sample.png', 'image-bytes');

        $this->get(route('files.show', ['path' => 'storage/question-images/sample.png']))
            ->assertOk();
    }

    public function test_she_review_calculates_final_score_from_segment_averages(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_SHE,
        ]);
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Segment Score',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'status' => Assessment::STATUS_PENDING_REVIEW,
            'total_questions' => 4,
            'correct_answers' => 1,
            'score' => 50,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $correctQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'PG benar',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $wrongQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'PG salah',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $essayQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_ESSAY,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'Essay SHE',
            'is_active' => true,
        ]);
        $uploadQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_UPLOAD,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'Portfolio SHE',
            'is_active' => true,
        ]);

        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $correctQuestion->id,
            'position' => 1,
            'selected_option' => 'a',
            'is_correct' => true,
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $wrongQuestion->id,
            'position' => 2,
            'selected_option' => 'b',
            'is_correct' => false,
        ]);
        $essayAnswer = AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $essayQuestion->id,
            'position' => 3,
            'answer_text' => 'Jawaban essay',
        ]);
        $uploadAnswer = AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $uploadQuestion->id,
            'position' => 4,
            'file_path' => 'assessment-uploads/sample.pdf',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.she-review.grade', $assessment), [
                'scores' => [
                    $essayAnswer->id => 80,
                    $uploadAnswer->id => 70,
                ],
                'notes' => [],
            ])
            ->assertRedirect(route('admin.she-review.index', ['type' => QuestionPackage::TYPE_SHE]));

        $assessment->refresh();

        $this->assertTrue($assessment->isGraded());
        $this->assertEquals(66.67, (float) $assessment->score);
    }

    public function test_user_assessment_uses_assigned_question_package(): void
    {
        $assignedPackage = QuestionPackage::create([
            'name' => 'Paket Assigned',
            'is_active' => true,
        ]);
        $otherPackage = QuestionPackage::create([
            'name' => 'Paket Lain',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'question_package_id' => $assignedPackage->id,
        ]);

        for ($i = 1; $i <= 6; $i++) {
            Question::create([
                'question_package_id' => $assignedPackage->id,
                'category' => 'Mechanic',
                'difficulty' => 'basic',
                'text' => 'Soal paket assigned '.$i,
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_option' => 'a',
                'is_active' => true,
            ]);

            Question::create([
                'question_package_id' => $otherPackage->id,
                'category' => 'Mechanic',
                'difficulty' => 'basic',
                'text' => 'Soal paket lain '.$i,
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_option' => 'a',
                'is_active' => true,
            ]);
        }

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $assessment = Assessment::first();

        $this->assertSame($assignedPackage->id, $assessment->question_package_id);
        $this->assertSame(6, $assessment->answers()->count());

        $assessment->load('answers.question');

        $this->assertTrue(
            $assessment->answers->every(
                fn (AssessmentAnswer $answer): bool => $answer->question->question_package_id === $assignedPackage->id
            )
        );
    }

    public function test_manual_questions_only_trigger_pending_review_for_she_assessment(): void
    {
        $shePackage = QuestionPackage::create([
            'name' => 'Paket SHE Manual',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
        ]);
        $hrPackage = QuestionPackage::create([
            'name' => 'Paket HR Manual Legacy',
            'type' => QuestionPackage::TYPE_HR,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => 'user']);

        $sheAssessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $shePackage->id,
            'total_questions' => 2,
            'started_at' => now(),
        ]);
        $hrAssessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $hrPackage->id,
            'total_questions' => 2,
            'started_at' => now(),
        ]);

        $multipleChoiceQuestion = Question::create([
            'question_package_id' => $shePackage->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE PG',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $sheEssayQuestion = Question::create([
            'question_package_id' => $shePackage->id,
            'type' => Question::TYPE_ESSAY,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE Essay',
            'is_active' => true,
        ]);
        $hrEssayQuestion = Question::create([
            'question_package_id' => $hrPackage->id,
            'type' => Question::TYPE_ESSAY,
            'category' => 'HR',
            'difficulty' => 'basic',
            'text' => 'Legacy HR Essay',
            'is_active' => true,
        ]);

        $sheMcAnswer = AssessmentAnswer::create([
            'assessment_id' => $sheAssessment->id,
            'question_id' => $multipleChoiceQuestion->id,
            'position' => 1,
        ]);
        $sheEssayAnswer = AssessmentAnswer::create([
            'assessment_id' => $sheAssessment->id,
            'question_id' => $sheEssayQuestion->id,
            'position' => 2,
        ]);
        $hrEssayAnswer = AssessmentAnswer::create([
            'assessment_id' => $hrAssessment->id,
            'question_id' => $hrEssayQuestion->id,
            'position' => 1,
        ]);

        app(AssessmentSecurity::class)->finishAssessment($sheAssessment, [
            $sheMcAnswer->id => 'a',
            $sheEssayAnswer->id => 'Jawaban SHE',
        ]);
        app(AssessmentSecurity::class)->finishAssessment($hrAssessment, [
            $hrEssayAnswer->id => 'Jawaban HR legacy',
        ]);

        $this->assertSame(Assessment::STATUS_PENDING_REVIEW, $sheAssessment->fresh()->status);
        $this->assertTrue($sheAssessment->fresh()->isPendingReview());
        $this->assertSame(Assessment::STATUS_GRADED, $hrAssessment->fresh()->status);
        $this->assertFalse($hrAssessment->fresh()->isPendingReview());
    }

    public function test_admin_cannot_create_essay_or_upload_question_for_non_she_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin_hr']);
        $package = QuestionPackage::create([
            'name' => 'Paket HR',
            'type' => QuestionPackage::TYPE_HR,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'question_package_id' => $package->id,
                'type' => Question::TYPE_ESSAY,
                'category' => 'HR',
                'difficulty' => 'basic',
                'text' => 'Essay HR tidak boleh',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Question::where('question_package_id', $package->id)->count());
    }

    public function test_admin_she_can_create_essay_question_for_she_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin_she']);
        $package = QuestionPackage::create([
            'name' => 'Paket SHE',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'question_package_id' => $package->id,
                'type' => Question::TYPE_ESSAY,
                'category' => 'SHE',
                'difficulty' => 'basic',
                'text' => 'Essay SHE boleh',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.packages.questions', $package->id));

        $this->assertDatabaseHas('questions', [
            'question_package_id' => $package->id,
            'type' => Question::TYPE_ESSAY,
            'text' => 'Essay SHE boleh',
        ]);
    }

    public function test_admin_user_form_normalizes_she_segments_by_position(): void
    {
        $admin = User::factory()->create(['role' => 'admin_she']);
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Segment',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Peserta SHE Segment',
                'email' => 'peserta.she.segment@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => User::ROLE_USER,
                'question_package_id' => $package->id,
                'assessment_access_expires_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'assessment_duration_hours' => 1.75,
                'max_attempts' => 1,
                'segment_config' => [
                    ['type' => Question::TYPE_MULTIPLE_CHOICE, 'duration' => 30],
                    ['type' => Question::TYPE_MULTIPLE_CHOICE, 'duration' => 45],
                    ['type' => Question::TYPE_MULTIPLE_CHOICE, 'duration' => 30],
                ],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'peserta.she.segment@example.com')->firstOrFail();

        $this->assertSame([
            ['type' => Question::TYPE_MULTIPLE_CHOICE, 'duration' => 30],
            ['type' => Question::TYPE_ESSAY, 'duration' => 45],
            ['type' => Question::TYPE_UPLOAD, 'duration' => 30],
        ], $user->segment_config);
    }

    public function test_she_assessment_start_normalizes_legacy_duplicate_segments(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Legacy Segment',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'assessment_access_expires_at' => now()->addDays(3),
            'assessment_duration_minutes' => 120,
            'max_attempts' => 1,
            'segment_config' => [
                ['type' => Question::TYPE_MULTIPLE_CHOICE, 'duration' => 30],
                ['type' => Question::TYPE_MULTIPLE_CHOICE, 'duration' => 45],
                ['type' => Question::TYPE_MULTIPLE_CHOICE, 'duration' => 30],
            ],
        ]);

        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE PG legacy',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_ESSAY,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE Essay legacy',
            'is_active' => true,
        ]);
        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_UPLOAD,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE Upload legacy',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('assessment.start'));

        $assessment = Assessment::firstOrFail();

        $response->assertRedirect(route('assessment.show', $assessment));
        $this->assertSame(3, $assessment->answers()->count());
        $this->assertSame(
            [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_ESSAY, Question::TYPE_UPLOAD],
            $assessment->segments()->pluck('type')->all()
        );
        $this->assertSame([30, 45, 30], $assessment->segments()->pluck('duration_minutes')->all());
    }

    public function test_she_assessment_does_not_start_when_required_segments_have_no_active_questions(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Tidak Lengkap',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'assessment_access_expires_at' => now()->addDays(3),
            'segment_config' => config('assessment.she_default_segments'),
        ]);

        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE PG saja',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertSessionHas('status', 'Paket SHE belum lengkap. Soal aktif yang kurang: Essay, Portfolio.');

        $this->assertSame(0, Assessment::count());
    }

    public function test_segmented_assessment_recovers_when_no_segment_is_in_progress(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Recover',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'total_questions' => 0,
            'started_at' => now(),
            'duration_minutes' => 75,
            'ends_at' => now()->addMinutes(75),
        ]);

        AssessmentSegment::create([
            'assessment_id' => $assessment->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'duration_minutes' => 30,
            'order_index' => 0,
            'status' => AssessmentSegment::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
        $essaySegment = AssessmentSegment::create([
            'assessment_id' => $assessment->id,
            'type' => Question::TYPE_ESSAY,
            'duration_minutes' => 45,
            'order_index' => 1,
            'status' => AssessmentSegment::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertRedirect(route('assessment.show', $assessment));

        $essaySegment->refresh();

        $this->assertSame(AssessmentSegment::STATUS_IN_PROGRESS, $essaySegment->status);
        $this->assertNotNull($essaySegment->started_at);
    }

    public function test_segmented_assessment_finalizes_when_all_segments_are_completed_without_submission(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Finalize',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'total_questions' => 1,
            'started_at' => now(),
            'duration_minutes' => 30,
            'ends_at' => now()->addMinutes(30),
        ]);
        $question = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE PG finalize',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $question->id,
            'position' => 1,
        ]);
        AssessmentSegment::create([
            'assessment_id' => $assessment->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'duration_minutes' => 30,
            'order_index' => 0,
            'status' => AssessmentSegment::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertRedirect(route('assessment.result', $assessment));

        $assessment->refresh();

        $this->assertTrue($assessment->isSubmitted());
        $this->assertSame(Assessment::STATUS_GRADED, $assessment->status);
    }

    public function test_she_segment_can_be_completed_before_timer_expires(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Lanjut Manual',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'total_questions' => 2,
            'started_at' => now(),
            'duration_minutes' => 75,
            'ends_at' => now()->addMinutes(75),
        ]);
        $mcQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'PG lanjut manual',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $essayQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_ESSAY,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'Essay setelah PG',
            'is_active' => true,
        ]);
        $mcAnswer = AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $mcQuestion->id,
            'position' => 1,
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $essayQuestion->id,
            'position' => 2,
        ]);
        $mcSegment = AssessmentSegment::create([
            'assessment_id' => $assessment->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'duration_minutes' => 30,
            'order_index' => 0,
            'status' => AssessmentSegment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
        $essaySegment = AssessmentSegment::create([
            'assessment_id' => $assessment->id,
            'type' => Question::TYPE_ESSAY,
            'duration_minutes' => 45,
            'order_index' => 1,
            'status' => AssessmentSegment::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->post(route('assessment.submit', $assessment), [
                'answers' => [
                    $mcAnswer->id => 'a',
                ],
            ])
            ->assertRedirect(route('assessment.show', $assessment));

        $assessment->refresh();
        $mcAnswer->refresh();
        $mcSegment->refresh();
        $essaySegment->refresh();

        $this->assertFalse($assessment->isSubmitted());
        $this->assertSame('a', $mcAnswer->selected_option);
        $this->assertTrue($mcAnswer->is_correct);
        $this->assertSame(AssessmentSegment::STATUS_COMPLETED, $mcSegment->status);
        $this->assertSame(AssessmentSegment::STATUS_IN_PROGRESS, $essaySegment->status);
        $this->assertNotNull($essaySegment->started_at);
    }

    public function test_she_segmented_page_keeps_positive_overall_countdown(): void
    {
        $this->travelTo(now()->startOfMinute());

        $package = QuestionPackage::create([
            'name' => 'Paket SHE Timer',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'assessment_access_expires_at' => now()->addDays(3),
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'total_questions' => 1,
            'started_at' => now(),
            'duration_minutes' => 105,
            'ends_at' => now()->addMinutes(105),
        ]);
        $question = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'SHE',
            'difficulty' => 'basic',
            'text' => 'SHE PG timer',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $question->id,
            'position' => 1,
        ]);
        AssessmentSegment::create([
            'assessment_id' => $assessment->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'duration_minutes' => 30,
            'order_index' => 0,
            'status' => AssessmentSegment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('assessment.show', $assessment))
            ->assertOk()
            ->assertSee('const segmentRemaining = 1800;', false)
            ->assertSee('const overallRemaining = 6300;', false)
            ->assertSee('let manualPromptOpen = false;', false)
            ->assertSee('window.confirm', false);
    }

    public function test_admin_hr_can_create_question_with_custom_points(): void
    {
        $admin = User::factory()->create(['role' => 'admin_hr']);
        $package = QuestionPackage::create([
            'name' => 'Paket HR Nilai',
            'type' => QuestionPackage::TYPE_HR,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'question_package_id' => $package->id,
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'category' => 'HR',
                'difficulty' => 'intermediate',
                'text' => 'Soal HR berbobot',
                'option_a' => 'Benar',
                'option_b' => 'Salah',
                'option_c' => 'Salah',
                'option_d' => 'Salah',
                'correct_option' => 'a',
                'points' => 7.5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.packages.questions', $package->id));

        $this->assertDatabaseHas('questions', [
            'question_package_id' => $package->id,
            'text' => 'Soal HR berbobot',
            'points' => 7.5,
        ]);
    }

    public function test_hr_assessment_uses_question_points_for_final_score(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket HR Weighted',
            'type' => QuestionPackage::TYPE_HR,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'question_package_id' => $package->id,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'total_questions' => 2,
            'started_at' => now(),
        ]);

        $lowPointQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'HR',
            'difficulty' => 'basic',
            'text' => 'Soal bobot kecil',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'points' => 2,
            'is_active' => true,
        ]);
        $highPointQuestion = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'HR',
            'difficulty' => 'advanced',
            'text' => 'Soal bobot besar',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'points' => 8,
            'is_active' => true,
        ]);
        $lowAnswer = AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $lowPointQuestion->id,
            'position' => 1,
        ]);
        $highAnswer = AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $highPointQuestion->id,
            'position' => 2,
        ]);

        app(AssessmentSecurity::class)->finishAssessment($assessment, [
            $lowAnswer->id => 'a',
            $highAnswer->id => 'b',
        ]);

        $assessment->refresh();

        $this->assertSame(Assessment::STATUS_GRADED, $assessment->status);
        $this->assertSame(1, $assessment->correct_answers);
        $this->assertEquals(20.0, (float) $assessment->score);
    }

    public function test_admin_can_invite_user_with_generated_credentials(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        $package = QuestionPackage::create([
            'name' => 'Paket Invite',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'email' => 'candidate@example.com',
                'type' => 'mekanik',
                'question_package_id' => $package->id,
                'access_days' => 5,
                'duration_hours' => 1.5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'candidate@example.com',
            'role' => 'user',
            'question_package_id' => $package->id,
            'assessment_duration_minutes' => 90,
        ]);
    }

    public function test_admin_can_invite_many_users_from_email_list(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        $package = QuestionPackage::create([
            'name' => 'Paket Bulk Invite',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite-many'), [
                'bulk_emails' => "alpha@example.com\nBeta User <beta@example.com>\ngamma@example.com, delta@example.com",
                'bulk_type' => QuestionPackage::TYPE_MEKANIK,
                'bulk_question_package_id' => $package->id,
                'bulk_access_days' => 7,
                'bulk_duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'));

        foreach (['alpha@example.com', 'beta@example.com', 'gamma@example.com', 'delta@example.com'] as $email) {
            $this->assertDatabaseHas('users', [
                'email' => $email,
                'role' => 'user',
                'question_package_id' => $package->id,
                'assessment_duration_minutes' => 120,
            ]);
        }

        $this->assertDatabaseHas('users', [
            'email' => 'beta@example.com',
            'name' => 'Beta User',
        ]);
    }

    public function test_user_can_not_start_assessment_after_access_expires(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'assessment_access_expires_at' => now()->subDay(),
            'assessment_duration_minutes' => 120,
        ]);

        Question::create([
            'category' => 'Mechanic',
            'difficulty' => 'basic',
            'text' => 'Pertanyaan expired',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertSessionHas('status', 'Masa akses assessment untuk akun ini sudah habis. Hubungi admin.');

        $this->assertSame(0, Assessment::count());
    }

    public function test_security_violation_blocks_assessment_until_admin_unblocks_it(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'total_questions' => 1,
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('assessment.security-violation', $assessment), [
                'reason' => 'Peserta meninggalkan tab assessment.',
            ])
            ->assertOk()
            ->assertJson(['blocked' => true]);

        $this->assertTrue($assessment->fresh()->isBlocked());
        $this->assertSame(1, $assessment->fresh()->security_violations);

        $this->actingAs($admin)
            ->post(route('admin.assessments.unblock', $assessment))
            ->assertRedirect();

        $this->assertFalse($assessment->fresh()->isBlocked());

        $this->actingAs($user)
            ->postJson(route('assessment.security-violation', $assessment), [
                'reason' => 'Peserta meninggalkan tab assessment lagi.',
            ])
            ->assertOk()
            ->assertJson(['blocked' => true]);

        $this->assertSame(2, $assessment->fresh()->security_violations);

        $this->actingAs($admin)
            ->post(route('admin.assessments.unblock', $assessment))
            ->assertRedirect();

        $this->actingAs($user)
            ->postJson(route('assessment.security-violation', $assessment), [
                'reason' => 'Pelanggaran ketiga.',
            ])
            ->assertOk()
            ->assertJson(['submitted' => true]);

        $assessment->refresh();

        $this->assertSame(3, $assessment->security_violations);
        $this->assertTrue($assessment->isSubmitted());
        $this->assertNotNull($assessment->auto_submitted_at);
    }

    public function test_user_with_active_assessment_is_redirected_back_when_opening_other_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'total_questions' => 1,
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('assessment.show', $assessment));

        $this->assertTrue($assessment->fresh()->isBlocked());
        $this->assertSame(1, $assessment->fresh()->security_violations);
    }
}
