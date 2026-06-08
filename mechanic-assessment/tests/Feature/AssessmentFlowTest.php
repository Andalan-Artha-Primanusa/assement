<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Question;
use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AssessmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_cms_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('CMS Admin');
    }

    public function test_non_admin_can_not_open_cms_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.questions.index'))
            ->assertForbidden();
    }

    public function test_user_can_start_random_assessment_from_active_questions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

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
            'is_admin' => false,
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

    public function test_admin_can_invite_user_with_generated_credentials(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $package = QuestionPackage::create([
            'name' => 'Paket Invite',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.invite'), [
                'email' => 'candidate@example.com',
                'question_package_id' => $package->id,
                'access_days' => 5,
                'duration_hours' => 1.5,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'candidate@example.com',
            'is_admin' => false,
            'question_package_id' => $package->id,
            'assessment_duration_minutes' => 90,
        ]);
    }

    public function test_user_can_not_start_assessment_after_access_expires(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
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
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);
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
        $user = User::factory()->create(['is_admin' => false]);
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
