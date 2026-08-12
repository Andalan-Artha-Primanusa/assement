<?php

namespace App\Http\Middleware;

use App\Services\AssessmentSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNoActiveAssessmentElsewhere
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin() || $request->routeIs('assessment.*')) {
            return $next($request);
        }

        $packageId = $user->question_package_id;
        $inviteCategoryId = $user->operator_assessment_category_id;
        $assessment = $user->assessments()
            ->whereNull('submitted_at')
            ->when($packageId, fn ($query) => $query->where('question_package_id', $packageId))
            ->when(
                $inviteCategoryId,
                fn ($query) => $query->where('operator_assessment_category_id', $inviteCategoryId),
                fn ($query) => $query->whereNull('operator_assessment_category_id')
            )
            ->latest()
            ->first();

        if (! $assessment) {
            return $next($request);
        }

        $currentSegment = $assessment->segments()
            ->where('status', 'in_progress')
            ->first();

        if ($currentSegment?->type === 'upload') {
            return $next($request);
        }

        if ($assessment->isExpired()) {
            app(AssessmentSecurity::class)->finishAssessment($assessment, [], true);

            return redirect()->route('assessment.result', $assessment);
        }

        if ($assessment->isBlocked()) {
            return redirect()->route('assessment.show', $assessment);
        }

        $status = app(AssessmentSecurity::class)->recordViolation(
            $assessment,
            'Peserta membuka halaman lain saat assessment berlangsung.'
        );

        if ($status['submitted']) {
            return redirect()->route('assessment.result', $assessment);
        }

        return redirect()->route('assessment.show', $assessment);
    }
}
