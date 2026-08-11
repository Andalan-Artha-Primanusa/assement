<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Question;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            ->get();

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
            'points' => 1,
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
            return redirect()->route('admin.packages.preview', $data['question_package_id'])->with('status', 'Soal berhasil ditambahkan. Silakan preview semua soal di paket ini.');
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
            return redirect()->route('admin.packages.preview', $data['question_package_id'])->with('status', 'Soal berhasil diperbarui. Silakan preview semua soal di paket ini.');
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
            'type' => ['required', Rule::in([
                Question::TYPE_MULTIPLE_CHOICE,
                Question::TYPE_TRUE_FALSE,
                Question::TYPE_ESSAY,
                Question::TYPE_UPLOAD,
            ])],
            'category' => ['required', 'string', 'max:100'],
            'difficulty' => ['required', 'in:basic,intermediate,advanced'],
            'text' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_option' => ['nullable', 'in:a,b,c,d'],
            'points' => ['nullable', 'numeric', 'min:0.01', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $data['question_package_id'] = $data['question_package_id'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        $package = $this->packageForData($request, $data);
        $this->ensureTypeAllowedForPackage($request, $data);
        $data['points'] = QuestionPackage::usesQuestionPoints($package?->type)
            ? (float) ($data['points'] ?? 1)
            : 1;

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

        if ($data['type'] === Question::TYPE_TRUE_FALSE) {
            $data['option_a'] = 'Benar';
            $data['option_b'] = 'Salah';
            $data['option_c'] = null;
            $data['option_d'] = null;
            $data['correct_option'] = in_array($data['correct_option'] ?? null, ['a', 'b'], true)
                ? $data['correct_option']
                : 'a';
        } elseif ($data['type'] === Question::TYPE_MULTIPLE_CHOICE) {
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

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function ensureTypeAllowedForPackage(Request $request, array $data): void
    {
        $package = $this->packageForData($request, $data);
        $type = $data['type'] ?? null;

        if ($type === Question::TYPE_TRUE_FALSE && $package?->type === QuestionPackage::TYPE_SHE) {
            throw ValidationException::withMessages([
                'type' => 'Benar/Salah dipakai untuk paket Mekanik, Operator, atau HR. Paket SHE tetap memakai PG, Essay, dan Portfolio.',
            ]);
        }

        if (in_array($type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_TRUE_FALSE], true)) {
            return;
        }

        if ($package?->type === QuestionPackage::TYPE_SHE) {
            return;
        }

        throw ValidationException::withMessages([
            'type' => 'Essay dan Upload File khusus untuk paket SHE. Paket Mekanik, Operator, dan HR hanya menggunakan PG atau Benar/Salah.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function packageForData(Request $request, array $data): ?QuestionPackage
    {
        return ! empty($data['question_package_id'])
            ? QuestionPackage::whereIn('type', $request->user()->visiblePackageTypes())
                ->find($data['question_package_id'])
            : null;
    }

    private function authorizeQuestionPackage(Request $request, Question $question): void
    {
        $packageType = $question->questionPackage?->type;

        abort_unless($packageType && $request->user()->canManageType($packageType), 403);
    }
}
