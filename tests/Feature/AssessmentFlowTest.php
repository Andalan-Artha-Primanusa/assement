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
        $this->assertSame((int) config('assessment.question_limit'), AssessmentAnswer::count());

        $this->actingAs($user)
            ->get(route('assessment.show', Assessment::first()))
            ->assertOk()
            ->assertSee('Assessment Mechanic');
    }

    public function test_user_assessment_uses_assigned_question_package(): void
    {
        config(['assessment.question_limit' => 4]);

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
        $this->assertSame(4, $assessment->answers()->count());

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
            ->assertSee('const overallRemaining = 6300;', false);
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
