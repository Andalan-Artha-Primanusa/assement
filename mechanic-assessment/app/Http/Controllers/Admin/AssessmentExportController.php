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
        abort_unless(auth()->user()->is_admin, 403);

        $assessment->load('user', 'questionPackage', 'answers.question');

        $pdf = Pdf::loadView('admin.assessments.pdf', compact('assessment'));

        return $pdf->download('assessment-'.$assessment->id.'-'.now()->format('Y-m-d').'.pdf');
    }
}
