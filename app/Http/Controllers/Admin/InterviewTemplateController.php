<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterviewTemplate;
use App\Models\InterviewCategory;
use App\Models\InterviewAspect;
use App\Models\ActivityLog;
use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InterviewTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = InterviewTemplate::withCount('categories')->latest();
        $visibleTypes = $this->visibleInterviewTypes($user);

        abort_if($visibleTypes === [], 403);

        $query->whereIn('type', $visibleTypes);

        $templates = $query->paginate(15);

        return view('admin.interview-templates.index', compact('templates'));
    }

    public function create()
    {
        $visibleTypes = $this->visibleInterviewTypes(auth()->user());

        abort_if($visibleTypes === [], 403);

        return view('admin.interview-templates.create', compact('visibleTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', 'max:50', Rule::in($this->visibleInterviewTypes(auth()->user()))],
            'min_recommended_percentage' => 'required|integer|min:0|max:100',
            'min_considered_percentage' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.aspects' => 'required|array|min:1',
            'categories.*.aspects.*.name' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            $template = InterviewTemplate::create([
                'name' => $request->name,
                'type' => $request->type,
                'min_recommended_percentage' => $request->min_recommended_percentage,
                'min_considered_percentage' => $request->min_considered_percentage,
                'is_active' => $request->has('is_active'),
            ]);

            foreach ($request->categories as $cIndex => $categoryData) {
                $category = $template->categories()->create([
                    'name' => $categoryData['name'],
                    'order' => $cIndex,
                ]);

                foreach ($categoryData['aspects'] as $aIndex => $aspectData) {
                    $category->aspects()->create([
                        'name' => $aspectData['name'],
                        'weight' => 100,
                        'order' => $aIndex,
                    ]);
                }
            }

            ActivityLog::log('interview_template_create', 'Membuat template interview ' . $template->name, InterviewTemplate::class, $template->id);
        });

        return redirect()->route('admin.interview-templates.index')
            ->with('status', 'Template interview berhasil ditambahkan.');
    }

    public function edit(InterviewTemplate $interview_template)
    {
        $this->authorizeInterviewType($interview_template->type);

        $interview_template->load('categories.aspects');
        $visibleTypes = $this->visibleInterviewTypes(auth()->user());

        return view('admin.interview-templates.edit', compact('interview_template', 'visibleTypes'));
    }

    public function update(Request $request, InterviewTemplate $interview_template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', 'max:50', Rule::in($this->visibleInterviewTypes(auth()->user()))],
            'min_recommended_percentage' => 'required|integer|min:0|max:100',
            'min_considered_percentage' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.aspects' => 'required|array|min:1',
            'categories.*.aspects.*.name' => 'required|string|max:255',
        ]);

        $this->authorizeInterviewType($interview_template->type);

        DB::transaction(function () use ($request, $interview_template) {
            $interview_template->update([
                'name' => $request->name,
                'type' => $request->type,
                'min_recommended_percentage' => $request->min_recommended_percentage,
                'min_considered_percentage' => $request->min_considered_percentage,
                'is_active' => $request->has('is_active'),
            ]);

            $keepCategoryIds = [];
            $keepAspectIds = [];

            foreach ($request->categories as $cIndex => $categoryData) {
                $categoryId = $categoryData['id'] ?? null;
                
                if ($categoryId) {
                    $category = $interview_template->categories()->findOrFail($categoryId);
                    $category->update([
                        'name' => $categoryData['name'],
                        'order' => $cIndex,
                    ]);
                } else {
                    $category = $interview_template->categories()->create([
                        'name' => $categoryData['name'],
                        'order' => $cIndex,
                    ]);
                    $categoryId = $category->id;
                }
                $keepCategoryIds[] = $categoryId;

                foreach ($categoryData['aspects'] as $aIndex => $aspectData) {
                    $aspectId = $aspectData['id'] ?? null;

                    if ($aspectId) {
                        $aspect = $category->aspects()->findOrFail($aspectId);
                        $aspect->update([
                            'name' => $aspectData['name'],
                            'order' => $aIndex,
                        ]);
                    } else {
                        $aspect = $category->aspects()->create([
                            'name' => $aspectData['name'],
                            'weight' => 100,
                            'order' => $aIndex,
                        ]);
                        $aspectId = $aspect->id;
                    }
                    $keepAspectIds[] = $aspectId;
                }
            }

            // Delete aspects that are not in the update payload
            InterviewAspect::whereIn('interview_category_id', $keepCategoryIds)
                ->whereNotIn('id', $keepAspectIds)
                ->delete();

            // Delete categories that are not in the update payload
            $interview_template->categories()->whereNotIn('id', $keepCategoryIds)->delete();

            ActivityLog::log('interview_template_update', 'Mengupdate template interview ' . $interview_template->name, InterviewTemplate::class, $interview_template->id);
        });

        return redirect()->route('admin.interview-templates.index')
            ->with('status', 'Template interview berhasil diperbarui.');
    }

    public function destroy(InterviewTemplate $interview_template)
    {
        $this->authorizeInterviewType($interview_template->type);

        ActivityLog::log('interview_template_delete', 'Menghapus template interview ' . $interview_template->name, InterviewTemplate::class, $interview_template->id);
        
        $interview_template->delete();

        return redirect()->route('admin.interview-templates.index')
            ->with('status', 'Template interview berhasil dihapus.');
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
}
