<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\QuestionPackage;
use App\Models\User;
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
            ->with('questionPackage')
            ->withCount('assessments')
            ->when(! $adminUser->isSuperAdmin(), function ($query) use ($visibleTypes): void {
                $query->where(function ($q) use ($visibleTypes): void {
                    $q->where('role', 'user')
                        ->whereIn('question_package_id', function ($subQuery) use ($visibleTypes): void {
                            $subQuery->select('id')->from('question_packages')->whereIn('type', $visibleTypes);
                        });
                });
            })
            ->when($adminUser->isSuperAdmin() && $selectedType, function ($query) use ($selectedType): void {
                $query->where(function ($q) use ($selectedType): void {
                    $q->where('role', 'user')
                        ->whereIn('question_package_id', function ($subQuery) use ($selectedType): void {
                            $subQuery->select('id')->from('question_packages')->where('type', $selectedType);
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
                $query->where('question_package_id', $request->integer('package'));
            })
            ->when($request->filled('role'), function ($query) use ($request): void {
                $query->where('role', $request->string('role'));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $packages = QuestionPackage::where('is_active', true)
            ->whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users', 'packages', 'selectedType'));
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

        $adminUser = $request->user();
        $packagesByName = QuestionPackage::whereIn('type', $adminUser->visiblePackageTypes())->pluck('id', 'name');
        $created = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($emailIndex === false || ! isset($row[$emailIndex]) || ! filter_var(trim($row[$emailIndex]), FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $email = strtolower(trim($row[$emailIndex]));

            if (User::where('email', $email)->exists()) {
                $errors[] = "Email {$email} sudah terdaftar.";
                continue;
            }

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

            $packageId = null;
            if ($packageIndex !== false && isset($row[$packageIndex])) {
                $packageName = trim($row[$packageIndex]);
                $packageId = $packagesByName[$packageName] ?? null;
            }

            $password = strtoupper(Str::random(4));
            $accessDays = (int) config('assessment.default_access_days', 7);
            $durationMinutes = (int) config('assessment.default_duration_minutes', 120);

            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'user',
                'question_package_id' => $packageId,
                'assessment_access_expires_at' => now()->addDays($accessDays),
                'assessment_duration_minutes' => $durationMinutes,
            ]);

            try {
                $user = User::where('email', $email)->first();
                $this->sendAssessmentInvite($user, $password, $accessDays, $durationMinutes);
            } catch (\Throwable $e) {
                $errors[] = "Gagal kirim email ke {$email}.";
            }

            $created++;
        }

        fclose($handle);

        $message = "Berhasil mengundang {$created} peserta.";
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

        $availableTypes = collect($visibleTypes)->map(fn ($t) => [
            'value' => $t,
            'label' => QuestionPackage::typeLabel($t),
        ])->toArray();

        return view('admin.users.invite', compact('packages', 'visibleTypes', 'availableTypes'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'type' => ['required', 'string', 'in:'.implode(',', $visibleTypes)],
            'question_package_id' => [
                'nullable',
                'integer',
                Rule::exists('question_packages', 'id')->where(function ($query) use ($visibleTypes): void {
                    $query->whereIn('type', $visibleTypes);
                }),
            ],
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

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => $password,
            'role' => 'user',
            'question_package_id' => $data['question_package_id'] ?? null,
            'assessment_access_expires_at' => now()->addDays($accessDays),
            'assessment_duration_minutes' => $durationMinutes,
        ]);

        $user->load('questionPackage');

        $this->sendAssessmentInvite($user, $password, $accessDays, $durationMinutes);

        ActivityLog::log('user_invite', 'Mengundang user '.$data['email'], User::class, $user->id);

        return redirect()
            ->route('admin.invite')
            ->with('status', "Undangan peserta dibuat dan dikirim. Nama: {$name}");
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
        $created = 0;
        $sent = 0;
        $errors = [];
        $seen = [];

        if ($invalidRows) {
            $errors[] = count($invalidRows).' baris email tidak valid: '.implode(', ', array_slice($invalidRows, 0, 3));
        }

        foreach ($parsedEmails as $item) {
            $email = $item['email'];

            if (isset($seen[$email])) {
                $errors[] = "Email {$email} duplikat di daftar.";
                continue;
            }
            $seen[$email] = true;

            if (User::where('email', $email)->exists()) {
                $errors[] = "Email {$email} sudah terdaftar.";
                continue;
            }

            $password = Str::upper(Str::random(4));
            $name = $item['name'] ?: $this->nameFromEmail($email);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => User::ROLE_USER,
                'question_package_id' => $packageId,
                'assessment_access_expires_at' => now()->addDays($accessDays),
                'assessment_duration_minutes' => $durationMinutes,
            ]);

            try {
                $this->sendAssessmentInvite($user, $password, $accessDays, $durationMinutes);
                $sent++;
            } catch (\Throwable $e) {
                $errors[] = "Akun {$email} dibuat, tapi email gagal dikirim.";
            }

            ActivityLog::log('user_invite_bulk', 'Mengundang user '.$email, User::class, $user->id);
            $created++;
        }

        $message = "Berhasil membuat {$created} akun peserta dan mengirim {$sent} email undangan.";
        if ($errors) {
            $message .= ' Catatan: '.implode(', ', array_slice($errors, 0, 5));
        }

        return redirect()->route('admin.invite')->with('status', $message);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $adminUser = request()->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $user = new User(['role' => User::ROLE_USER]);
        $user->assessment_access_expires_at = now()->addDays((int) config('assessment.default_access_days', 7));
        $user->assessment_duration_minutes = (int) config('assessment.default_duration_minutes', 120);
        $user->max_attempts = (int) config('assessment.max_attempts', 1);
        $packages = QuestionPackage::where('is_active', true)
            ->whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();

        return view('admin.users.create', compact('user', 'packages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
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
        return redirect()->route('admin.users.edit', $user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $adminUser = request()->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $packages = QuestionPackage::whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();

        return view('admin.users.edit', compact('user', 'packages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        ActivityLog::log('user_update', 'Mengupdate user '.$user->email, User::class, $user->id);

        return redirect()->route('admin.users.index')->with('status', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('status', 'Admin yang sedang login tidak bisa menghapus akunnya sendiri.');
        }

        ActivityLog::log('user_delete', 'Menghapus user '.$user->email.' ('.$user->name.')', User::class, $user->id);

        $user->delete();

        return back()->with('status', 'User berhasil dihapus.');
    }

    public function answers(Request $request, User $user): View
    {
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
            'assessment_access_expires_at' => ['nullable', 'date'],
            'assessment_duration_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'segment_config' => ['nullable', 'array'],
            'segment_config.*.type' => ['required_with:segment_config', 'string', 'in:multiple_choice,essay,upload'],
            'segment_config.*.duration' => ['required_with:segment_config', 'integer', 'min:1', 'max:480'],
        ]);

        $data['role'] = $data['role'] ?? User::ROLE_USER;
        $data['question_package_id'] = $data['question_package_id'] ?? null;
        $data['assessment_duration_minutes'] = (int) round(((float) $data['assessment_duration_hours']) * 60);
        $data['assessment_access_expires_at'] = filled($data['assessment_access_expires_at'] ?? null)
            ? $data['assessment_access_expires_at']
            : null;
        unset($data['assessment_duration_hours']);

        $segmentConfig = null;
        if (! empty($data['segment_config'])) {
            $segmentConfig = collect($data['segment_config'])
                ->filter(fn ($s) => ! empty($s['type']) && ! empty($s['duration']))
                ->values()
                ->toArray();
            if (empty($segmentConfig)) {
                $segmentConfig = null;
            }
        }
        $data['segment_config'] = $segmentConfig;

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
