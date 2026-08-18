<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InterviewAssessment;
use App\Models\InterviewTemplate;
use App\Models\InterviewScore;
use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InterviewAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $visibleTypes = $this->visibleInterviewTypes($user);

        abort_if($visibleTypes === [], 403);
        
        $query = InterviewAssessment::with('template', 'creator')->latest();
        
        $query->whereHas('template', fn ($q) => $q->whereIn('type', $visibleTypes));
        
        $assessments = $query->paginate(15);
        
        return view('admin.interview-assessments.index', compact('assessments'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $visibleTypes = $this->visibleInterviewTypes($user);

        abort_if($visibleTypes === [], 403);
        
        $query = InterviewTemplate::where('is_active', true)->with(['categories.aspects']);
        
        $query->whereIn('type', $visibleTypes);
        
        $templates = $query->get();

        if ($request->filled('template_id')) {
            abort_unless($templates->contains('id', $request->integer('template_id')), 403);
        }
        
        return view('admin.interview-assessments.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $template = InterviewTemplate::with('categories.aspects')->findOrFail($data['interview_template_id']);
        $calculated = $this->calculateResult($template, $data['scores']);

        $assessment = InterviewAssessment::create([
            'interview_template_id' => $template->id,
            ...$this->assessmentAttributes($data),
            ...$calculated,
            'signature_path' => $this->storeSignature($request),
            'created_by' => auth()->id(),
        ]);

        foreach ($data['scores'] as $aspectId => $scoreData) {
            InterviewScore::create([
                'interview_assessment_id' => $assessment->id,
                'interview_aspect_id' => $aspectId,
                'score' => $scoreData['score'] ?? null,
                'notes' => $scoreData['notes'] ?? null,
            ]);
        }

        ActivityLog::log('interview_assessment_create', 'Membuat penilaian interview '.$assessment->candidate_name, InterviewAssessment::class, $assessment->id);

        return redirect()->route('admin.interview-assessments.show', $assessment)
            ->with('success', 'Form penilaian interview berhasil disimpan.');
    }

    public function edit(InterviewAssessment $interview_assessment)
    {
        $interview_assessment->load(['template.categories.aspects', 'scores']);
        $this->authorizeInterviewType($interview_assessment->template->type);

        $templates = InterviewTemplate::where('is_active', true)
            ->whereIn('type', $this->visibleInterviewTypes(auth()->user()))
            ->with(['categories.aspects'])
            ->get();

        return view('admin.interview-assessments.edit', compact('interview_assessment', 'templates'));
    }

    public function update(Request $request, InterviewAssessment $interview_assessment): RedirectResponse
    {
        $interview_assessment->load('template');
        $this->authorizeInterviewType($interview_assessment->template->type);

        $data = $this->validated($request);
        $template = InterviewTemplate::with('categories.aspects')->findOrFail($data['interview_template_id']);
        $this->authorizeInterviewType($template->type);
        $calculated = $this->calculateResult($template, $data['scores']);

        $attributes = [
            'interview_template_id' => $template->id,
            ...$this->assessmentAttributes($data),
            ...$calculated,
        ];

        if ($request->boolean('remove_signature')) {
            $this->deleteSignature($interview_assessment);
            $attributes['signature_path'] = null;
        }

        if ($request->hasFile('signature')) {
            $this->deleteSignature($interview_assessment);
            $attributes['signature_path'] = $this->storeSignature($request);
        }

        $interview_assessment->update($attributes);

        $interview_assessment->scores()->delete();
        foreach ($data['scores'] as $aspectId => $scoreData) {
            InterviewScore::create([
                'interview_assessment_id' => $interview_assessment->id,
                'interview_aspect_id' => $aspectId,
                'score' => $scoreData['score'] ?? null,
                'notes' => $scoreData['notes'] ?? null,
            ]);
        }

        ActivityLog::log('interview_assessment_update', 'Mengupdate penilaian interview '.$interview_assessment->candidate_name, InterviewAssessment::class, $interview_assessment->id);

        return redirect()->route('admin.interview-assessments.show', $interview_assessment)
            ->with('success', 'Form penilaian interview berhasil diperbarui.');
    }

    public function show(InterviewAssessment $interview_assessment)
    {
        $interview_assessment->load(['template.categories.aspects', 'scores']);
        $this->authorizeInterviewType($interview_assessment->template->type);

        return view('admin.interview-assessments.show', compact('interview_assessment'));
    }

    public function pdf(InterviewAssessment $interview_assessment): View
    {
        $interview_assessment->load(['template.categories.aspects', 'scores']);
        $this->authorizeInterviewType($interview_assessment->template->type);

        return view('admin.interview-assessments.pdf', compact('interview_assessment'));
    }

    public function destroy(InterviewAssessment $interview_assessment): RedirectResponse
    {
        $interview_assessment->load('template');
        $this->authorizeInterviewType($interview_assessment->template->type);

        ActivityLog::log('interview_assessment_delete', 'Menghapus penilaian interview '.$interview_assessment->candidate_name, InterviewAssessment::class, $interview_assessment->id);
        $this->deleteSignature($interview_assessment);
        $interview_assessment->delete();

        return redirect()->route('admin.interview-assessments.index')
            ->with('success', 'Penilaian interview berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $visibleTypes = $this->visibleInterviewTypes($user);

        abort_if($visibleTypes === [], 403);
        
        $query = InterviewAssessment::with('template', 'creator')->latest();
        
        $query->whereHas('template', fn ($q) => $q->whereIn('type', $visibleTypes));
        
        $assessments = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=data_penilaian_interview.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'ID', 'Template', 'Nama Kandidat', 'Jabatan', 'Jenis Kelamin', 'Departemen', 
            'Usia', 'Lokasi/Site', 'Domisili', 'Tanggal Join', 'Ekspektasi Gaji', 
            'Tanggal Interview', 'Total Nilai', 'Nilai Rata-rata', 'Persentase (%)', 
            'Rekomendasi', 'Nama Penilai', 'Tanggal Dibuat'
        ];

        $callback = function() use($assessments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($assessments as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->template->name ?? '-',
                    $item->candidate_name,
                    $item->job_title,
                    $item->gender,
                    $item->department,
                    $item->age,
                    $item->location,
                    $item->domicile,
                    $item->join_date ? \Carbon\Carbon::parse($item->join_date)->format('d M Y') : '-',
                    $item->expected_salary,
                    $item->interview_date ? \Carbon\Carbon::parse($item->interview_date)->format('d M Y') : '-',
                    $item->total_score,
                    $item->average_score,
                    $item->percentage,
                    $item->recommendation,
                    $item->hr_interviewer_name,
                    $item->created_at->format('d M Y H:i:s')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * @return array<int, string>
     */
    private function visibleInterviewTypes(User $user): array
    {
        return array_values(array_intersect($user->visiblePackageTypes(), [
            QuestionPackage::TYPE_MEKANIK,
            QuestionPackage::TYPE_OPERATOR,
            QuestionPackage::TYPE_HR,
        ]));
    }

    private function authorizeInterviewType(string $type): void
    {
        abort_unless(in_array($type, $this->visibleInterviewTypes(auth()->user()), true), 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'interview_template_id' => [
                'required',
                Rule::exists('interview_templates', 'id')->where(fn ($query) => $query->whereIn('type', $this->visibleInterviewTypes(auth()->user()))),
            ],
            'candidate_name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:L,P'],
            'department' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'domicile' => ['nullable', 'string', 'max:255'],
            'join_date' => ['nullable', 'date'],
            'expected_salary' => ['nullable', 'string', 'max:255'],
            'interview_date' => ['nullable', 'date'],
            'hr_conclusion' => ['nullable', 'string'],
            'hr_interviewer_name' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'image', 'max:2048'],
            'remove_signature' => ['nullable', 'boolean'],
            'scores' => ['required', 'array'],
            'scores.*.score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'scores.*.notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function assessmentAttributes(array $data): array
    {
        return [
            'candidate_name' => $data['candidate_name'],
            'job_title' => $data['job_title'] ?? null,
            'gender' => $data['gender'] ?? null,
            'department' => $data['department'] ?? null,
            'age' => $data['age'] ?? null,
            'location' => $data['location'] ?? null,
            'domicile' => $data['domicile'] ?? null,
            'join_date' => $data['join_date'] ?? null,
            'expected_salary' => $data['expected_salary'] ?? null,
            'interview_date' => $data['interview_date'] ?? null,
            'hr_conclusion' => $data['hr_conclusion'] ?? null,
            'hr_interviewer_name' => $data['hr_interviewer_name'] ?? null,
            'user_interviewer_name' => null,
        ];
    }

    private function storeSignature(Request $request): ?string
    {
        if (! $request->hasFile('signature')) {
            return null;
        }

        return $request->file('signature')->store('interview-signatures', 'public');
    }

    private function deleteSignature(InterviewAssessment $assessment): void
    {
        if ($assessment->signature_path) {
            Storage::disk('public')->delete($assessment->signature_path);
        }
    }

    /**
     * @param  array<int|string, array{score?: int|string|null, notes?: string|null}>  $scores
     * @return array{total_score: int, average_score: float, percentage: float, recommendation: string}
     */
    private function calculateResult(InterviewTemplate $template, array $scores): array
    {
        $countAspects = $template->categories->sum(fn ($category) => $category->aspects->count());
        $maxPossibleScore = $countAspects * 5;
        $totalScore = collect($scores)->sum(fn ($scoreData) => (int) ($scoreData['score'] ?? 0));
        $averageScore = $countAspects > 0 ? $totalScore / $countAspects : 0;
        $percentage = $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0;

        $recommendation = 'TIDAK DIREKOMENDASIKAN';
        if ($percentage >= $template->min_recommended_percentage) {
            $recommendation = 'DIREKOMENDASIKAN';
        } elseif ($percentage >= $template->min_considered_percentage) {
            $recommendation = 'DIPERTIMBANGKAN';
        }

        return [
            'total_score' => $totalScore,
            'average_score' => round($averageScore, 2),
            'percentage' => round($percentage, 2),
            'recommendation' => $recommendation,
        ];
    }
}
