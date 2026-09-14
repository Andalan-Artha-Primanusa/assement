<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\OperatorAssessmentCategory;
use App\Models\QuestionPackage;
use App\Models\Site;
use App\Models\User;
use App\Support\AssessmentSegmentConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $selectedType = $request->string('type')->toString();
        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, QuestionPackage::TYPES, true)) {
            $visibleTypes = [$selectedType];
        }

        $users = User::query()
            ->with('questionPackage', 'operatorAssessmentCategory')
            ->withCount([
                'assessments',
                'assessments as submitted_assessments_count' => function ($query): void {
                    $query->whereNotNull('submitted_at');
                },
                'assessments as blocked_assessments_count' => function ($query): void {
                    $query->whereNotNull('blocked_at')
                        ->whereNull('submitted_at')
                        ->where(function ($q): void {
                            $q->whereNull('unlocked_at')
                                ->orWhereColumn('unlocked_at', '<', 'blocked_at');
                        });
                },
                'assessments as running_assessments_count' => function ($query): void {
                    $query->whereNull('submitted_at')
                        ->whereNull('blocked_at')
                        ->where(function ($q): void {
                            $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                        });
                },
            ])
            ->when(! $adminUser->isSuperAdmin(), function ($query) use ($visibleTypes): void {
                $query->where(function ($q) use ($visibleTypes): void {
                    $q->where('role', 'user')
                        ->where(function ($q2) use ($visibleTypes): void {
                            $q2->whereIn('question_package_id', function ($subQuery) use ($visibleTypes): void {
                                $subQuery->select('id')->from('question_packages')->whereIn('type', $visibleTypes);
                            })
                            ->orWhereHas('assessments.questionPackage', function ($subQuery) use ($visibleTypes): void {
                                $subQuery->whereIn('type', $visibleTypes);
                            });
                        });
                });
            })
            ->when($adminUser->isSuperAdmin() && $selectedType, function ($query) use ($selectedType): void {
                $query->where(function ($q) use ($selectedType): void {
                    $q->where('role', 'user')
                        ->where(function ($q2) use ($selectedType): void {
                            $q2->whereIn('question_package_id', function ($subQuery) use ($selectedType): void {
                                $subQuery->select('id')->from('question_packages')->where('type', $selectedType);
                            })
                            ->orWhereHas('assessments.questionPackage', function ($subQuery) use ($selectedType): void {
                                $subQuery->where('type', $selectedType);
                            });
                        });
                });
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('package'), function ($query) use ($request): void {
                $packageId = $request->integer('package');
                $query->where(function ($q) use ($packageId): void {
                    $q->where('question_package_id', $packageId)
                      ->orWhereHas('assessments', function ($subQuery) use ($packageId): void {
                          $subQuery->where('question_package_id', $packageId);
                      });
                });
            })
            ->when($request->filled('operator_category'), function ($query) use ($request): void {
                $query->where('operator_assessment_category_id', $request->integer('operator_category'));
            })
            ->when($request->filled('site'), function ($query) use ($request): void {
                $query->where('site', $request->string('site')->toString());
            })
            ->when($request->filled('test_status'), function ($query) use ($request): void {
                match ($request->string('test_status')->toString()) {
                    'submitted' => $query->whereHas('assessments', function ($assessmentQuery): void {
                        $assessmentQuery->whereNotNull('submitted_at');
                    }),
                    'running' => $query->whereHas('assessments', function ($assessmentQuery): void {
                        $assessmentQuery->whereNull('submitted_at')
                            ->whereNull('blocked_at')
                            ->where(function ($q): void {
                                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                            });
                    }),
                    'blocked' => $query->whereHas('assessments', function ($assessmentQuery): void {
                        $assessmentQuery->whereNotNull('blocked_at')
                            ->whereNull('submitted_at')
                            ->where(function ($q): void {
                                $q->whereNull('unlocked_at')
                                    ->orWhereColumn('unlocked_at', '<', 'blocked_at');
                            });
                    }),
                    'not_started' => $query->where('role', User::ROLE_USER)
                        ->whereDoesntHave('assessments'),
                    default => null,
                };
            })
            ->when($request->filled('role'), function ($query) use ($request): void {
                $query->where('role', $request->string('role'));
            })
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $query->where('site', $adminUser->normalizedSite());
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $packages = QuestionPackage::where('is_active', true)
            ->whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();
        $operatorCategories = $this->supportsInviteCategory($visibleTypes)
            ? OperatorAssessmentCategory::orderBy('name')->get()
            : collect();
        $sites = User::query()
            ->where('role', User::ROLE_USER)
            ->whereNotNull('site')
            ->where('site', '<>', '')
            ->when(! $adminUser->isSuperAdmin() || $selectedType, function ($query) use ($visibleTypes): void {
                $query->where(function ($q) use ($visibleTypes): void {
                    $q->whereIn('question_package_id', function ($subQuery) use ($visibleTypes): void {
                        $subQuery->select('id')->from('question_packages')->whereIn('type', $visibleTypes);
                    })
                    ->orWhereHas('assessments.questionPackage', function ($subQuery) use ($visibleTypes): void {
                        $subQuery->whereIn('type', $visibleTypes);
                    });
                });
            })
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $query->where('site', $adminUser->normalizedSite());
            })
            ->distinct()
            ->orderBy('site')
            ->pluck('site');

        return view('admin.users.index', compact('users', 'packages', 'operatorCategories', 'sites', 'selectedType'));
    }

    public function inviteBulk(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $emailIndex = array_search(strtolower('email'), array_map('strtolower', $header ?: []));
        $nameIndex = array_search(strtolower('nama'), array_map('strtolower', $header ?: []));
        $packageIndex = array_search(strtolower('paket'), array_map('strtolower', $header ?: []));
        $typeIndex = array_search(strtolower('tipe'), array_map('strtolower', $header ?: []));
        $operatorCategoryIndex = collect($header ?: [])
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->search(fn ($value) => in_array($value, ['kategori', 'kategori_operator', 'operator_category'], true));
        $siteIndex = collect($header ?: [])
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->search(fn ($value) => in_array($value, ['site', 'lokasi', 'area'], true));

        $adminUser = $request->user();
        $packagesByName = QuestionPackage::whereIn('type', $adminUser->visiblePackageTypes())
            ->get()
            ->keyBy('name');
        $operatorCategoriesByName = OperatorAssessmentCategory::query()
            ->get()
            ->keyBy(fn (OperatorAssessmentCategory $category): string => strtolower($category->name));
        $processed = 0;
        $created = 0;
        $sent = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($emailIndex === false || ! isset($row[$emailIndex]) || ! filter_var(trim($row[$emailIndex]), FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $email = strtolower(trim($row[$emailIndex]));

            $name = $nameIndex !== false && isset($row[$nameIndex])
                ? trim($row[$nameIndex])
                : 'Peserta '.strtoupper(Str::random(6));

            $type = match (true) {
                $adminUser->isAdminMekanik() => QuestionPackage::TYPE_MEKANIK,
                $adminUser->isAdminOperation() => QuestionPackage::TYPE_OPERATOR,
                $adminUser->isAdminShe() => QuestionPackage::TYPE_SHE,
                $adminUser->isAdminHr() => QuestionPackage::TYPE_HR,
                default => QuestionPackage::TYPE_OPERATOR,
            };
            if ($adminUser->isSuperAdmin() && $typeIndex !== false && isset($row[$typeIndex])) {
                $rawType = strtolower(trim($row[$typeIndex]));
                if (in_array($rawType, QuestionPackage::TYPES, true)) {
                    $type = $rawType;
                }
            }

            $package = null;
            $packageId = null;
            if ($packageIndex !== false && isset($row[$packageIndex])) {
                $packageName = trim($row[$packageIndex]);
                $package = $packagesByName[$packageName] ?? null;
                $packageId = $package?->id;
            }
            $operatorCategoryId = null;
            if ($this->supportsInviteCategory($type) && $operatorCategoryIndex !== false && isset($row[$operatorCategoryIndex])) {
                $categoryName = strtolower(trim($row[$operatorCategoryIndex]));
                $operatorCategoryId = $operatorCategoriesByName[$categoryName]?->id ?? null;
            }
            $site = $this->supportsInviteSite($type) && $siteIndex !== false && isset($row[$siteIndex])
                ? Str::of($row[$siteIndex])->squish()->limit(100, '')->toString()
                : null;
            $site = $this->siteForAdmin($adminUser, $site !== '' ? $site : null);

            $password = strtoupper(Str::random(4));
            $accessDays = (int) config('assessment.default_access_days', 7);
            $durationMinutes = (int) config('assessment.default_duration_minutes', 120);

            try {
                [$user, $wasCreated] = $this->createOrRefreshInvitedUser(
                    $email,
                    $name,
                    $packageId,
                    $package,
                    $operatorCategoryId,
                    $site,
                    $password,
                    $accessDays,
                    $durationMinutes,
                );
            } catch (ValidationException) {
                $errors[] = "Email {$email} sudah dipakai akun admin, tidak direset.";
                continue;
            }

            try {
                $this->sendAssessmentInvite($user, $password, $accessDays, $durationMinutes);
                $sent++;
            } catch (\Throwable $e) {
                $errors[] = "Gagal kirim email ke {$email}.";
            }

            ActivityLog::log($wasCreated ? 'user_invite_bulk' : 'user_reinvite_bulk', 'Mengundang user '.$email, User::class, $user->id);
            $processed++;
            $created += $wasCreated ? 1 : 0;
        }

        fclose($handle);

        $message = "Berhasil memproses {$processed} peserta ({$created} akun baru) dan mengirim {$sent} email undangan.";
        if ($errors) {
            $message .= ' '.implode(', ', array_slice($errors, 0, 5));
        }

        return redirect()->route('admin.invite')->with('status', $message);
    }

    public function inviteForm(): View
    {
        $adminUser = request()->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $packages = QuestionPackage::where('is_active', true)
            ->whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();
        $operatorCategories = $this->supportsInviteCategory($visibleTypes)
            ? OperatorAssessmentCategory::where('is_active', true)->orderBy('name')->get()
            : collect();

        $availableTypes = collect($visibleTypes)->map(fn ($t) => [
            'value' => $t,
            'label' => QuestionPackage::typeLabel($t),
        ])->toArray();
        $allSites = Site::active()->excludeHO()->orderBy('code')->get();

        return view('admin.users.invite', compact('packages', 'operatorCategories', 'visibleTypes', 'availableTypes', 'allSites'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', $visibleTypes)],
            'question_package_id' => [
                'nullable',
                'integer',
                Rule::exists('question_packages', 'id')->where(function ($query) use ($visibleTypes): void {
                    $query->whereIn('type', $visibleTypes);
                }),
            ],
            'operator_assessment_category_id' => ['nullable', 'integer', Rule::exists('operator_assessment_categories', 'id')],
            'site' => ['nullable', 'string', 'max:100'],
            'access_days' => ['required', 'integer', 'min:1', 'max:365'],
            'duration_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
        ]);

        $this->ensurePackageMatchesType($data['question_package_id'] ?? null, $data['type']);

        $password = Str::upper(Str::random(4));
        $name = filled($data['name'] ?? null)
            ? $data['name']
            : 'Peserta '.Str::upper(Str::random(6));

        $accessDays = (int) $data['access_days'];
        $durationMinutes = (int) round(((float) $data['duration_hours']) * 60);

        $package = isset($data['question_package_id'])
            ? QuestionPackage::find($data['question_package_id'])
            : null;
        $operatorCategoryId = $this->supportsInviteCategory($data['type'])
            ? ($data['operator_assessment_category_id'] ?? null)
            : null;
        $site = $this->supportsInviteSite($data['type'])
            ? Str::of($data['site'] ?? '')->squish()->toString()
            : null;
        $site = $this->siteForAdmin($adminUser, $site !== '' ? $site : null);

        [$user, $wasCreated] = $this->createOrRefreshInvitedUser(
            $data['email'],
            $name,
            $data['question_package_id'] ?? null,
            $package,
            $operatorCategoryId,
            $site,
            $password,
            $accessDays,
            $durationMinutes,
        );

        $user->load('questionPackage', 'operatorAssessmentCategory');

        $this->sendAssessmentInvite($user, $password, $accessDays, $durationMinutes);

        ActivityLog::log($wasCreated ? 'user_invite' : 'user_reinvite', 'Mengundang user '.$data['email'], User::class, $user->id);

        return redirect()
            ->route('admin.invite')
            ->with('status', ($wasCreated ? 'Undangan peserta dibuat dan dikirim.' : 'Undangan peserta dikirim ulang dengan password baru.')." Nama: {$user->name}");
    }

    public function inviteMany(Request $request): RedirectResponse
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $data = $request->validate([
            'bulk_emails' => ['required', 'string', 'max:20000'],
            'bulk_type' => ['required', 'string', Rule::in($visibleTypes)],
            'bulk_question_package_id' => [
                'nullable',
                'integer',
                Rule::exists('question_packages', 'id')->where(function ($query) use ($visibleTypes): void {
                    $query->whereIn('type', $visibleTypes);
                }),
            ],
            'bulk_operator_assessment_category_id' => ['nullable', 'integer', Rule::exists('operator_assessment_categories', 'id')],
            'bulk_site' => ['nullable', 'string', 'max:100'],
            'bulk_access_days' => ['required', 'integer', 'min:1', 'max:365'],
            'bulk_duration_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
        ]);

        $this->ensurePackageMatchesType($data['bulk_question_package_id'] ?? null, $data['bulk_type']);

        [$parsedEmails, $invalidRows] = $this->parseBulkInviteEmails($data['bulk_emails']);

        if (empty($parsedEmails)) {
            throw ValidationException::withMessages([
                'bulk_emails' => 'Isi minimal satu email yang valid.',
            ]);
        }

        if (count($parsedEmails) > 200) {
            throw ValidationException::withMessages([
                'bulk_emails' => 'Maksimal 200 email dalam sekali kirim.',
            ]);
        }

        $accessDays = (int) $data['bulk_access_days'];
        $durationMinutes = (int) round(((float) $data['bulk_duration_hours']) * 60);
        $packageId = $data['bulk_question_package_id'] ?? null;
        $package = $packageId ? QuestionPackage::find($packageId) : null;
        $operatorCategoryId = $this->supportsInviteCategory($data['bulk_type'])
            ? ($data['bulk_operator_assessment_category_id'] ?? null)
            : null;
        $site = $this->supportsInviteSite($data['bulk_type'])
            ? Str::of($data['bulk_site'] ?? '')->squish()->toString()
            : null;
        $site = $this->siteForAdmin($adminUser, $site !== '' ? $site : null);
        $created = 0;
        $sent = 0;
        $errors = [];

        if ($invalidRows) {
            $errors[] = count($invalidRows).' baris email tidak valid: '.implode(', ', array_slice($invalidRows, 0, 3));
        }

        foreach ($parsedEmails as $item) {
            $email = $item['email'];

            $password = Str::upper(Str::random(4));
            $name = $item['name'] ?: $this->nameFromEmail($email);

            try {
                [$user, $wasCreated] = $this->createOrRefreshInvitedUser(
                    $email,
                    $name,
                    $packageId,
                    $package,
                    $operatorCategoryId,
                    $site,
                    $password,
                    $accessDays,
                    $durationMinutes,
                );
            } catch (ValidationException) {
                $errors[] = "Email {$email} sudah dipakai akun admin, tidak direset.";
                continue;
            }

            try {
                $this->sendAssessmentInvite($user, $password, $accessDays, $durationMinutes);
                $sent++;
            } catch (\Throwable $e) {
                $errors[] = "Akun {$email} diproses, tapi email gagal dikirim.";
            }

            ActivityLog::log($wasCreated ? 'user_invite_bulk' : 'user_reinvite_bulk', 'Mengundang user '.$email, User::class, $user->id);
            $created += $wasCreated ? 1 : 0;
        }

        $message = "Berhasil membuat {$created} akun baru dan mengirim {$sent} email undangan.";
        if ($errors) {
            $message .= ' Catatan: '.implode(', ', array_slice($errors, 0, 5));
        }

        return redirect()->route('admin.invite')->with('status', $message);
    }

    public function create(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();
        
        $formType = $request->string('type')->toString() === 'admin' ? 'admin' : 'peserta';
        abort_unless($adminUser->canViewAllSites(), 403);

        $allSites = Site::active()->orderBy('code')->get();

        if ($formType === 'admin' && ! $request->boolean('form')) {
            $adminUsers = User::query()
                ->whereIn('role', [
                    User::ROLE_ADMIN_MEKANIK,
                    User::ROLE_ADMIN_OPERATION,
                    User::ROLE_ADMIN_SHE,
                    User::ROLE_ADMIN_HR,
                    User::ROLE_SUPER_ADMIN,
                ])
                ->when(! $adminUser->isSuperAdmin(), fn ($query) => $query->where('role', '<>', User::ROLE_SUPER_ADMIN))
                ->latest()
                ->paginate(12)
                ->withQueryString();

            return view('admin.users.admin_index', compact('adminUsers', 'allSites'));
        }

        $user = new User(['role' => $formType === 'admin' ? null : User::ROLE_USER]);
        $user->assessment_access_expires_at = now()->addDays((int) config('assessment.default_access_days', 7));
        $user->assessment_duration_minutes = (int) config('assessment.default_duration_minutes', 120);
        $user->max_attempts = (int) config('assessment.max_attempts', 1);
        $packages = QuestionPackage::where('is_active', true)
            ->whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();
        $operatorCategories = $this->supportsInviteCategory($visibleTypes)
            ? OperatorAssessmentCategory::where('is_active', true)->orderBy('name')->get()
            : collect();
        return view('admin.users.create', compact('user', 'packages', 'operatorCategories', 'formType', 'allSites'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canViewAllSites(), 403);

        $data = $this->validated($request);

        User::create($data);

        ActivityLog::log('user_create', 'Membuat user '.$data['email'], User::class);

        return redirect()->route('admin.users.index')->with('status', 'User berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): RedirectResponse
    {
        $this->authorizeSiteAccess(request()->user(), $user);

        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user): View
    {
        $adminUser = request()->user();
        $this->authorizeSiteAccess($adminUser, $user);
        $visibleTypes = $adminUser->visiblePackageTypes();

        $formType = $user->role === User::ROLE_USER ? 'peserta' : 'admin';

        $packages = QuestionPackage::whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();
        $operatorCategories = $this->supportsInviteCategory($visibleTypes)
            ? OperatorAssessmentCategory::orderBy('name')->get()
            : collect();
        $allSites = Site::active()->orderBy('code')->get();
        $currentTestStatus = $this->testStatusFor($user);

        return view('admin.users.edit', compact('user', 'packages', 'operatorCategories', 'formType', 'allSites', 'currentTestStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSiteAccess($request->user(), $user);

        $testStatus = $request->input('test_status_control');
        $data = $this->validated($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);
        $this->resetOpenAssessmentsThatNoLongerMatchUser($user);
        $this->syncUserAssessmentStatus($user, $testStatus);

        ActivityLog::log('user_update', 'Mengupdate user '.$user->email, User::class, $user->id);

        return redirect()->route('admin.users.index')->with('status', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSiteAccess($request->user(), $user);

        if ($request->user()->is($user)) {
            return back()->with('status', 'Admin yang sedang login tidak bisa menghapus akunnya sendiri.');
        }

        ActivityLog::log('user_delete', 'Menghapus user '.$user->email.' ('.$user->name.')', User::class, $user->id);

        $user->delete();

        return back()->with('status', 'User berhasil dihapus.');
    }

    public function answers(Request $request, User $user): View
    {
        $this->authorizeSiteAccess($request->user(), $user);

        $assessments = $user->assessments()
            ->with(['answers.question', 'questionPackage', 'segments'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->get();

        return view('admin.users.answers', compact('user', 'assessments'));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $adminUser = $request->user();
        $passwordRules = $user
            ? ['nullable', 'confirmed', Rules\Password::defaults()]
            : ['required', 'confirmed', Rules\Password::defaults()];

        $roles = $adminUser->isSuperAdmin()
            ? ['nullable', 'string', 'in:user,admin_mekanik,admin_operation,admin_she,admin_hr,super_admin']
            : ['nullable', 'string', 'in:user,admin_mekanik,admin_operation,admin_she,admin_hr'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user),
            ],
            'password' => $passwordRules,
            'role' => $roles,
            'question_package_id' => [
                'nullable',
                'integer',
                Rule::exists('question_packages', 'id')->where(function ($query) use ($adminUser): void {
                    $query->whereIn('type', $adminUser->visiblePackageTypes());
                }),
            ],
            'operator_assessment_category_id' => ['nullable', 'integer', Rule::exists('operator_assessment_categories', 'id')],
            'site' => ['nullable', 'string', 'max:100'],
            'assessment_access_expires_at' => ['nullable', 'date'],
            'assessment_duration_hours' => ['nullable', 'numeric', 'min:0.25', 'max:24'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'test_status_control' => ['nullable', 'string', 'in:keep,not_started,submitted,running,blocked'],
            'segment_config' => ['nullable', 'array'],
            'segment_config.*.type' => ['required_with:segment_config', 'string', 'in:multiple_choice,essay,upload'],
            'segment_config.*.duration' => ['required_with:segment_config', 'integer', 'min:1', 'max:480'],
        ]);

        $data['role'] = $data['role'] ?? User::ROLE_USER;
        abort_if($data['role'] !== User::ROLE_USER && ! $adminUser->canViewAllSites(), 403);
        $data['question_package_id'] = $data['question_package_id'] ?? null;
        $durationHours = filled($data['assessment_duration_hours'] ?? null)
            ? (float) $data['assessment_duration_hours']
            : ((int) config('assessment.default_duration_minutes', 120) / 60);
        $data['assessment_duration_minutes'] = (int) round($durationHours * 60);
        $data['max_attempts'] = filled($data['max_attempts'] ?? null)
            ? (int) $data['max_attempts']
            : (int) config('assessment.max_attempts', 1);
        $data['assessment_access_expires_at'] = filled($data['assessment_access_expires_at'] ?? null)
            ? $data['assessment_access_expires_at']
            : null;
        unset($data['assessment_duration_hours']);
        unset($data['test_status_control']);

        $package = $data['question_package_id']
            ? QuestionPackage::find($data['question_package_id'])
            : null;
        $data['operator_assessment_category_id'] = $this->supportsInviteCategory($package?->type)
            ? ($data['operator_assessment_category_id'] ?? null)
            : null;
        $data['site'] = $this->supportsInviteSite($package?->type) || $data['role'] !== User::ROLE_USER
            ? Str::of($data['site'] ?? '')->squish()->toString()
            : null;
        $data['site'] = $data['site'] !== '' ? $data['site'] : null;
        $data['site'] = $this->siteForAdmin($adminUser, $data['site']);
        $data['segment_config'] = AssessmentSegmentConfig::forPackage($package, $data['segment_config'] ?? null);

        return $data;
    }

    private function ensurePackageMatchesType(?int $packageId, string $type): void
    {
        if (! $packageId) {
            return;
        }

        $package = QuestionPackage::find($packageId);
        if (! $package || $package->type !== $type) {
            throw ValidationException::withMessages([
                'question_package_id' => 'Paket soal harus sesuai dengan tipe peserta.',
            ]);
        }
    }

    /**
     * @param  string|array<int, string>|null  $type
     */
    private function supportsInviteCategory(string|array|null $type): bool
    {
        $types = is_array($type) ? $type : [$type];

        return count(array_intersect($types, [
            QuestionPackage::TYPE_MEKANIK,
            QuestionPackage::TYPE_OPERATOR,
        ])) > 0;
    }

    /**
     * @param  string|array<int, string>|null  $type
     */
    private function supportsInviteSite(string|array|null $type): bool
    {
        $types = is_array($type) ? $type : [$type];

        return count(array_intersect($types, [
            QuestionPackage::TYPE_MEKANIK,
            QuestionPackage::TYPE_OPERATOR,
            QuestionPackage::TYPE_SHE,
            QuestionPackage::TYPE_HR,
        ])) > 0;
    }

    private function siteForAdmin(User $adminUser, ?string $site): ?string
    {
        return $adminUser->hasSiteRestriction()
            ? $adminUser->normalizedSite()
            : $site;
    }

    private function testStatusFor(User $user): string
    {
        if ($user->role !== User::ROLE_USER) {
            return 'keep';
        }

        if ($user->assessments()->whereNotNull('submitted_at')->exists()) {
            return 'submitted';
        }

        if ($user->assessments()
            ->whereNotNull('blocked_at')
            ->whereNull('submitted_at')
            ->where(function ($query): void {
                $query->whereNull('unlocked_at')
                    ->orWhereColumn('unlocked_at', '<', 'blocked_at');
            })
            ->exists()) {
            return 'blocked';
        }

        if ($user->assessments()->whereNull('submitted_at')->exists()) {
            return 'running';
        }

        return 'not_started';
    }

    private function syncUserAssessmentStatus(User $user, ?string $status): void
    {
        if ($user->role !== User::ROLE_USER || blank($status) || $status === 'keep') {
            return;
        }

        if ($status === 'not_started') {
            $user->assessments()->delete();
            ActivityLog::log('user_assessment_status_reset', 'Mereset status assessment user '.$user->email.' ke Belum Mengerjakan', User::class, $user->id);

            return;
        }

        $assessment = $user->assessments()
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if (! $assessment) {
            $assessment = $user->assessments()->create([
                'question_package_id' => $user->question_package_id,
                'operator_assessment_category_id' => $user->operator_assessment_category_id,
                'site' => $user->site,
                'status' => Assessment::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'duration_minutes' => $user->assessmentDurationMinutes(),
                'ends_at' => now()->addMinutes($user->assessmentDurationMinutes()),
                'total_questions' => 0,
                'correct_answers' => 0,
                'score' => 0,
            ]);
        }

        if ($status === 'submitted') {
            $assessment->update([
                'question_package_id' => $user->question_package_id,
                'operator_assessment_category_id' => $user->operator_assessment_category_id,
                'site' => $user->site,
                'status' => Assessment::STATUS_GRADED,
                'submitted_at' => now(),
                'blocked_at' => null,
                'block_reason' => null,
                'unlocked_at' => null,
            ]);
        } elseif ($status === 'blocked') {
            $assessment->update([
                'question_package_id' => $user->question_package_id,
                'operator_assessment_category_id' => $user->operator_assessment_category_id,
                'site' => $user->site,
                'status' => Assessment::STATUS_IN_PROGRESS,
                'submitted_at' => null,
                'blocked_at' => now(),
                'block_reason' => 'Diatur manual oleh admin.',
                'unlocked_at' => null,
            ]);
        } else {
            $assessment->update([
                'question_package_id' => $user->question_package_id,
                'operator_assessment_category_id' => $user->operator_assessment_category_id,
                'site' => $user->site,
                'status' => Assessment::STATUS_IN_PROGRESS,
                'submitted_at' => null,
                'blocked_at' => null,
                'block_reason' => null,
                'unlocked_at' => null,
            ]);
        }

        ActivityLog::log('user_assessment_status_update', 'Mengubah status assessment user '.$user->email.' menjadi '.$status, User::class, $user->id);
    }

    private function resetOpenAssessmentsThatNoLongerMatchUser(User $user): void
    {
        if ($user->role !== User::ROLE_USER) {
            return;
        }

        $user->assessments()
            ->whereNull('submitted_at')
            ->update(['site' => $user->site]);

        $staleAssessments = $user->assessments()
            ->whereNull('submitted_at')
            ->where(function ($query) use ($user): void {
                $query->where(function ($packageQuery) use ($user): void {
                    $this->whereNullableValueDiffers($packageQuery, 'question_package_id', $user->question_package_id);
                })->orWhere(function ($categoryQuery) use ($user): void {
                    $this->whereNullableValueDiffers($categoryQuery, 'operator_assessment_category_id', $user->operator_assessment_category_id);
                });
            })
            ->get();

        foreach ($staleAssessments as $assessment) {
            ActivityLog::log(
                'assessment_reset_after_user_update',
                'Mereset assessment aktif setelah assignment user '.$user->email.' diperbarui',
                $assessment::class,
                $assessment->id
            );

            $assessment->delete();
        }
    }

    private function whereNullableValueDiffers($query, string $column, mixed $value): void
    {
        if ($value === null) {
            $query->whereNotNull($column);

            return;
        }

        $query->where(function ($q) use ($column, $value): void {
            $q->where($column, '<>', $value)
                ->orWhereNull($column);
        });
    }

    private function authorizeSiteAccess(User $adminUser, User $targetUser): void
    {
        abort_if($targetUser->role !== User::ROLE_USER && ! $adminUser->canViewAllSites(), 403);

        abort_unless(
            $adminUser->canViewAllSites() || $targetUser->normalizedSite() === $adminUser->normalizedSite(),
            403
        );
    }

    /**
     * @return array{0: array<int, array{email:string, name:?string}>, 1: array<int, string>}
     */
    private function parseBulkInviteEmails(string $input): array
    {
        $items = [];
        $invalidRows = [];

        foreach (preg_split('/\R+/', $input) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $line, $matches);
            $emails = $matches[0] ?? [];

            if (count($emails) === 0) {
                $invalidRows[] = Str::limit($line, 80);
                continue;
            }

            if (count($emails) > 1) {
                foreach ($emails as $email) {
                    $items[] = [
                        'email' => strtolower($email),
                        'name' => null,
                    ];
                }
                continue;
            }

            $email = strtolower($emails[0]);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidRows[] = Str::limit($line, 80);
                continue;
            }

            $name = trim(str_replace($emails[0], '', $line), " \t\n\r\0\x0B<>,;:-\"'");

            $items[] = [
                'email' => $email,
                'name' => $name !== '' ? $name : null,
            ];
        }

        return [$items, $invalidRows];
    }

    private function nameFromEmail(string $email): string
    {
        $localPart = Str::before($email, '@');
        $name = Str::of($localPart)
            ->replace(['.', '_', '-'], ' ')
            ->squish()
            ->title()
            ->toString();

        return $name !== '' ? $name : 'Peserta '.Str::upper(Str::random(6));
    }

    /**
     * @return array{0: User, 1: bool}
     */
    private function createOrRefreshInvitedUser(
        string $email,
        ?string $name,
        ?int $packageId,
        ?QuestionPackage $package,
        ?int $operatorCategoryId,
        ?string $site,
        string $password,
        int $accessDays,
        int $durationMinutes,
    ): array {
        $user = User::where('email', $email)->first();

        if ($user && $user->role !== User::ROLE_USER) {
            throw ValidationException::withMessages([
                'email' => 'Email sudah dipakai akun admin.',
            ]);
        }

        $attributes = [
            'name' => filled($name) ? $name : ($user?->name ?? $this->nameFromEmail($email)),
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_USER,
            'question_package_id' => $packageId,
            'operator_assessment_category_id' => $operatorCategoryId,
            'site' => $site,
            'assessment_access_expires_at' => now()->addDays($accessDays),
            'assessment_duration_minutes' => $durationMinutes,
            'segment_config' => AssessmentSegmentConfig::forPackage($package),
        ];

        if ($user) {
            $user->update($attributes);

            return [$user->fresh('questionPackage', 'operatorAssessmentCategory'), false];
        }

        return [User::create($attributes)->load('questionPackage', 'operatorAssessmentCategory'), true];
    }

    private function sendAssessmentInvite(User $user, string $password, int $accessDays, int $durationMinutes): void
    {
        Mail::send('emails.assessment-invite', [
            'user' => $user,
            'password' => $password,
            'loginUrl' => route('login'),
            'accessDays' => $accessDays,
            'durationHours' => round($durationMinutes / 60, 2),
        ], function ($message) use ($user): void {
            $message->to($user->email, $user->name)
                ->subject('Undangan Assessment - Andalan HR');
        });
    }
}
