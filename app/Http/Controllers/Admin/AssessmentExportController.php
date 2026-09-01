<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AssessmentExportController extends Controller
{
    public function pdf(Assessment $assessment): Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $assessment->load('user', 'questionPackage', 'operatorAssessmentCategory', 'answers.question');
        abort_unless(
            auth()->user()->canViewAllSites()
                || $assessment->site === auth()->user()->normalizedSite()
                || ($assessment->site === null && $assessment->user?->normalizedSite() === auth()->user()->normalizedSite()),
            403
        );

        $pdf = Pdf::loadView('admin.assessments.pdf', compact('assessment'));

        return $pdf->download('assessment-'.$assessment->id.'-'.now()->format('Y-m-d').'.pdf');
    }
}
