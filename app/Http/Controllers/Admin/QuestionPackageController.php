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
use Illuminate\View\View;

class QuestionPackageController extends Controller
{
    public function index(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $selectedType = $request->string('type')->toString();
        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, QuestionPackage::TYPES, true)) {
            $visibleTypes = [$selectedType];
        }

        $packages = QuestionPackage::withCount(['questions', 'users'])
            ->with('creator')
            ->whereIn('type', $visibleTypes)
            ->latest()
            ->paginate(12);

        return view('admin.packages.index', compact('packages', 'selectedType'));
    }

    public function create(): View
    {
        $adminUser = request()->user();
        $defaultType = match (true) {
            $adminUser->isAdminMekanik() => QuestionPackage::TYPE_MEKANIK,
            $adminUser->isAdminShe() => QuestionPackage::TYPE_SHE,
            $adminUser->isAdminHr() => QuestionPackage::TYPE_HR,
            default => QuestionPackage::TYPE_OPERATOR,
        };

        $package = new QuestionPackage(['is_active' => true, 'type' => $defaultType]);

        return view('admin.packages.create', compact('package'));
    }

    public function store(Request $request): RedirectResponse
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($visibleTypes)],
            'level' => ['nullable', 'string', Rule::in(array_keys(QuestionPackage::levelOptions()))],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_certificate' => ['nullable', 'boolean'],
            'has_segments' => ['nullable', 'boolean'],
            'min_score_pertimbangan' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_score_lolos' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_certificate'] = $request->boolean('is_certificate');
        $data['has_segments'] = $data['type'] === QuestionPackage::TYPE_SHE || $request->boolean('has_segments');
        $data['level'] = $data['type'] === QuestionPackage::TYPE_OPERATOR ? null : ($data['level'] ?: null);
        $this->ensureLevelMatchesType($data['type'], $data['level']);
        $data['created_by'] = $adminUser->id;

        $package = QuestionPackage::create($data);

        ActivityLog::log('package_create', 'Membuat paket '.$package->name, QuestionPackage::class, $package->id);

        return redirect()->route('admin.packages.index')->with('status', 'Paket berhasil ditambahkan.');
    }

    public function edit(QuestionPackage $package): View
    {
        $adminUser = request()->user();

        if (! $adminUser->canManageType($package->type)) {
            abort(403, 'Anda tidak memiliki akses ke paket ini.');
        }

        $package->load([
            'questions' => fn ($query) => $query->latest()->limit(8),
            'users' => fn ($query) => $query->where('role', 'user')->latest()->limit(8),
        ]);
        $package->loadCount(['questions', 'users']);

        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, QuestionPackage $package): RedirectResponse
    {
        $adminUser = $request->user();

        if (! $adminUser->canManageType($package->type)) {
            abort(403, 'Anda tidak memiliki akses ke paket ini.');
        }

        $visibleTypes = $adminUser->visiblePackageTypes();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($visibleTypes)],
            'level' => ['nullable', 'string', Rule::in(array_keys(QuestionPackage::levelOptions()))],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_certificate' => ['nullable', 'boolean'],
            'has_segments' => ['nullable', 'boolean'],
            'min_score_pertimbangan' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_score_lolos' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_certificate'] = $request->boolean('is_certificate');
        $data['has_segments'] = $data['type'] === QuestionPackage::TYPE_SHE || $request->boolean('has_segments');
        $data['level'] = $data['type'] === QuestionPackage::TYPE_OPERATOR ? null : ($data['level'] ?: null);
        $this->ensureLevelMatchesType($data['type'], $data['level']);

        $package->update($data);

        ActivityLog::log('package_update', 'Mengupdate paket '.$package->name, QuestionPackage::class, $package->id);

        return redirect()->route('admin.packages.index')->with('status', 'Paket berhasil diperbarui.');
    }

    public function questions(Request $request, QuestionPackage $package): View
    {
        $adminUser = $request->user();

        if (! $adminUser->canManageType($package->type)) {
            abort(403, 'Anda tidak memiliki akses ke paket ini.');
        }

        $questions = $package->questions()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where('text', 'like', '%'.$request->string('search')->toString().'%');
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

        $categories = $package->questions()->select('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.packages.questions', compact('package', 'questions', 'categories'));
    }

    public function destroy(Request $request, QuestionPackage $package): RedirectResponse
    {
        $adminUser = $request->user();

        if (! $adminUser->canManageType($package->type)) {
            abort(403, 'Anda tidak memiliki akses ke paket ini.');
        }

        if ($package->users()->exists()) {
            return back()->with('status', 'Paket masih dipakai user. Pindahkan user terlebih dahulu.');
        }

        $questionStats = $this->deleteOrDetachPackageQuestions($package);

        ActivityLog::log('package_delete', 'Menghapus paket '.$package->name, QuestionPackage::class, $package->id);

        $package->delete();

        $message = 'Paket berhasil dihapus.';
        if ($questionStats['deleted'] > 0 || $questionStats['deactivated'] > 0) {
            $message .= ' '.$questionStats['deleted'].' soal ikut dihapus, '.$questionStats['deactivated'].' soal dinonaktifkan karena sudah punya riwayat.';
        }

        return back()->with('status', $message);
    }

    /**
     * @return array{deleted: int, deactivated: int}
     */
    private function deleteOrDetachPackageQuestions(QuestionPackage $package): array
    {
        $stats = ['deleted' => 0, 'deactivated' => 0];

        $package->questions()
            ->withCount('answers')
            ->chunkById(100, function ($questions) use (&$stats): void {
                foreach ($questions as $question) {
                    if ($question->answers_count > 0) {
                        $question->update([
                            'question_package_id' => null,
                            'is_active' => false,
                        ]);
                        $stats['deactivated']++;

                        ActivityLog::log('question_deactivate', 'Menonaktifkan soal #'.$question->id.' karena paket dihapus', Question::class, $question->id);

                        continue;
                    }

                    ActivityLog::log('question_delete', 'Menghapus soal #'.$question->id.' karena paket dihapus', Question::class, $question->id);
                    $question->delete();
                    $stats['deleted']++;
                }
            });

        return $stats;
    }

    private function ensureLevelMatchesType(string $type, ?string $level): void
    {
        if ($level === null) {
            return;
        }

        if (! array_key_exists($level, QuestionPackage::levelsFor($type))) {
            throw ValidationException::withMessages([
                'level' => 'Level harus sesuai dengan tipe paket yang dipilih.',
            ]);
        }
    }
}
