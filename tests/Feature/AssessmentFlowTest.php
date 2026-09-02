<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentSegment;
use App\Models\InterviewAssessment;
use App\Models\InterviewTemplate;
use App\Models\OperatorAssessmentCategory;
use App\Models\Question;
use App\Models\QuestionPackage;
use App\Models\User;
use App\Services\AssessmentSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_dashboard_counts_participants_who_have_not_started_test(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_MEKANIK]);
        $package = QuestionPackage::create([
            'name' => 'Paket Belum Mengerjakan',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $notStarted = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $submittedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        Assessment::create([
            'user_id' => $submittedUser->id,
            'question_package_id' => $package->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 10,
            'correct_answers' => 8,
            'score' => 80,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum Mengerjakan')
            ->assertViewHas('stats', fn (array $stats): bool => $stats['not_started'] === 1)
            ->assertViewHas('chartData', fn (array $chartData): bool => $chartData['notStarted'] === 1);
    }

    public function test_admin_users_index_shows_current_test_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_MEKANIK]);
        $package = QuestionPackage::create([
            'name' => 'Paket Status Test',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $preTest = OperatorAssessmentCategory::firstOrCreate([
            'name' => 'Pre Test',
        ], [
            'is_active' => true,
        ]);
        $postTest = OperatorAssessmentCategory::firstOrCreate([
            'name' => 'Post Test',
        ], [
            'is_active' => true,
        ]);

        $notStarted = User::factory()->create([
            'name' => 'Peserta Belum Mengerjakan',
            'email' => 'belum.test@example.com',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTest->id,
        ]);
        Assessment::create([
            'user_id' => $notStarted->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $postTest->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 10,
            'correct_answers' => 8,
            'score' => 80,
            'started_at' => now()->subDays(2),
            'submitted_at' => now()->subDays(2),
        ]);

        $submitted = User::factory()->create([
            'email' => 'sudah.test@example.com',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTest->id,
        ]);
        Assessment::create([
            'user_id' => $submitted->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTest->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 10,
            'correct_answers' => 9,
            'score' => 90,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $running = User::factory()->create([
            'email' => 'sedang.jalan@example.com',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTest->id,
        ]);
        Assessment::create([
            'user_id' => $running->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTest->id,
            'status' => Assessment::STATUS_IN_PROGRESS,
            'total_questions' => 10,
            'correct_answers' => 0,
            'score' => 0,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
        ]);

        $blocked = User::factory()->create([
            'email' => 'terblokir.test@example.com',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTest->id,
        ]);
        Assessment::create([
            'user_id' => $blocked->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTest->id,
            'status' => Assessment::STATUS_IN_PROGRESS,
            'total_questions' => 10,
            'correct_answers' => 0,
            'score' => 0,
            'started_at' => now()->subMinutes(10),
            'blocked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Status Test')
            ->assertSee('Belum Mengerjakan')
            ->assertSee('Sudah Test')
            ->assertSee('Sedang Jalan')
            ->assertSee('Terblokir')
            ->assertViewHas('users', function ($users): bool {
                $items = collect($users->items())->keyBy('email');

                return ($items['belum.test@example.com']->current_submitted_assessments_count ?? null) === 0
                    && ($items['belum.test@example.com']->current_assessments_count ?? null) === 0
                    && ($items['sudah.test@example.com']->current_submitted_assessments_count ?? null) === 1
                    && ($items['sedang.jalan@example.com']->current_running_assessments_count ?? null) === 1
                    && ($items['terblokir.test@example.com']->current_blocked_assessments_count ?? null) === 1;
            });

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['test_status' => 'not_started']))
            ->assertOk()
            ->assertSee('belum.test@example.com')
            ->assertDontSee('sudah.test@example.com')
            ->assertDontSee('sedang.jalan@example.com')
            ->assertDontSee('terblokir.test@example.com');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['test_status' => 'submitted']))
            ->assertOk()
            ->assertSee('sudah.test@example.com')
            ->assertDontSee('belum.test@example.com');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['test_status' => 'running']))
            ->assertOk()
            ->assertSee('sedang.jalan@example.com')
            ->assertDontSee('belum.test@example.com');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['test_status' => 'blocked']))
            ->assertOk()
            ->assertSee('terblokir.test@example.com')
            ->assertDontSee('belum.test@example.com');
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

    public function test_multiple_choice_options_are_not_randomized_for_participants(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket Opsi Tetap',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Mechanic',
            'difficulty' => 'basic',
            'text' => 'Soal opsi tetap',
            'option_a' => 'Opsi pertama',
            'option_b' => 'Opsi kedua',
            'option_c' => 'Opsi ketiga',
            'option_d' => 'Opsi keempat',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $html = $this->actingAs($user)
            ->get(route('assessment.show', Assessment::first()))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            strpos($html, 'Opsi pertama') < strpos($html, 'Opsi kedua')
            && strpos($html, 'Opsi kedua') < strpos($html, 'Opsi ketiga')
            && strpos($html, 'Opsi ketiga') < strpos($html, 'Opsi keempat')
        );
        $this->assertStringContainsString('Opsi pertama', $html);
        $this->assertStringContainsString('Opsi kedua', $html);
        $this->assertStringContainsString('Opsi ketiga', $html);
        $this->assertStringContainsString('Opsi keempat', $html);
    }

    public function test_operator_and_mechanic_questions_are_randomized_when_assessment_starts(): void
    {
        foreach ([QuestionPackage::TYPE_OPERATOR, QuestionPackage::TYPE_MEKANIK] as $type) {
            $package = QuestionPackage::create([
                'name' => 'Paket Acak '.strtoupper($type),
                'type' => $type,
                'is_active' => true,
            ]);
            $user = User::factory()->create([
                'role' => User::ROLE_USER,
                'question_package_id' => $package->id,
            ]);
            $createdQuestionIds = [];

            for ($i = 1; $i <= 8; $i++) {
                $createdQuestionIds[] = Question::create([
                    'question_package_id' => $package->id,
                    'type' => Question::TYPE_MULTIPLE_CHOICE,
                    'category' => $type,
                    'difficulty' => 'basic',
                    'text' => 'Soal '.$type.' '.$i,
                    'option_a' => 'Benar',
                    'option_b' => 'Salah',
                    'option_c' => 'Salah',
                    'option_d' => 'Salah',
                    'correct_option' => 'a',
                    'is_active' => true,
                ])->id;
            }

            $this->actingAs($user)
                ->post(route('assessment.start'))
                ->assertRedirect();

            $assessmentQuestionIds = Assessment::where('user_id', $user->id)
                ->firstOrFail()
                ->answers()
                ->orderBy('position')
                ->pluck('question_id')
                ->all();

            $this->assertNotSame($createdQuestionIds, $assessmentQuestionIds);
            $this->assertEqualsCanonicalizing($createdQuestionIds, $assessmentQuestionIds);
        }
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
            ->assertRedirect(route('admin.packages.preview', $package->id));

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

    public function test_admin_can_preview_all_questions_in_package(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('question-images/preview.png', 'image-bytes');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_MEKANIK]);
        $package = QuestionPackage::create([
            'name' => 'Paket Mekanik Preview',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);

        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Engine',
            'difficulty' => 'basic',
            'text' => 'Apa fungsi oli mesin?',
            'image' => 'question-images/preview.png',
            'option_a' => 'Melumasi komponen',
            'option_b' => 'Mengecat body',
            'option_c' => 'Mengisi ban',
            'option_d' => 'Mengganti filter',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_TRUE_FALSE,
            'category' => 'Engine',
            'difficulty' => 'basic',
            'text' => 'Filter udara tersumbat dapat menurunkan performa engine.',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.packages.preview', $package))
            ->assertOk()
            ->assertSee('Preview Soal: Paket Mekanik Preview')
            ->assertSee('Apa fungsi oli mesin?')
            ->assertSee('Filter udara tersumbat dapat menurunkan performa engine.')
            ->assertSee('Benar/Salah')
            ->assertSee('Kunci jawaban:')
            ->assertSee('question-images/preview.png', false);
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

    public function test_completed_attempt_on_previous_package_does_not_block_current_post_test_package(): void
    {
        $preTestPackage = QuestionPackage::create([
            'name' => 'Pre Test Training',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $postTestPackage = QuestionPackage::create([
            'name' => 'Post Test Training',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $postTestPackage->id,
            'max_attempts' => 1,
        ]);

        Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $preTestPackage->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        Question::create([
            'question_package_id' => $postTestPackage->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Post Test',
            'difficulty' => 'basic',
            'text' => 'Soal post test boleh mulai',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Percobaan: 0/1')
            ->assertSee('Mulai Assessment')
            ->assertDontSee('Batas Percobaan Terpakai')
            ->assertDontSee('Percobaan Habis');

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'user_id' => $user->id,
            'question_package_id' => $postTestPackage->id,
            'submitted_at' => null,
        ]);
    }

    public function test_completed_attempt_on_same_package_but_previous_invite_category_does_not_block_current_test(): void
    {
        $package = QuestionPackage::create([
            'name' => 'SOAL TRAINING LOTO',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $preTestCategory = OperatorAssessmentCategory::create([
            'name' => 'Pre Test Training',
            'is_active' => true,
        ]);
        $postTestCategory = OperatorAssessmentCategory::create([
            'name' => 'Post Test Training',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $postTestCategory->id,
            'max_attempts' => 1,
        ]);

        Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTestCategory->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 25,
            'correct_answers' => 11,
            'score' => 44,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Post Test Training',
            'difficulty' => 'basic',
            'text' => 'Soal post test dari paket sama',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('kategori Post Test Training')
            ->assertSee('Belum ada hasil')
            ->assertSee('Pre Test Training')
            ->assertSee('SOAL TRAINING LOTO')
            ->assertSee('Percobaan: 0/1')
            ->assertSee('Mulai Assessment')
            ->assertSee('44.00')
            ->assertDontSee('Batas Percobaan Terpakai')
            ->assertDontSee('Percobaan Habis');

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $postTestCategory->id,
            'submitted_at' => null,
        ]);
    }

    public function test_blocked_open_assessment_from_previous_invite_category_does_not_block_current_test(): void
    {
        $package = QuestionPackage::create([
            'name' => 'SOAL A',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $postTestCategory = OperatorAssessmentCategory::create([
            'name' => 'Post Test A',
            'is_active' => true,
        ]);
        $preTestCategory = OperatorAssessmentCategory::create([
            'name' => 'Pre Test A',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTestCategory->id,
            'max_attempts' => 1,
        ]);

        Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $postTestCategory->id,
            'status' => Assessment::STATUS_IN_PROGRESS,
            'total_questions' => 1,
            'started_at' => now()->subHour(),
            'blocked_at' => now()->subMinutes(30),
            'block_reason' => 'Tab switch post test',
            'security_violations' => 1,
        ]);
        Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Pre Test A',
            'difficulty' => 'basic',
            'text' => 'Soal pre test setelah post blocked',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('kategori Pre Test A')
            ->assertSee('Percobaan: 0/1')
            ->assertSee('Mulai Assessment')
            ->assertDontSee('Terblokir');

        $this->actingAs($user)
            ->post(route('assessment.start'))
            ->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $preTestCategory->id,
            'submitted_at' => null,
            'blocked_at' => null,
        ]);
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

    public function test_hr_admin_can_access_interview_assessments_with_site(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);
        $template = InterviewTemplate::create([
            'name' => 'Template Interview HR',
            'type' => QuestionPackage::TYPE_HR,
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
            'is_active' => true,
        ]);
        InterviewAssessment::create([
            'interview_template_id' => $template->id,
            'candidate_name' => 'Kandidat HR',
            'job_title' => 'HR Officer',
            'location' => 'Site Sangatta',
            'interview_date' => now()->toDateString(),
            'total_score' => 10,
            'average_score' => 4,
            'percentage' => 80,
            'recommendation' => 'DIREKOMENDASIKAN',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.interview-assessments.index'))
            ->assertOk()
            ->assertSee('Kandidat HR')
            ->assertSee('Site Sangatta')
            ->assertSee('Template Interview HR');
    }

    public function test_site_admin_only_sees_interview_assessments_from_their_site(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_HR,
            'site' => 'Site A',
        ]);
        $template = InterviewTemplate::create([
            'name' => 'Template Interview Site HR',
            'type' => QuestionPackage::TYPE_HR,
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
            'is_active' => true,
        ]);
        $category = $template->categories()->create([
            'name' => 'Kompetensi HR',
            'order' => 1,
        ]);
        $aspect = $category->aspects()->create([
            'name' => 'Komunikasi',
            'order' => 1,
        ]);

        InterviewAssessment::create([
            'interview_template_id' => $template->id,
            'candidate_name' => 'Kandidat Site A',
            'job_title' => 'HR Officer',
            'location' => 'Site A',
            'interview_date' => now()->toDateString(),
            'total_score' => 10,
            'average_score' => 5,
            'percentage' => 100,
            'recommendation' => 'DIREKOMENDASIKAN',
            'created_by' => $admin->id,
        ]);
        $blocked = InterviewAssessment::create([
            'interview_template_id' => $template->id,
            'candidate_name' => 'Kandidat Site B',
            'job_title' => 'HR Officer',
            'location' => 'Site B',
            'interview_date' => now()->toDateString(),
            'total_score' => 10,
            'average_score' => 5,
            'percentage' => 100,
            'recommendation' => 'DIREKOMENDASIKAN',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.interview-assessments.index'))
            ->assertOk()
            ->assertSee('Kandidat Site A')
            ->assertDontSee('Kandidat Site B');

        $this->actingAs($admin)
            ->get(route('admin.interview-assessments.show', $blocked))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.interview-assessments.store'), [
                'interview_template_id' => $template->id,
                'candidate_name' => 'Kandidat Baru',
                'job_title' => 'HR Supervisor',
                'location' => 'Site B',
                'interview_date' => now()->toDateString(),
                'scores' => [
                    $aspect->id => ['score' => 5, 'notes' => 'baik'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('interview_assessments', [
            'candidate_name' => 'Kandidat Baru',
            'location' => 'Site A',
            'created_by' => $admin->id,
        ]);
    }

    public function test_interview_templates_are_scoped_to_operator_hr_and_mechanic_roles(): void
    {
        $adminHr = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);
        InterviewTemplate::create([
            'name' => 'Template HR',
            'type' => QuestionPackage::TYPE_HR,
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
            'is_active' => true,
        ]);
        InterviewTemplate::create([
            'name' => 'Template Mekanik',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
            'is_active' => true,
        ]);
        InterviewTemplate::create([
            'name' => 'Template Operator',
            'type' => QuestionPackage::TYPE_OPERATOR,
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
            'is_active' => true,
        ]);
        InterviewTemplate::create([
            'name' => 'Template SHE',
            'type' => QuestionPackage::TYPE_SHE,
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($adminHr)
            ->get(route('admin.interview-templates.index'))
            ->assertOk()
            ->assertSee('Template HR')
            ->assertDontSee('Template Mekanik')
            ->assertDontSee('Template Operator')
            ->assertDontSee('Template SHE');

        $this->actingAs($adminHr)
            ->get(route('admin.interview-templates.create'))
            ->assertOk()
            ->assertSee('value="hr"', false)
            ->assertDontSee('value="mekanik"', false)
            ->assertDontSee('value="operator"', false)
            ->assertDontSee('value="she"', false);
    }

    public function test_she_admin_cannot_access_interview_assessments(): void
    {
        $adminShe = User::factory()->create(['role' => User::ROLE_ADMIN_SHE]);

        $this->actingAs($adminShe)
            ->get(route('admin.interview-assessments.index'))
            ->assertForbidden();
    }

    public function test_interview_assessment_can_be_updated_deleted_and_downloaded_as_pdf(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);
        $template = InterviewTemplate::create([
            'name' => 'Template Update Interview HR',
            'type' => QuestionPackage::TYPE_HR,
            'min_recommended_percentage' => 70,
            'min_considered_percentage' => 50,
            'is_active' => true,
        ]);
        $category = $template->categories()->create([
            'name' => 'Kompetensi HR',
            'order' => 1,
        ]);
        $aspectA = $category->aspects()->create([
            'name' => 'Komunikasi',
            'order' => 1,
        ]);
        $aspectB = $category->aspects()->create([
            'name' => 'Analisa',
            'order' => 2,
        ]);
        $assessment = InterviewAssessment::create([
            'interview_template_id' => $template->id,
            'candidate_name' => 'Nama Lama',
            'job_title' => 'HR Officer',
            'location' => 'Site Lama',
            'interview_date' => now()->toDateString(),
            'total_score' => 4,
            'average_score' => 2,
            'percentage' => 40,
            'recommendation' => 'TIDAK DIREKOMENDASIKAN',
            'created_by' => $admin->id,
        ]);
        $assessment->scores()->create([
            'interview_aspect_id' => $aspectA->id,
            'score' => 2,
            'notes' => 'lama',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.interview-assessments.edit', $assessment))
            ->assertOk()
            ->assertSee('Nama Lama')
            ->assertSee('Site Lama');

        $this->actingAs($admin)
            ->put(route('admin.interview-assessments.update', $assessment), [
                'interview_template_id' => $template->id,
                'candidate_name' => 'Nama Baru',
                'job_title' => 'HR Supervisor',
                'gender' => 'L',
                'department' => 'Human Capital',
                'age' => 31,
                'location' => 'Site Baru',
                'domicile' => 'Balikpapan',
                'expected_salary' => '10000000',
                'interview_date' => now()->toDateString(),
                'hr_conclusion' => 'Layak lanjut',
                'hr_interviewer_name' => 'Penilai HR',
                'signature' => UploadedFile::fake()->image('signature.png', 240, 90),
                'scores' => [
                    $aspectA->id => ['score' => 5, 'notes' => 'bagus'],
                    $aspectB->id => ['score' => 5, 'notes' => 'tajam'],
                ],
            ])
            ->assertRedirect(route('admin.interview-assessments.show', $assessment));

        $this->assertDatabaseHas('interview_assessments', [
            'id' => $assessment->id,
            'candidate_name' => 'Nama Baru',
            'job_title' => 'HR Supervisor',
            'location' => 'Site Baru',
            'total_score' => 10,
            'recommendation' => 'DIREKOMENDASIKAN',
        ]);
        $assessment->refresh();
        $this->assertNotNull($assessment->signature_path);
        Storage::disk('public')->assertExists($assessment->signature_path);
        $this->assertDatabaseHas('interview_scores', [
            'interview_assessment_id' => $assessment->id,
            'interview_aspect_id' => $aspectB->id,
            'score' => 5,
            'notes' => 'tajam',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.interview-assessments.pdf', $assessment))
            ->assertOk()
            ->assertSee('Form Penilaian Interview')
            ->assertSee('Cetak / Save as PDF')
            ->assertSee('Site Baru')
            ->assertSee($assessment->signature_path);

        $signaturePath = $assessment->signature_path;

        $this->actingAs($admin)
            ->delete(route('admin.interview-assessments.destroy', $assessment))
            ->assertRedirect(route('admin.interview-assessments.index'));

        Storage::disk('public')->assertMissing($signaturePath);
        $this->assertDatabaseMissing('interview_assessments', [
            'id' => $assessment->id,
        ]);
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

    public function test_participant_result_does_not_show_answer_details(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket Hasil Peserta',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $question = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Safety',
            'difficulty' => 'basic',
            'text' => 'Soal rahasia detail peserta',
            'option_a' => 'Opsi benar rahasia',
            'option_b' => 'Opsi salah rahasia',
            'option_c' => 'Distraktor C',
            'option_d' => 'Distraktor D',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $question->id,
            'position' => 1,
            'selected_option' => 'a',
            'is_correct' => true,
        ]);

        $this->actingAs($user)
            ->get(route('assessment.result', $assessment))
            ->assertOk()
            ->assertDontSee('Detail Jawaban')
            ->assertDontSee('Soal rahasia detail peserta')
            ->assertDontSee('Opsi benar rahasia');
    }

    public function test_admin_result_can_still_show_answer_details(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_MEKANIK]);
        $package = QuestionPackage::create([
            'name' => 'Paket Hasil Admin',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
        ]);
        $question = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Safety',
            'difficulty' => 'basic',
            'text' => 'Soal detail untuk admin',
            'option_a' => 'Opsi benar admin',
            'option_b' => 'Opsi salah admin',
            'option_c' => 'Distraktor C',
            'option_d' => 'Distraktor D',
            'correct_option' => 'a',
            'is_active' => true,
        ]);
        $assessment = Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
        AssessmentAnswer::create([
            'assessment_id' => $assessment->id,
            'question_id' => $question->id,
            'position' => 1,
            'selected_option' => 'a',
            'is_correct' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('assessment.result', $assessment))
            ->assertOk()
            ->assertSee('Detail Jawaban')
            ->assertSee('Soal detail untuk admin')
            ->assertSee('Opsi benar admin');
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
            ->assertRedirect(route('admin.packages.preview', $package->id));

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
            ->assertRedirect(route('admin.packages.preview', $package->id));

        $this->assertDatabaseHas('questions', [
            'question_package_id' => $package->id,
            'text' => 'Soal HR berbobot',
            'points' => 7.5,
        ]);
    }

    public function test_admin_operator_can_update_question_points(): void
    {
        $admin = User::factory()->create(['role' => 'admin_operation']);
        $package = QuestionPackage::create([
            'name' => 'Paket Operator Nilai',
            'type' => QuestionPackage::TYPE_OPERATOR,
            'is_active' => true,
        ]);
        $question = Question::create([
            'question_package_id' => $package->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => 'Operator',
            'difficulty' => 'basic',
            'text' => 'Soal operator lama',
            'option_a' => 'Benar',
            'option_b' => 'Salah',
            'option_c' => 'Salah',
            'option_d' => 'Salah',
            'correct_option' => 'a',
            'points' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.questions.update', $question), [
                'question_package_id' => $package->id,
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'category' => 'Operator',
                'difficulty' => 'intermediate',
                'text' => 'Soal operator berbobot',
                'option_a' => 'Benar',
                'option_b' => 'Salah',
                'option_c' => 'Salah',
                'option_d' => 'Salah',
                'correct_option' => 'a',
                'points' => 6.5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.packages.preview', $package->id));

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'text' => 'Soal operator berbobot',
            'points' => 6.5,
        ]);
    }

    public function test_admin_operator_can_manage_operator_assessment_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin_operation']);

        $this->actingAs($admin)
            ->post(route('admin.operator-categories.store'), [
                'name' => 'Operator Track Khusus',
                'description' => 'Assessment untuk operator baru',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.operator-categories.index'));

        $this->assertDatabaseHas('operator_assessment_categories', [
            'name' => 'Operator Track Khusus',
            'is_active' => true,
        ]);

        $category = OperatorAssessmentCategory::where('name', 'Operator Track Khusus')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.operator-categories.update', $category), [
                'name' => 'New Hire Operator',
                'description' => 'Assessment operator baru',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.operator-categories.index'));

        $this->assertDatabaseHas('operator_assessment_categories', [
            'id' => $category->id,
            'name' => 'New Hire Operator',
        ]);
    }

    public function test_admin_mechanic_can_manage_invite_assessment_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin_mekanik']);

        $this->actingAs($admin)
            ->post(route('admin.operator-categories.store'), [
                'name' => 'Experienced',
                'description' => 'Assessment untuk mekanik berpengalaman',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.operator-categories.index'));

        $this->assertDatabaseHas('operator_assessment_categories', [
            'name' => 'Experienced',
            'is_active' => true,
        ]);
    }

    public function test_operator_invite_can_track_custom_operator_category(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin_operation']);
        $category = OperatorAssessmentCategory::firstOrCreate([
            'name' => 'New Hire',
        ], [
            'is_active' => true,
        ]);
        $package = QuestionPackage::create([
            'name' => 'Operator Dump Truck',
            'type' => QuestionPackage::TYPE_OPERATOR,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'name' => 'Operator Baru',
                'email' => 'operator.newhire@example.com',
                'type' => QuestionPackage::TYPE_OPERATOR,
                'question_package_id' => $package->id,
                'operator_assessment_category_id' => $category->id,
                'site' => 'Site Sangatta',
                'access_days' => 7,
                'duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'));

        $this->assertDatabaseHas('users', [
            'email' => 'operator.newhire@example.com',
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $category->id,
            'site' => 'Site Sangatta',
        ]);
    }

    public function test_mechanic_invite_can_track_custom_invite_category(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        $category = OperatorAssessmentCategory::create([
            'name' => 'Refreshment',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $package = QuestionPackage::create([
            'name' => 'Mekanik Unit A',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'name' => 'Mekanik Refreshment',
                'email' => 'mechanic.refreshment@example.com',
                'type' => QuestionPackage::TYPE_MEKANIK,
                'question_package_id' => $package->id,
                'operator_assessment_category_id' => $category->id,
                'site' => 'Site Melak',
                'access_days' => 7,
                'duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'));

        $this->assertDatabaseHas('users', [
            'email' => 'mechanic.refreshment@example.com',
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $category->id,
            'site' => 'Site Melak',
        ]);
    }

    public function test_hr_invite_can_track_site_without_invite_category(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);
        $package = QuestionPackage::create([
            'name' => 'HR Recruitment',
            'type' => QuestionPackage::TYPE_HR,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'name' => 'HR Candidate',
                'email' => 'hr.candidate@example.com',
                'type' => QuestionPackage::TYPE_HR,
                'question_package_id' => $package->id,
                'site' => 'Head Office',
                'access_days' => 7,
                'duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'));

        $this->assertDatabaseHas('users', [
            'email' => 'hr.candidate@example.com',
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => null,
            'site' => 'Head Office',
        ]);
    }

    public function test_operator_invite_form_shows_operator_category_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin_operation']);
        OperatorAssessmentCategory::firstOrCreate([
            'name' => 'New Hire',
        ], [
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invite'))
            ->assertOk()
            ->assertSee('Kategori Invite')
            ->assertSee('Site')
            ->assertSee('New Hire');
    }

    public function test_mechanic_invite_form_shows_invite_category_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        OperatorAssessmentCategory::firstOrCreate([
            'name' => 'New Hire',
        ], [
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invite'))
            ->assertOk()
            ->assertSee('Kategori Invite')
            ->assertSee('Site')
            ->assertSee('New Hire');
    }

    public function test_hr_invite_form_shows_site_field(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_HR]);

        $this->actingAs($admin)
            ->get(route('admin.invite'))
            ->assertOk()
            ->assertSee('Site');
    }

    public function test_assessment_report_shows_invite_category_and_site_in_separate_columns(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_MEKANIK]);
        $category = OperatorAssessmentCategory::firstOrCreate([
            'name' => 'Post Test',
        ], [
            'is_active' => true,
        ]);
        $package = QuestionPackage::create([
            'name' => 'Paket Report Kolom',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Peserta Report',
            'email' => 'peserta.report@example.com',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $category->id,
            'site' => 'Site Separah',
        ]);
        Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $package->id,
            'operator_assessment_category_id' => $category->id,
            'site' => 'Site Separah',
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 10,
            'correct_answers' => 8,
            'score' => 80,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.assessments.index'))
            ->assertOk()
            ->assertSee('Kategori Invite')
            ->assertSee('Site')
            ->assertSee('Post Test')
            ->assertSee('Site Separah');

        $csv = $this->actingAs($admin)
            ->get(route('admin.assessments.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('"Kategori Invite",Site', $csv);
        $this->assertStringContainsString('Post Test', $csv);
        $this->assertStringContainsString('Site Separah', $csv);
    }

    public function test_site_admin_only_sees_users_and_assessments_from_their_site(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_MEKANIK,
            'site' => 'Site A',
        ]);
        $package = QuestionPackage::create([
            'name' => 'Paket Site Scope',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $siteAUser = User::factory()->create([
            'name' => 'Peserta Site A',
            'email' => 'site.a@example.com',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'site' => 'Site A',
        ]);
        $siteBUser = User::factory()->create([
            'name' => 'Peserta Site B',
            'email' => 'site.b@example.com',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'site' => 'Site B',
        ]);
        Assessment::create([
            'user_id' => $siteAUser->id,
            'question_package_id' => $package->id,
            'site' => 'Site A',
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 5,
            'correct_answers' => 4,
            'score' => 80,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        $siteBAssessment = Assessment::create([
            'user_id' => $siteBUser->id,
            'question_package_id' => $package->id,
            'site' => 'Site B',
            'status' => Assessment::STATUS_GRADED,
            'total_questions' => 5,
            'correct_answers' => 3,
            'score' => 60,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Peserta Site A')
            ->assertDontSee('Peserta Site B');

        $this->actingAs($admin)
            ->get(route('admin.assessments.index'))
            ->assertOk()
            ->assertSee('Peserta Site A')
            ->assertDontSee('Peserta Site B');

        $this->actingAs($admin)
            ->get(route('assessment.result', $siteBAssessment))
            ->assertForbidden();
    }

    public function test_ho_admin_and_super_admin_can_see_all_sites(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket HO Scope',
            'type' => QuestionPackage::TYPE_OPERATOR,
            'is_active' => true,
        ]);
        foreach (['Site A', 'Site B'] as $site) {
            $user = User::factory()->create([
                'name' => 'Peserta '.$site,
                'role' => User::ROLE_USER,
                'question_package_id' => $package->id,
                'site' => $site,
            ]);
            Assessment::create([
                'user_id' => $user->id,
                'question_package_id' => $package->id,
                'site' => $site,
                'status' => Assessment::STATUS_GRADED,
                'total_questions' => 5,
                'correct_answers' => 4,
                'score' => 80,
                'started_at' => now()->subHour(),
                'submitted_at' => now(),
            ]);
        }

        $hoAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN_OPERATION,
            'site' => 'HO',
        ]);
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'site' => null,
        ]);

        $this->actingAs($hoAdmin)
            ->get(route('admin.assessments.index'))
            ->assertOk()
            ->assertSee('Peserta Site A')
            ->assertSee('Peserta Site B');

        $this->actingAs($superAdmin)
            ->get(route('admin.assessments.index'))
            ->assertOk()
            ->assertSee('Peserta Site A')
            ->assertSee('Peserta Site B');
    }

    public function test_site_admin_invites_participant_to_their_own_site(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_SHE,
            'site' => 'Site SHE',
        ]);
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Site',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'name' => 'Peserta SHE Site',
                'email' => 'peserta.she.site@example.com',
                'type' => QuestionPackage::TYPE_SHE,
                'question_package_id' => $package->id,
                'site' => 'Site Lain',
                'access_days' => 7,
                'duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'));

        $this->assertDatabaseHas('users', [
            'email' => 'peserta.she.site@example.com',
            'question_package_id' => $package->id,
            'site' => 'Site SHE',
        ]);
    }

    public function test_she_review_is_scoped_by_admin_site(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_SHE,
            'site' => 'Site A',
        ]);
        $package = QuestionPackage::create([
            'name' => 'Paket SHE Review Site',
            'type' => QuestionPackage::TYPE_SHE,
            'is_active' => true,
            'has_segments' => true,
        ]);
        $siteAUser = User::factory()->create([
            'name' => 'Review Site A',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'site' => 'Site A',
        ]);
        $siteBUser = User::factory()->create([
            'name' => 'Review Site B',
            'role' => User::ROLE_USER,
            'question_package_id' => $package->id,
            'site' => 'Site B',
        ]);
        Assessment::create([
            'user_id' => $siteAUser->id,
            'question_package_id' => $package->id,
            'site' => 'Site A',
            'status' => Assessment::STATUS_PENDING_REVIEW,
            'total_questions' => 1,
            'correct_answers' => 0,
            'score' => 0,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);
        $siteBAssessment = Assessment::create([
            'user_id' => $siteBUser->id,
            'question_package_id' => $package->id,
            'site' => 'Site B',
            'status' => Assessment::STATUS_PENDING_REVIEW,
            'total_questions' => 1,
            'correct_answers' => 0,
            'score' => 0,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.she-review.index'))
            ->assertOk()
            ->assertSee('Review Site A')
            ->assertDontSee('Review Site B');

        $this->actingAs($admin)
            ->get(route('admin.she-review.show', $siteBAssessment))
            ->assertForbidden();
    }

    public function test_blocked_assessment_is_detected_after_reinvite_changes_user_package_type(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        $oldPackage = QuestionPackage::create([
            'name' => 'Operator Lama',
            'type' => QuestionPackage::TYPE_OPERATOR,
            'is_active' => true,
        ]);
        $newPackage = QuestionPackage::create([
            'name' => 'Mekanik Baru',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'blocked.reinvite@example.com',
            'question_package_id' => $oldPackage->id,
        ]);

        Assessment::create([
            'user_id' => $user->id,
            'question_package_id' => $oldPackage->id,
            'status' => Assessment::STATUS_IN_PROGRESS,
            'total_questions' => 1,
            'started_at' => now(),
            'blocked_at' => now(),
            'block_reason' => 'Tab switch',
            'security_violations' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'name' => 'Blocked Reinvite',
                'email' => 'blocked.reinvite@example.com',
                'type' => QuestionPackage::TYPE_MEKANIK,
                'question_package_id' => $newPackage->id,
                'access_days' => 7,
                'duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'));

        $this->assertDatabaseHas('users', [
            'email' => 'blocked.reinvite@example.com',
            'question_package_id' => $newPackage->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['blocked_assessments'] === 1);
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

    public function test_operator_assessment_uses_question_points_for_final_score(): void
    {
        $package = QuestionPackage::create([
            'name' => 'Paket Operator Weighted',
            'type' => QuestionPackage::TYPE_OPERATOR,
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
            'category' => 'Operator',
            'difficulty' => 'basic',
            'text' => 'Soal operator bobot kecil',
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
            'category' => 'Operator',
            'difficulty' => 'advanced',
            'text' => 'Soal operator bobot besar',
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
                'bulk_site' => 'Site Bulk',
                'bulk_access_days' => 7,
                'bulk_duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'));

        foreach (['alpha@example.com', 'beta@example.com', 'gamma@example.com', 'delta@example.com'] as $email) {
            $this->assertDatabaseHas('users', [
                'email' => $email,
                'role' => 'user',
                'question_package_id' => $package->id,
                'site' => 'Site Bulk',
                'assessment_duration_minutes' => 120,
            ]);
        }

        $this->assertDatabaseHas('users', [
            'email' => 'beta@example.com',
            'name' => 'Beta User',
        ]);
    }

    public function test_admin_can_resend_invite_to_existing_participant_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        $package = QuestionPackage::create([
            'name' => 'Paket Reinvite',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'repeat@example.com',
            'role' => User::ROLE_USER,
            'password' => 'OLDPASS',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'name' => 'Nama Baru',
                'email' => 'repeat@example.com',
                'type' => QuestionPackage::TYPE_MEKANIK,
                'question_package_id' => $package->id,
                'access_days' => 5,
                'duration_hours' => 1,
            ])
            ->assertRedirect(route('admin.invite'));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame($package->id, $user->question_package_id);
        $this->assertSame(60, $user->assessment_duration_minutes);
        $this->assertFalse(Hash::check('OLDPASS', $user->password));
        $this->assertSame(1, User::where('email', 'repeat@example.com')->count());
    }

    public function test_bulk_invite_sends_duplicate_existing_emails_without_duplicate_error(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin_mekanik']);
        $package = QuestionPackage::create([
            'name' => 'Paket Reinvite Many',
            'type' => QuestionPackage::TYPE_MEKANIK,
            'is_active' => true,
        ]);
        User::factory()->create([
            'email' => 'same@example.com',
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite-many'), [
                'bulk_emails' => "same@example.com\nsame@example.com",
                'bulk_type' => QuestionPackage::TYPE_MEKANIK,
                'bulk_question_package_id' => $package->id,
                'bulk_access_days' => 7,
                'bulk_duration_hours' => 2,
            ])
            ->assertRedirect(route('admin.invite'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, User::where('email', 'same@example.com')->count());
        $this->assertDatabaseHas('users', [
            'email' => 'same@example.com',
            'question_package_id' => $package->id,
            'assessment_duration_minutes' => 120,
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
