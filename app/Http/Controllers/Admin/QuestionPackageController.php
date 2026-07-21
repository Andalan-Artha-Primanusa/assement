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
    public function index(): View
    {
        $packages = QuestionPackage::withCount(['questions', 'users'])
            ->with('creator')
            ->latest()
            ->paginate(12);

        return view('admin.packages.index', compact('packages'));
    }

    public function create(): View
    {
        $package = new QuestionPackage(['is_active' => true]);

        return view('admin.packages.create', compact('package'));
    }

    public function store(Request $request): RedirectResponse
    {
        $package = QuestionPackage::create([
            'name' => $request->validate(['name' => ['required', 'string', 'max:255']])['name'],
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active'),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::log('package_create', 'Membuat paket '.$package->name, QuestionPackage::class, $package->id);

        return redirect()->route('admin.packages.index')->with('status', 'Paket berhasil ditambahkan.');
    }

    public function edit(QuestionPackage $package): View
    {
        $package->load([
            'questions' => fn ($query) => $query->latest()->limit(8),
            'users' => fn ($query) => $query->where('is_admin', false)->latest()->limit(8),
        ]);
        $package->loadCount(['questions', 'users']);

        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, QuestionPackage $package): RedirectResponse
    {
        $package->update([
            'name' => $request->validate(['name' => ['required', 'string', 'max:255']])['name'],
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::log('package_update', 'Mengupdate paket '.$package->name, QuestionPackage::class, $package->id);

        return redirect()->route('admin.packages.index')->with('status', 'Paket berhasil diperbarui.');
    }

    public function questions(Request $request, QuestionPackage $package): View
    {
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
