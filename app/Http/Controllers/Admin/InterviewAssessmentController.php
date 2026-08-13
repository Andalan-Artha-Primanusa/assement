<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterviewAssessment;
use App\Models\InterviewTemplate;
use App\Models\InterviewScore;
use App\Models\User;
use Illuminate\Http\Request;

class InterviewAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = InterviewAssessment::with('template', 'creator')->latest();
        
        // RBAC filtering
        if ($user->isAdminMekanik()) {
            $query->whereHas('template', function($q) {
                $q->where('type', 'mekanik');
            });
        } elseif ($user->isAdminOperation()) {
            $query->whereHas('template', function($q) {
                $q->where('type', 'operator');
            });
        }
        
        $assessments = $query->paginate(15);
        
        return view('admin.interview-assessments.index', compact('assessments'));
    }

    public function create()
    {
        $user = auth()->user();
        
        $query = InterviewTemplate::where('is_active', true)->with(['categories.aspects']);
        
        // RBAC filtering
        if ($user->isAdminMekanik()) {
            $query->where('type', 'mekanik');
        } elseif ($user->isAdminOperation()) {
            $query->where('type', 'operator');
        }
        
        $templates = $query->get();
        
        return view('admin.interview-assessments.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'interview_template_id' => 'required|exists:interview_templates,id',
            'candidate_name' => 'required|string|max:255',
            'scores' => 'required|array',
            'scores.*.score' => 'nullable|integer|min:1|max:5',
            'scores.*.notes' => 'nullable|string|max:500',
        ]);

        $template = InterviewTemplate::findOrFail($request->interview_template_id);
        
        // Calculate scores
        $totalScore = 0;
        $maxPossibleScore = 0;
        $countAspects = 0;
        
        foreach ($template->categories as $category) {
            foreach ($category->aspects as $aspect) {
                $maxPossibleScore += 5; // Assuming max score is 5 per aspect
                $countAspects++;
            }
        }
        
        foreach ($request->scores as $aspectId => $scoreData) {
            if (!empty($scoreData['score'])) {
                $totalScore += $scoreData['score'];
            }
        }
        
        $averageScore = $countAspects > 0 ? $totalScore / $countAspects : 0;
        $percentage = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;
        
        $recommendation = 'TIDAK DIREKOMENDASIKAN';
        if ($percentage >= $template->min_recommended_percentage) {
            $recommendation = 'DIREKOMENDASIKAN';
        } elseif ($percentage >= $template->min_considered_percentage) {
            $recommendation = 'DIPERTIMBANGKAN';
        }

        $assessment = InterviewAssessment::create([
            'interview_template_id' => $template->id,
            'candidate_name' => $request->candidate_name,
            'job_title' => $request->job_title,
            'gender' => $request->gender,
            'department' => $request->department,
            'age' => $request->age,
            'location' => $request->location,
            'domicile' => $request->domicile,
            'join_date' => $request->join_date,
            'expected_salary' => $request->expected_salary,
            'interview_date' => $request->interview_date,
            'hr_conclusion' => $request->hr_conclusion,
            'hr_interviewer_name' => $request->hr_interviewer_name,
            'user_interviewer_name' => $request->user_interviewer_name,
            'total_score' => $totalScore,
            'average_score' => $averageScore,
            'percentage' => $percentage,
            'recommendation' => $recommendation,
            'created_by' => auth()->id(),
        ]);

        foreach ($request->scores as $aspectId => $scoreData) {
            InterviewScore::create([
                'interview_assessment_id' => $assessment->id,
                'interview_aspect_id' => $aspectId,
                'score' => $scoreData['score'] ?? null,
                'notes' => $scoreData['notes'] ?? null,
            ]);
        }

        return redirect()->route('admin.interview-assessments.show', $assessment)
            ->with('success', 'Form penilaian interview berhasil disimpan.');
    }

    public function show(InterviewAssessment $interview_assessment)
    {
        $interview_assessment->load(['template.categories.aspects', 'scores']);
        return view('admin.interview-assessments.show', compact('interview_assessment'));
    }
}
