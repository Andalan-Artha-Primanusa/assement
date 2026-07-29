<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $questions = Question::query()
            ->with('questionPackage')
            ->when(! $adminUser->isSuperAdmin(), function ($query) use ($visibleTypes): void {
                $query->whereHas('questionPackage', function ($q) use ($visibleTypes): void {
                    $q->whereIn('type', $visibleTypes);
                });
            })
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
        $packages = QuestionPackage::whereIn('type', $visibleTypes)->orderBy('name')->get();

        return view('admin.questions.index', compact('questions', 'categories', 'packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $defaultType = $request->user()->visiblePackageTypes()[0] ?? QuestionPackage::TYPE_MEKANIK;

        $question = new Question([
            'question_package_id' => $request->integer('question_package_id'),
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'category' => QuestionPackage::typeLabel($defaultType),
            'difficulty' => 'basic',
            'correct_option' => 'a',
            'is_active' => true,
        ]);

        $packages = QuestionPackage::whereIn('type', $request->user()->visiblePackageTypes())->orderBy('name')->get();

        return view('admin.questions.create', compact('question', 'packages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $question = Question::create($data);

        ActivityLog::log('question_create', 'Membuat soal #'.$question->id, Question::class, $question->id);

        if ($data['question_package_id']) {
            return redirect()->route('admin.packages.questions', $data['question_package_id'])->with('status', 'Soal berhasil ditambahkan.');
        }

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
        $this->authorizeQuestionPackage(request(), $question);

        $packages = QuestionPackage::whereIn('type', request()->user()->visiblePackageTypes())->orderBy('name')->get();

        return view('admin.questions.edit', compact('question', 'packages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeQuestionPackage($request, $question);

        $data = $this->validated($request, $question);
        $question->update($data);

        ActivityLog::log('question_update', 'Mengupdate soal #'.$question->id, Question::class, $question->id);

        if ($data['question_package_id']) {
            return redirect()->route('admin.packages.questions', $data['question_package_id'])->with('status', 'Soal berhasil diperbarui.');
        }

        return redirect()->route('admin.questions.index')->with('status', 'Soal berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): RedirectResponse
    {
        $this->authorizeQuestionPackage(request(), $question);

        if ($question->answers()->exists()) {
            $question->update(['is_active' => false]);

            ActivityLog::log('question_deactivate', 'Menonaktifkan soal #'.$question->id, Question::class, $question->id);

            return back()->with('status', 'Soal sudah pernah dipakai, jadi dinonaktifkan agar riwayat tetap utuh.');
        }

        ActivityLog::log('question_delete', 'Menghapus soal #'.$question->id, Question::class, $question->id);

        $question->delete();

        return back()->with('status', 'Soal berhasil dihapus.');
    }

    private function validated(Request $request, ?Question $question = null): array
    {
        $data = $request->validate([
            'question_package_id' => [
                'nullable',
                'integer',
                Rule::exists('question_packages', 'id')->where(function ($query) use ($request): void {
                    $query->whereIn('type', $request->user()->visiblePackageTypes());
                }),
            ],
            'type' => ['required', 'in:multiple_choice,essay,upload'],
            'category' => ['required', 'string', 'max:100'],
            'difficulty' => ['required', 'in:basic,intermediate,advanced'],
            'text' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_option' => ['nullable', 'in:a,b,c,d'],
            'is_active' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $data['question_package_id'] = $data['question_package_id'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($question && $question->image) {
                Storage::disk('public')->delete($question->image);
            }
            $data['image'] = $request->file('image')->store('question-images', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($question && $question->image) {
                Storage::disk('public')->delete($question->image);
            }
            $data['image'] = null;
        } else {
            $data['image'] = $question->image ?? null;
        }

        unset($data['remove_image']);

        if ($data['type'] === Question::TYPE_MULTIPLE_CHOICE) {
            $data['option_a'] = $data['option_a'] ?? '';
            $data['option_b'] = $data['option_b'] ?? '';
            $data['option_c'] = $data['option_c'] ?? '';
            $data['option_d'] = $data['option_d'] ?? '';
            $data['correct_option'] = $data['correct_option'] ?? 'a';
        } else {
            $data['option_a'] = null;
            $data['option_b'] = null;
            $data['option_c'] = null;
            $data['option_d'] = null;
            $data['correct_option'] = null;
        }

        return $data;
    }

    private function authorizeQuestionPackage(Request $request, Question $question): void
    {
        $packageType = $question->questionPackage?->type;

        abort_unless($packageType && $request->user()->canManageType($packageType), 403);
    }
}
