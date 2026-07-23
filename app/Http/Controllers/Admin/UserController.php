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
        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, ['mekanik', 'operator', 'she'])) {
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

        $packagesByName = QuestionPackage::pluck('id', 'name');

        $adminUser = $request->user();
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

            $type = $adminUser->isAdminMekanik() ? 'mekanik' : 'operator';
            if ($adminUser->isSuperAdmin() && $typeIndex !== false && isset($row[$typeIndex])) {
                $rawType = strtolower(trim($row[$typeIndex]));
                if (in_array($rawType, ['operator', 'mekanik'])) {
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
                Mail::send('emails.assessment-invite', [
                    'user' => $user,
                    'password' => $password,
                    'loginUrl' => route('login'),
                    'accessDays' => $accessDays,
                    'durationHours' => round($durationMinutes / 60, 2),
                ], function ($message) use ($email, $name): void {
                    $message->to($email, $name)
                        ->subject('Undangan Assessment - Andalan HR');
                });
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
            'label' => $t === 'she' ? 'SHE' : ucfirst($t),
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
            'question_package_id' => ['nullable', 'integer', 'exists:question_packages,id'],
            'access_days' => ['required', 'integer', 'min:1', 'max:365'],
            'duration_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'segment_config' => ['nullable', 'array'],
            'segment_config.*.type' => ['required_with:segment_config', 'string', 'in:multiple_choice,essay,upload'],
            'segment_config.*.duration' => ['required_with:segment_config', 'integer', 'min:1', 'max:480'],
        ]);

        $password = Str::upper(Str::random(4));
        $name = filled($data['name'] ?? null)
            ? $data['name']
            : 'Peserta '.Str::upper(Str::random(6));

        $accessDays = (int) $data['access_days'];
        $durationMinutes = (int) round(((float) $data['duration_hours']) * 60);

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

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => $password,
            'role' => 'user',
            'question_package_id' => $data['question_package_id'] ?? null,
            'assessment_access_expires_at' => now()->addDays($accessDays),
            'assessment_duration_minutes' => $durationMinutes,
            'segment_config' => $segmentConfig,
        ]);

        $user->load('questionPackage');

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

        ActivityLog::log('user_invite', 'Mengundang user '.$data['email'], User::class, $user->id);

        return redirect()
            ->route('admin.invite')
            ->with('status', "Undangan peserta dibuat dan dikirim. Nama: {$name}");
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

    private function validated(Request $request, ?User $user = null): array
    {
        $adminUser = $request->user();
        $passwordRules = $user
            ? ['nullable', 'confirmed', Rules\Password::defaults()]
            : ['required', 'confirmed', Rules\Password::defaults()];

        $roles = $adminUser->isSuperAdmin()
            ? ['nullable', 'string', 'in:user,admin_mekanik,admin_operation,admin_she,super_admin']
            : ['nullable', 'string', 'in:user,admin_mekanik,admin_operation,admin_she'];

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
            'question_package_id' => ['nullable', 'integer', 'exists:question_packages,id'],
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
}
