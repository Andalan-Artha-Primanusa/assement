<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OperatorAssessmentCategory;
use App\Models\QuestionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperatorAssessmentCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeOperator($request);

        $categories = OperatorAssessmentCategory::query()
            ->withCount('users')
            ->latest()
            ->paginate(12);

        return view('admin.operator-categories.index', compact('categories'));
    }

    public function create(Request $request): View
    {
        $this->authorizeOperator($request);

        $category = new OperatorAssessmentCategory(['is_active' => true]);

        return view('admin.operator-categories.create', compact('category'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeOperator($request);

        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $category = OperatorAssessmentCategory::create($data);

        ActivityLog::log('operator_category_create', 'Membuat kategori operator '.$category->name, OperatorAssessmentCategory::class, $category->id);

        return redirect()->route('admin.operator-categories.index')->with('status', 'Kategori Operator berhasil ditambahkan.');
    }

    public function edit(Request $request, OperatorAssessmentCategory $operatorCategory): View
    {
        $this->authorizeOperator($request);

        return view('admin.operator-categories.edit', ['category' => $operatorCategory]);
    }

    public function update(Request $request, OperatorAssessmentCategory $operatorCategory): RedirectResponse
    {
        $this->authorizeOperator($request);

        $operatorCategory->update($this->validated($request, $operatorCategory));

        ActivityLog::log('operator_category_update', 'Mengupdate kategori operator '.$operatorCategory->name, OperatorAssessmentCategory::class, $operatorCategory->id);

        return redirect()->route('admin.operator-categories.index')->with('status', 'Kategori Operator berhasil diperbarui.');
    }

    public function destroy(Request $request, OperatorAssessmentCategory $operatorCategory): RedirectResponse
    {
        $this->authorizeOperator($request);

        if ($operatorCategory->users()->exists()) {
            return back()->with('status', 'Kategori masih dipakai peserta Operator. Pindahkan peserta terlebih dahulu.');
        }

        ActivityLog::log('operator_category_delete', 'Menghapus kategori operator '.$operatorCategory->name, OperatorAssessmentCategory::class, $operatorCategory->id);

        $operatorCategory->delete();

        return back()->with('status', 'Kategori Operator berhasil dihapus.');
    }

    private function validated(Request $request, ?OperatorAssessmentCategory $category = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('operator_assessment_categories', 'name')->ignore($category),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function authorizeOperator(Request $request): void
    {
        abort_unless($request->user()->canManageType(QuestionPackage::TYPE_OPERATOR), 403);
    }
}
