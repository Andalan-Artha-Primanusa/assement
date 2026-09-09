<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ActivityLog;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentSegment;
use App\Models\OperatorAssessmentCategory;
use App\Models\Question;
use App\Models\QuestionPackage;
use App\Models\Site;
use App\Models\User;
use App\Services\AssessmentSecurity;
use App\Support\AssessmentSegmentConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class AssessmentController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $selectedType = $request->string('type')->toString();
        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, QuestionPackage::TYPES, true)) {
            $visibleTypes = [$selectedType];
        }

        $assessments = Assessment::with('user', 'questionPackage', 'operatorAssessmentCategory')
            ->when(! $adminUser->isSuperAdmin(), function ($query) use ($visibleTypes): void {
                $query->whereHas('questionPackage', function ($q) use ($visibleTypes): void {
                    $q->whereIn('type', $visibleTypes);
                });
            })
            ->when($adminUser->isSuperAdmin() && $selectedType, function ($query) use ($selectedType): void {
                $query->whereHas('questionPackage', function ($q) use ($selectedType): void {
                    $q->where('type', $selectedType);
                });
            })
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $this->applyAssessmentSiteScope($query, $adminUser);
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->whereHas('user', function ($q) use ($search): void {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                match ($request->string('status')->toString()) {
                    'submitted' => $query->whereNotNull('submitted_at'),
                    'pending' => $query->whereNull('submitted_at')->whereNull('blocked_at'),
                    'blocked' => $query->whereNotNull('blocked_at')->whereNull('submitted_at'),
                    'pending_review' => $query->where('status', Assessment::STATUS_PENDING_REVIEW)
                        ->whereHas('questionPackage', function ($q): void {
                            $q->where('type', QuestionPackage::TYPE_SHE);
                        }),
                    'graded' => $query->where('status', Assessment::STATUS_GRADED),
                    default => null,
                };
            })
            ->when($request->filled('package'), function ($query) use ($request): void {
                $query->where('question_package_id', $request->integer('package'));
            })
            ->when($request->filled('operator_category'), function ($query) use ($request): void {
                $query->where('operator_assessment_category_id', $request->integer('operator_category'));
            })
            ->when($request->filled('site'), function ($query) use ($request): void {
                $site = $request->string('site')->toString();
                $query->where(function ($q) use ($site): void {
                    $q->where('site', $site)
                        ->orWhere(function ($subQuery) use ($site): void {
                            $subQuery->whereNull('site')
                                ->whereHas('user', fn ($userQuery) => $userQuery->where('site', $site));
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $packages = \App\Models\QuestionPackage::whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();
        $operatorCategories = OperatorAssessmentCategory::where('is_active', true)
            ->orderBy('name')
            ->get();
        $sites = Assessment::query()
            ->whereHas('questionPackage', fn ($query) => $query->whereIn('type', $visibleTypes))
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $this->applyAssessmentSiteScope($query, $adminUser);
            })
            ->select('site')
            ->whereNotNull('site')
            ->where('site', '<>', '')
            ->distinct()
            ->orderBy('site')
            ->pluck('site')
            ->merge(
                User::query()
                    ->where('role', User::ROLE_USER)
                    ->whereNotNull('site')
                    ->where('site', '<>', '')
                    ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                        $query->where('site', $adminUser->normalizedSite());
                    })
                    ->distinct()
                    ->orderBy('site')
                    ->pluck('site')
            )
            ->unique()
            ->values();

        return view('admin.assessments.index', compact('assessments', 'packages', 'operatorCategories', 'sites', 'selectedType'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();
        $selectedType = $request->string('type')->toString();

        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, QuestionPackage::TYPES, true)) {
            $visibleTypes = [$selectedType];
        }

        $assessments = Assessment::with('user', 'questionPackage', 'operatorAssessmentCategory')
            ->whereHas('questionPackage', function ($query) use ($visibleTypes): void {
                $query->whereIn('type', $visibleTypes);
            })
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $this->applyAssessmentSiteScope($query, $adminUser);
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                match ($request->string('status')->toString()) {
                    'submitted' => $query->whereNotNull('submitted_at'),
                    'pending' => $query->whereNull('submitted_at')->whereNull('blocked_at'),
                    'blocked' => $query->whereNotNull('blocked_at')->whereNull('submitted_at'),
                    'pending_review' => $query->where('status', Assessment::STATUS_PENDING_REVIEW)
                        ->whereHas('questionPackage', function ($q): void {
                            $q->where('type', QuestionPackage::TYPE_SHE);
                        }),
                    'graded' => $query->where('status', Assessment::STATUS_GRADED),
                    default => null,
                };
            })
            ->when($request->filled('package'), function ($query) use ($request): void {
                $query->where('question_package_id', $request->integer('package'));
            })
            ->when($request->filled('operator_category'), function ($query) use ($request): void {
                $query->where('operator_assessment_category_id', $request->integer('operator_category'));
            })
            ->when($request->filled('site'), function ($query) use ($request): void {
                $site = $request->string('site')->toString();
                $query->where(function ($q) use ($site): void {
                    $q->where('site', $site)
                        ->orWhere(function ($subQuery) use ($site): void {
                            $subQuery->whereNull('site')
                                ->whereHas('user', fn ($userQuery) => $userQuery->where('site', $site));
                        });
                });
            })
            ->latest()
            ->get();

        $filename = 'assessment-' . now()->format('Y-m-d') . '.xlsx';
        $tempBasePath = tempnam(storage_path('app'), 'assessment-export-');

        abort_if($tempBasePath === false, 500, 'Gagal menyiapkan file export.');

        $tempPath = $tempBasePath . '.xlsx';
        rename($tempBasePath, $tempPath);

        $rows = [[
            'Peserta',
            'Email',
            'Paket',
            'Kategori Invite',
            'Site',
            'Mulai',
            'Selesai',
            'Benar',
            'Total',
            'Nilai',
            'Status',
            'Pelanggaran',
        ]];

        foreach ($assessments as $a) {
            $status = $a->isPendingReview()
                ? 'Menunggu Review SHE'
                : ($a->isSubmitted() ? 'Selesai' : ($a->isBlocked() ? 'Terblokir' : 'Berjalan'));

            $rows[] = [
                $a->user->name,
                $a->user->email,
                $a->questionPackage?->name ?? '-',
                $a->operatorAssessmentCategory?->name ?? '-',
                $a->site ?: ($a->user->site ?: '-'),
                $a->started_at?->format('d/m/Y H:i') ?? '-',
                $a->submitted_at?->format('d/m/Y H:i') ?? '-',
                $a->correct_answers ?? 0,
                $a->total_questions ?? 0,
                $a->isPendingReview() ? 'Review SHE' : ($a->isSubmitted() ? number_format($a->score ?? 0, 2) : '-'),
                $status,
                $a->security_violations ?? 0,
            ];
        }

        $this->writeAssessmentExportXlsx($rows, $tempPath);

        return response()
            ->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function edit(Request $request, Assessment $assessment): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorizeAssessment($request, $assessment);

        $adminUser = $request->user();
        $packages = QuestionPackage::whereIn('type', $adminUser->visiblePackageTypes())
            ->orderBy('name')
            ->get();
        $operatorCategories = OperatorAssessmentCategory::where('is_active', true)
            ->orderBy('name')
            ->get();
        $sites = Site::active()
            ->when($adminUser->hasSiteRestriction(), fn ($query) => $query->where('code', $adminUser->normalizedSite()))
            ->orderBy('code')
            ->get();

        $assessment->load('user', 'questionPackage', 'operatorAssessmentCategory');

        return view('admin.assessments.edit', compact('assessment', 'packages', 'operatorCategories', 'sites'));
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorizeAssessment($request, $assessment);

        $adminUser = $request->user();
        $data = $request->validate([
            'question_package_id' => [
                'nullable',
                'integer',
                Rule::exists('question_packages', 'id')->where(fn ($query) => $query->whereIn('type', $adminUser->visiblePackageTypes())),
            ],
            'operator_assessment_category_id' => ['nullable', 'integer', Rule::exists('operator_assessment_categories', 'id')],
            'site' => ['nullable', 'string', 'max:100'],
            'status_mode' => ['required', 'string', 'in:running,blocked,submitted,not_started'],
            'total_questions' => ['required', 'integer', 'min:0', 'max:10000'],
            'correct_answers' => ['required', 'integer', 'min:0', 'max:10000'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'started_at' => ['nullable', 'date'],
            'submitted_at' => ['nullable', 'date'],
        ]);

        if ($data['status_mode'] === 'not_started') {
            return $this->resetStatus($request, $assessment);
        }

        $site = $adminUser->hasSiteRestriction()
            ? $adminUser->normalizedSite()
            : ($data['site'] ?: null);

        $statusUpdates = match ($data['status_mode']) {
            'submitted' => [
                'status' => Assessment::STATUS_GRADED,
                'submitted_at' => $data['submitted_at'] ?? now(),
                'blocked_at' => null,
                'block_reason' => null,
                'unlocked_at' => null,
            ],
            'blocked' => [
                'status' => Assessment::STATUS_IN_PROGRESS,
                'submitted_at' => null,
                'blocked_at' => now(),
                'block_reason' => 'Diatur manual oleh admin.',
                'unlocked_at' => null,
            ],
            default => [
                'status' => Assessment::STATUS_IN_PROGRESS,
                'submitted_at' => null,
                'blocked_at' => null,
                'block_reason' => null,
                'unlocked_at' => null,
            ],
        };

        $assessment->update([
            'question_package_id' => $data['question_package_id'] ?? null,
            'operator_assessment_category_id' => $data['operator_assessment_category_id'] ?? null,
            'site' => $site,
            'total_questions' => (int) $data['total_questions'],
            'correct_answers' => min((int) $data['correct_answers'], (int) $data['total_questions']),
            'score' => round((float) $data['score'], 2),
            'started_at' => $data['started_at'] ?? $assessment->started_at,
            ...$statusUpdates,
        ]);

        ActivityLog::log('assessment_update', 'Mengedit assessment #'.$assessment->id, Assessment::class, $assessment->id);

        return redirect()->route('admin.assessments.index')->with('status', 'Assessment berhasil diperbarui.');
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function writeAssessmentExportXlsx(array $rows, string $path): void
    {
        $lastRow = max(1, count($rows));
        $sheetData = '';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $sheetData .= '<row r="' . $excelRow . '" ht="20" customHeight="1">';

            foreach ($row as $columnIndex => $value) {
                $sheetData .= $this->xlsxCell($value, $excelRow, $columnIndex + 1, $excelRow === 1 ? 1 : 0);
            }

            $sheetData .= '</row>';
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>'
            . '<col min="1" max="4" width="24" customWidth="1"/>'
            . '<col min="5" max="7" width="18" customWidth="1"/>'
            . '<col min="8" max="10" width="12" customWidth="1"/>'
            . '<col min="11" max="11" width="18" customWidth="1"/>'
            . '<col min="12" max="12" width="12" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '<autoFilter ref="A1:L' . $lastRow . '"/>'
            . '</worksheet>';

        $zip = new ZipArchive();
        abort_unless($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE), 500, 'Gagal membuat file export.');

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Competra</Application></Properties>');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Assessment Report</dc:title><dc:creator>Competra</dc:creator></cp:coreProperties>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Assessment" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF002060"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
    }

    private function xlsxCell(mixed $value, int $row, int $column, int $styleIndex = 0): string
    {
        $cell = $this->xlsxColumnName($column) . $row;
        $style = $styleIndex > 0 ? ' s="' . $styleIndex . '"' : '';

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $cell . '"' . $style . '><v>' . $value . '</v></c>';
        }

        return '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t>' . htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
    }

    private function xlsxColumnName(int $column): string
    {
        $name = '';

        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)) . $name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    public function start(Request $request): RedirectResponse
    {
        if (! $request->user()->canAccessAssessment()) {
            return back()->with('status', 'Masa akses assessment untuk akun ini sudah habis. Hubungi admin.');
        }

        $user = $request->user();
        $package = $user->questionPackage;
        $inviteCategoryId = $user->operator_assessment_category_id;
        $maxAttempts = $user->max_attempts ?? (int) config('assessment.max_attempts', 1);
        $completedCount = $user
            ->assessments()
            ->whereNotNull('submitted_at')
            ->when($package, fn ($query) => $query->where('question_package_id', $package->id))
            ->when(
                $inviteCategoryId,
                fn ($query) => $query->where('operator_assessment_category_id', $inviteCategoryId),
                fn ($query) => $query->whereNull('operator_assessment_category_id')
            )
            ->count();

        if ($completedCount >= $maxAttempts) {
            return back()->with('status', 'Batas maksimal percobaan assessment ('.$maxAttempts.' kali) sudah terpakai semua. Hubungi admin.');
        }

        $openAssessment = $user
            ->assessments()
            ->whereNull('submitted_at')
            ->when($package, fn ($query) => $query->where('question_package_id', $package->id))
            ->when(
                $inviteCategoryId,
                fn ($query) => $query->where('operator_assessment_category_id', $inviteCategoryId),
                fn ($query) => $query->whereNull('operator_assessment_category_id')
            )
            ->latest()
            ->first();

        if ($openAssessment) {
            return redirect()->route('assessment.show', $openAssessment);
        }

        if ($package && ! $package->is_active) {
            return back()->with('status', 'Paket soal untuk akun ini sedang nonaktif. Hubungi admin.');
        }

        $questionQuery = Question::query()->where('is_active', true);

        if ($package) {
            $questionQuery->where('question_package_id', $package->id);
        }

        $activeQuestionCount = (clone $questionQuery)->count();

        if ($activeQuestionCount === 0) {
            $message = $package
                ? 'Paket soal '.$package->name.' belum punya soal aktif. Admin perlu menambahkan atau mengaktifkan soal.'
                : 'Belum ada soal aktif. Admin perlu menambahkan atau mengaktifkan soal.';

            return back()->with('status', $message);
        }

        $segmentConfig = $this->segmentConfigFor($user, $package);
        $hasSegments = $package
            && ($package->has_segments || $package->type === QuestionPackage::TYPE_SHE)
            && $segmentConfig !== [];

        if ($hasSegments) {
            return $this->startSegmentedAssessment($request, $package, $questionQuery, $segmentConfig);
        }

        $durationMinutes = $user->assessmentDurationMinutes();

        $assessment = DB::transaction(function () use ($request, $durationMinutes, $package, $questionQuery, $activeQuestionCount, $inviteCategoryId) {
            $assessment = Assessment::create([
                'user_id' => $request->user()->id,
                'question_package_id' => $package?->id,
                'operator_assessment_category_id' => $inviteCategoryId,
                'site' => $request->user()->site,
                'total_questions' => $activeQuestionCount,
                'started_at' => now(),
                'duration_minutes' => $durationMinutes,
                'ends_at' => now()->addMinutes($durationMinutes),
            ]);

            $this->randomizedQuestions($questionQuery)
                ->each(function (Question $question, int $index) use ($assessment): void {
                    AssessmentAnswer::create([
                        'assessment_id' => $assessment->id,
                        'question_id' => $question->id,
                        'position' => $index + 1,
                    ]);
                });

            return $assessment;
        });

        ActivityLog::log('assessment_start', 'Memulai assessment', Assessment::class, $assessment->id);

        return redirect()->route('assessment.show', $assessment);
    }

    /**
     * @param  array<int, array{type:string,duration:int}>  $segmentConfig
     */
    private function startSegmentedAssessment(Request $request, QuestionPackage $package, $questionQuery, array $segmentConfig): RedirectResponse
    {
        $segmentQuestionGroups = collect($segmentConfig)
            ->map(function (array $segment) use ($questionQuery): array {
                return [
                    'type' => $segment['type'],
                    'duration' => (int) $segment['duration'],
                    'questions' => $this->randomizedQuestions(
                        (clone $questionQuery)->where('type', $segment['type'])
                    ),
                ];
            })
            ->values();

        if ($package->type === QuestionPackage::TYPE_SHE) {
            $missingSegments = collect(AssessmentSegmentConfig::SHE_SEGMENT_TYPES)
                ->filter(function (string $type) use ($segmentQuestionGroups): bool {
                    $segment = $segmentQuestionGroups->firstWhere('type', $type);

                    return ! $segment || $segment['questions']->isEmpty();
                })
                ->map(fn (string $type): string => $this->segmentTypeLabel($type))
                ->values();

            if ($missingSegments->isNotEmpty()) {
                return back()->with('status', 'Paket SHE belum lengkap. Soal aktif yang kurang: '.$missingSegments->implode(', ').'.');
            }
        }

        $segmentQuestionGroups = $segmentQuestionGroups
            ->filter(fn (array $segment): bool => $segment['questions']->isNotEmpty())
            ->values();

        if ($segmentQuestionGroups->isEmpty()) {
            return back()->with('status', 'Paket SHE belum memiliki soal aktif untuk segmen PG, Essay, atau Portfolio.');
        }

        $totalQuestions = $segmentQuestionGroups->sum(fn (array $segment): int => $segment['questions']->count());
        $totalDuration = $segmentQuestionGroups->sum('duration');

        $assessment = DB::transaction(function () use ($request, $package, $segmentQuestionGroups, $totalQuestions, $totalDuration) {
            $assessment = Assessment::create([
                'user_id' => $request->user()->id,
                'question_package_id' => $package->id,
                'operator_assessment_category_id' => $request->user()->operator_assessment_category_id,
                'site' => $request->user()->site,
                'total_questions' => $totalQuestions,
                'started_at' => now(),
                'duration_minutes' => $totalDuration,
                'ends_at' => now()->addMinutes($totalDuration),
            ]);

            $position = 1;
            foreach ($segmentQuestionGroups as $group) {
                foreach ($group['questions'] as $question) {
                    AssessmentAnswer::create([
                        'assessment_id' => $assessment->id,
                        'question_id' => $question->id,
                        'position' => $position,
                    ]);
                    $position++;
                }
            }

            foreach ($segmentQuestionGroups as $index => $seg) {
                AssessmentSegment::create([
                    'assessment_id' => $assessment->id,
                    'type' => $seg['type'],
                    'duration_minutes' => $seg['duration'],
                    'order_index' => $index,
                    'status' => $index === 0 ? AssessmentSegment::STATUS_IN_PROGRESS : AssessmentSegment::STATUS_PENDING,
                    'started_at' => $index === 0 ? now() : null,
                ]);
            }

            return $assessment;
        });

        ActivityLog::log('assessment_start', 'Memulai assessment bersegment', Assessment::class, $assessment->id);

        return redirect()->route('assessment.show', $assessment);
    }

    public function show(Request $request, Assessment $assessment): View|RedirectResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->isSubmitted()) {
            return redirect()->route('assessment.result', $assessment);
        }

        if ($assessment->isBlocked()) {
            return view('assessment.blocked', compact('assessment'));
        }

        if ($assessment->isExpired()) {
            app(AssessmentSecurity::class)->finishAssessment($assessment, [], true);

            return redirect()->route('assessment.result', $assessment)
                ->with('status', 'Waktu pengerjaan sudah habis. Assessment otomatis dikirim.');
        }

        $assessment->load('answers.question', 'segments');

        $hasSegments = $assessment->segments()->count() > 0;
        $currentSegment = null;
        $segmentAnswers = null;

        if ($hasSegments) {
            $currentSegment = $assessment->segments()
                ->where('status', AssessmentSegment::STATUS_IN_PROGRESS)
                ->first();

            if (! $currentSegment) {
                $nextSegment = $assessment->segments()
                    ->where('status', AssessmentSegment::STATUS_PENDING)
                    ->orderBy('order_index')
                    ->first();

                if ($nextSegment) {
                    $nextSegment->update([
                        'status' => AssessmentSegment::STATUS_IN_PROGRESS,
                        'started_at' => now(),
                    ]);

                    ActivityLog::log('assessment_segment_recover', 'Memulihkan segment '.$nextSegment->type, Assessment::class, $assessment->id);

                    return redirect()->route('assessment.show', $assessment);
                }

                app(AssessmentSecurity::class)->finishAssessment($assessment);

                $this->notifyAdmins($assessment);

                ActivityLog::log('assessment_submit', 'Semua segment selesai, assessment difinalkan otomatis', Assessment::class, $assessment->id);

                return redirect()->route('assessment.result', $assessment)
                    ->with('status', 'Semua segment sudah selesai. Assessment otomatis dikirim.');
            }

            if ($currentSegment->remainingSeconds() <= 0) {
                return $this->completeSegment($request, $assessment, $currentSegment);
            }

            $segmentAnswers = $assessment->answers->filter(
                fn ($a) => $a->question->type === $currentSegment->type
            );

            return view('assessment.show-segmented', compact('assessment', 'hasSegments', 'currentSegment', 'segmentAnswers'));
        }

        $hasUploadQuestions = $assessment->answers->contains(fn ($answer) => $answer->question->isUpload());

        return view('assessment.show', compact('assessment', 'hasUploadQuestions'));
    }

    private function completeSegment(Request $request, Assessment $assessment, AssessmentSegment $segment): RedirectResponse
    {
        $segment->update([
            'status' => AssessmentSegment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $nextSegment = $assessment->segments()
            ->where('status', AssessmentSegment::STATUS_PENDING)
            ->orderBy('order_index')
            ->first();

        if ($nextSegment) {
            $nextSegment->update([
                'status' => AssessmentSegment::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            ActivityLog::log('assessment_segment_next', 'Lanjut ke segment '.$nextSegment->type, Assessment::class, $assessment->id);

            return redirect()->route('assessment.show', $assessment);
        }

        app(AssessmentSecurity::class)->finishAssessment($assessment);

        $this->notifyAdmins($assessment);

        ActivityLog::log('assessment_submit', 'Semua segment selesai, assessment otomatis dikirim', Assessment::class, $assessment->id);

        return redirect()->route('assessment.result', $assessment)
            ->with('status', 'Semua segment selesai. Assessment otomatis dikirim.');
    }

    public function submit(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->isSubmitted()) {
            return redirect()->route('assessment.result', $assessment);
        }

        if ($assessment->isBlocked()) {
            return redirect()->route('assessment.show', $assessment)
                ->with('status', 'Assessment terkunci. Minta admin untuk membuka akses.');
        }

        $hasSegments = $assessment->segments()->count() > 0;

        if ($hasSegments) {
            $currentSegment = $assessment->segments()
                ->where('status', AssessmentSegment::STATUS_IN_PROGRESS)
                ->first();

            if ($currentSegment) {
                $this->saveSegmentAnswers($request, $assessment, $currentSegment);

                return $this->completeSegment($request, $assessment, $currentSegment);
            }
        }

        $validationRules = [
            'answers' => ['array'],
        ];

        $assessment->load('answers.question');
        foreach ($assessment->answers as $answer) {
            if ($answer->question->isEssay()) {
                $validationRules['answers.'.$answer->id] = ['nullable', 'string', 'max:5000'];
            } elseif ($answer->question->isUpload()) {
                $validationRules['answers.'.$answer->id] = ['nullable', 'file', 'max:10240'];
            } else {
                $validationRules['answers.'.$answer->id] = ['nullable', 'in:'.implode(',', $answer->question->answerOptions())];
            }
        }

        $validated = $request->validate($validationRules);

        if ($assessment->isExpired()) {
            $this->processUploadedFiles($request, $assessment, $validated['answers'] ?? []);
            app(AssessmentSecurity::class)->finishAssessment($assessment, $validated['answers'] ?? [], true);

            return redirect()->route('assessment.result', $assessment)
                ->with('status', 'Waktu pengerjaan sudah habis. Assessment otomatis dikirim.');
        }

        $this->processUploadedFiles($request, $assessment, $validated['answers'] ?? []);
        app(AssessmentSecurity::class)->finishAssessment($assessment, $validated['answers'] ?? []);

        $this->notifyAdmins($assessment);

        ActivityLog::log('assessment_submit', 'Menyelesaikan assessment', Assessment::class, $assessment->id);

        return redirect()->route('assessment.result', $assessment);
    }

    private function saveSegmentAnswers(Request $request, Assessment $assessment, AssessmentSegment $segment): void
    {
        $segmentAnswers = $assessment->answers->filter(
            fn ($a) => $a->question->type === $segment->type
        );

        $validationRules = ['answers' => ['array']];
        foreach ($segmentAnswers as $answer) {
            if ($answer->question->isEssay()) {
                $validationRules['answers.'.$answer->id] = ['nullable', 'string', 'max:5000'];
            } elseif ($answer->question->isUpload()) {
                $validationRules['answers.'.$answer->id] = ['nullable', 'file', 'max:10240'];
            } else {
                $validationRules['answers.'.$answer->id] = ['nullable', 'in:a,b,c,d'];
            }
        }
        $request->validate($validationRules);

        foreach ($segmentAnswers as $answer) {
            if ($answer->question->isEssay()) {
                $text = $request->input('answers.'.$answer->id);
                if ($text !== null) {
                    $answer->update(['answer_text' => $text]);
                }
            } elseif ($answer->question->isUpload()) {
                if ($request->hasFile('answers.'.$answer->id)) {
                    $this->processUploadedFiles($request, $assessment, [$answer->id => $request->file('answers.'.$answer->id)]);
                }
            } else {
                $value = $request->input('answers.'.$answer->id);
                if ($value !== null && in_array($value, $answer->question->answerOptions(), true)) {
                    $answer->update([
                        'selected_option' => $value,
                        'is_correct' => $value === $answer->question->correct_option,
                    ]);
                }
            }
        }
    }

    public function result(Request $request, Assessment $assessment): View|RedirectResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if (! $assessment->isSubmitted()) {
            return redirect()->route('assessment.show', $assessment)
                ->with('status', 'Assessment belum selesai. Lanjutkan pengerjaan terlebih dahulu.');
        }

        $assessment->load('user', 'questionPackage', 'segments', 'answers.question');
        $canViewAnswerDetails = $request->user()->isAdmin();

        return view('assessment.result', compact('assessment', 'canViewAnswerDetails'));
    }

    public function certificate(Request $request, Assessment $assessment): View
    {
        $this->authorizeAssessment($request, $assessment);

        abort_unless($assessment->isSubmitted(), 404);

        $assessment->load('user', 'questionPackage');
        $package = $assessment->questionPackage;

        abort_unless($package && $package->is_certificate, 404);

        $grade = $package->getGrade((float) $assessment->score);
        abort_unless(in_array($grade, ['Lolos', 'Dipertimbangkan']), 403);

        $certificateNumber = 'AA-PRA/'.$assessment->submitted_at->format('Y/m').'/'.$assessment->id.'-'.$assessment->user_id;

        return view('assessment.certificate', compact('assessment', 'package', 'grade', 'certificateNumber'));
    }

    public function securityViolation(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if (! $request->user()->isAdmin() && ! $assessment->isSubmitted()) {
            $data = $request->validate([
                'reason' => ['nullable', 'string', 'max:255'],
                'answers' => ['array'],
            ]);

            if (! $assessment->isBlocked()) {
                $reason = $data['reason'] ?? 'Peserta meninggalkan halaman assessment.';
                $status = app(AssessmentSecurity::class)->recordViolation($assessment, $reason, $data['answers'] ?? []);

                if ($status['submitted']) {
                    return response()->json([
                        ...$status,
                        'redirect' => route('assessment.result', $assessment),
                    ]);
                }
            }
        }

        $assessment->refresh();

        return response()->json([
            'blocked' => $assessment->isBlocked(),
            'submitted' => $assessment->isSubmitted(),
            'violations' => $assessment->security_violations,
        ]);
    }

    public function unblock(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorizeAssessment($request, $assessment);

        $assessment->update([
            'unlocked_at' => now(),
            'blocked_at' => null,
            'block_reason' => null,
        ]);

        ActivityLog::log('assessment_unblock', 'Membuka blokir assessment #'.$assessment->id, Assessment::class, $assessment->id);

        return back()->with('status', 'Akses assessment berhasil dibuka kembali.');
    }

    public function adminQuestions(Request $request, Assessment $assessment): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorizeAssessment($request, $assessment);

        $assessment->load(['answers.question', 'user', 'questionPackage', 'segments']);

        $assessment->setRelation('answers', $assessment->answers->filter(fn ($a) => $a->question !== null)->values());

        return view('admin.assessments.questions', compact('assessment'));
    }

    public function setDuration(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorizeAssessment($request, $assessment);

        $data = $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $newEndsAt = $assessment->started_at
            ? $assessment->started_at->addMinutes((int) $data['duration_minutes'])
            : now()->addMinutes((int) $data['duration_minutes']);

        $assessment->update([
            'duration_minutes' => (int) $data['duration_minutes'],
            'ends_at' => $newEndsAt,
        ]);

        ActivityLog::log('assessment_set_duration', 'Set durasi assessment #'.$assessment->id.' ke '.$data['duration_minutes'].' menit', Assessment::class, $assessment->id);

        return back()->with('status', 'Durasi assessment berhasil diatur ke '.$data['duration_minutes'].' menit.');
    }

    public function markSubmitted(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->isSubmitted()) {
            return back()->with('status', 'Assessment ini sudah berstatus Sudah Test.');
        }

        app(AssessmentSecurity::class)->finishAssessment($assessment);

        ActivityLog::log('assessment_mark_submitted', 'Menandai assessment #'.$assessment->id.' sebagai Sudah Test', Assessment::class, $assessment->id);

        return back()->with('status', 'Assessment berhasil ditandai sebagai Sudah Test.');
    }

    public function resetStatus(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->authorizeAssessment($request, $assessment);

        ActivityLog::log('assessment_reset_status', 'Mereset assessment #'.$assessment->id.' ke Belum Mengerjakan', Assessment::class, $assessment->id);
        $assessment->delete();

        return redirect()->route('admin.assessments.index')->with('status', 'Assessment berhasil di-reset. Peserta kembali berstatus Belum Mengerjakan jika tidak ada riwayat lain.');
    }

    private function processUploadedFiles(Request $request, Assessment $assessment, array $answers): void
    {
        $assessment->load('answers.question');

        foreach ($assessment->answers as $answer) {
            if ($answer->question && $answer->question->isUpload() && $request->hasFile('answers.'.$answer->id)) {
                $file = $request->file('answers.'.$answer->id);
                $path = $file->store('assessment-uploads', 'public');
                $answer->update(['file_path' => $path]);
            }
        }
    }

    /**
     * @return array<int, array{type:string,duration:int}>
     */
    private function segmentConfigFor(User $user, ?QuestionPackage $package): array
    {
        if (! $package) {
            return [];
        }

        return AssessmentSegmentConfig::forPackage($package, $user->segment_config);
    }

    private function segmentTypeLabel(string $type): string
    {
        return match ($type) {
            Question::TYPE_MULTIPLE_CHOICE => 'PG',
            Question::TYPE_TRUE_FALSE => 'Benar/Salah',
            Question::TYPE_ESSAY => 'Essay',
            Question::TYPE_UPLOAD => 'Portfolio',
            default => $type,
        };
    }

    private function randomizedQuestions($questionQuery)
    {
        $questions = (clone $questionQuery)->get()->values();

        if ($questions->count() <= 1) {
            return $questions;
        }

        $randomized = $questions->shuffle()->values();

        if ($randomized->pluck('id')->all() === $questions->pluck('id')->all()) {
            return $questions->slice(1)->concat($questions->take(1))->values();
        }

        return $randomized;
    }

    private function applyAssessmentSiteScope($query, User $adminUser): void
    {
        $site = $adminUser->normalizedSite();

        $query->where(function ($q) use ($site): void {
            $q->where('site', $site)
                ->orWhere(function ($subQuery) use ($site): void {
                    $subQuery->whereNull('site')
                        ->whereHas('user', fn ($userQuery) => $userQuery->where('site', $site));
                });
        });
    }

    private function notifyAdmins(Assessment $assessment): void
    {
        try {
            $assessment->loadMissing('user');
            $admins = User::whereIn('role', [
                User::ROLE_ADMIN_MEKANIK,
                User::ROLE_ADMIN_OPERATION,
                User::ROLE_ADMIN_SHE,
                User::ROLE_ADMIN_HR,
            ])->get();

            foreach ($admins as $admin) {
                Mail::send('emails.assessment-completed', [
                    'assessment' => $assessment,
                    'user' => $assessment->user,
                    'resultUrl' => route('assessment.result', $assessment),
                ], function ($message) use ($admin, $assessment): void {
                    $message->to($admin->email, $admin->name)
                        ->subject('Assessment Selesai: '.$assessment->user->name);
                });
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi admin: '.$e->getMessage());
        }
    }

    private function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        abort_unless(
            $request->user()->isAdmin() || $assessment->user_id === $request->user()->id,
            403
        );

        if ($request->user()->isAdmin() && $request->user()->hasSiteRestriction()) {
            $assessment->loadMissing('user');
            abort_unless(
                $assessment->site === $request->user()->normalizedSite()
                    || ($assessment->site === null && $assessment->user?->normalizedSite() === $request->user()->normalizedSite()),
                403
            );
        }
    }
}
