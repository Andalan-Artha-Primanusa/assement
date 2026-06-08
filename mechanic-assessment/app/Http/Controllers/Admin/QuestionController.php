<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $questions = Question::query()
            ->with('questionPackage')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where('text', 'like', '%'.$request->string('search')->toString().'%');
            })
            ->when($request->filled('package'), function ($query) use ($request): void {
                $query->where('question_package_id', $request->integer('package'));
            })
            ->when($request->filled('category'), function ($query) use ($request): void {
                $query->where('category', $request->string('category')->toString());
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Question::query()->select('category')->distinct()->orderBy('category')->pluck('category');
        $packages = QuestionPackage::orderBy('name')->get();

        return view('admin.questions.index', compact('questions', 'categories', 'packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $question = new Question([
            'category' => 'Mechanic',
            'difficulty' => 'basic',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $packages = QuestionPackage::orderBy('name')->get();

        return view('admin.questions.create', compact('question', 'packages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $question = Question::create($this->validated($request));

        ActivityLog::log('question_create', 'Membuat soal #'.$question->id, Question::class, $question->id);

        return redirect()->route('admin.questions.index')->with('status', 'Soal berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question): RedirectResponse
    {
        return redirect()->route('admin.questions.edit', $question);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question): View
    {
        $packages = QuestionPackage::orderBy('name')->get();

        return view('admin.questions.edit', compact('question', 'packages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        $question->update($this->validated($request));

        ActivityLog::log('question_update', 'Mengupdate soal #'.$question->id, Question::class, $question->id);

        return redirect()->route('admin.questions.index')->with('status', 'Soal berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): RedirectResponse
    {
        if ($question->answers()->exists()) {
            $question->update(['is_active' => false]);

            ActivityLog::log('question_deactivate', 'Menonaktifkan soal #'.$question->id, Question::class, $question->id);

            return back()->with('status', 'Soal sudah pernah dipakai, jadi dinonaktifkan agar riwayat tetap utuh.');
        }

        ActivityLog::log('question_delete', 'Menghapus soal #'.$question->id, Question::class, $question->id);

        $question->delete();

        return back()->with('status', 'Soal berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question_package_id' => ['nullable', 'integer', 'exists:question_packages,id'],
            'category' => ['required', 'string', 'max:100'],
            'difficulty' => ['required', 'in:basic,intermediate,advanced'],
            'text' => ['required', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'correct_option' => ['required', 'in:a,b,c,d'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['question_package_id'] = $data['question_package_id'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
