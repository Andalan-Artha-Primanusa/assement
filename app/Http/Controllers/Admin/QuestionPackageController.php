<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionPackageController extends Controller
{
    public function index(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $selectedType = $request->string('type')->toString();
        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, ['mekanik', 'operator', 'she'])) {
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
            $adminUser->isAdminMekanik() => 'mekanik',
            $adminUser->isAdminShe() => 'she',
            default => 'operator',
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
            'type' => ['required', 'string', 'in:'.implode(',', $visibleTypes)],
            'level' => ['nullable', 'string', 'in:M1,M2,M3,T1,T2,T3,AE1,AE2,AE3,W1,W2,W3,Departement Head,Section Head,Lead Of'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'min_score_pertimbangan' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_score_lolos' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['level'] = $data['type'] === 'operator' ? null : ($data['level'] ?: null);
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
            'type' => ['required', 'string', 'in:'.implode(',', $visibleTypes)],
            'level' => ['nullable', 'string', 'in:M1,M2,M3,T1,T2,T3,AE1,AE2,AE3,W1,W2,W3,Departement Head,Section Head,Lead Of'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'min_score_pertimbangan' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_score_lolos' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['level'] = $data['type'] === 'operator' ? null : ($data['level'] ?: null);

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

        if ($package->questions()->exists()) {
            return back()->with('status', 'Paket masih memiliki soal. Pindahkan atau hapus soal terlebih dahulu.');
        }

        if ($package->users()->exists()) {
            return back()->with('status', 'Paket masih dipakai user. Pindahkan user terlebih dahulu.');
        }

        ActivityLog::log('package_delete', 'Menghapus paket '.$package->name, QuestionPackage::class, $package->id);

        $package->delete();

        return back()->with('status', 'Paket berhasil dihapus.');
    }
}
