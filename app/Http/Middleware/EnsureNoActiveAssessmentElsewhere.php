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

        $assessment = $user->assessments()
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if (! $assessment) {
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
