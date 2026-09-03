<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeManageSites($request);

        $sites = Site::orderByRaw("CASE WHEN code = 'HO' THEN 0 ELSE 1 END")
            ->orderBy('code')
            ->get();

        return view('admin.sites.index', compact('sites'));
    }

    public function create(Request $request): View
    {
        return $this->index($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManageSites($request);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:sites,code', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'code.regex' => 'Kode site hanya boleh mengandung huruf, angka, strip (-), dan underscore (_).',
            'code.unique' => 'Kode site ini sudah digunakan.',
        ]);

        $data['code'] = strtoupper(trim($data['code']));

        $site = Site::create($data);

        ActivityLog::log('site_create', 'Membuat site baru: ' . $site->code . ' - ' . $site->name, Site::class, $site->id);

        return redirect()->route('admin.sites.index')->with('status', 'Site "' . $site->name . '" berhasil ditambahkan.');
    }

    public function edit(Request $request, Site $site): View
    {
        $this->authorizeManageSites($request);

        $sites = Site::orderByRaw("CASE WHEN code = 'HO' THEN 0 ELSE 1 END")
            ->orderBy('code')
            ->get();

        return view('admin.sites.index', compact('sites', 'site'));
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $this->authorizeManageSites($request);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('sites', 'code')->ignore($site), 'regex:/^[A-Za-z0-9\-_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Kode site hanya boleh mengandung huruf, angka, strip (-), dan underscore (_).',
        ]);

        $oldCode = $site->code;
        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');

        $site->update($data);

        // If the code changed, update all users and assessments referencing the old code
        if ($oldCode !== $data['code']) {
            \App\Models\User::where('site', $oldCode)->update(['site' => $data['code']]);
            \DB::table('assessments')->where('site', $oldCode)->update(['site' => $data['code']]);
        }

        ActivityLog::log('site_update', 'Mengupdate site: ' . $site->code, Site::class, $site->id);

        return redirect()->route('admin.sites.index')->with('status', 'Site "' . $site->name . '" berhasil diperbarui.');
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $this->authorizeManageSites($request);

        if ($site->isHO()) {
            return back()->with('status', 'Site HO (Head Office) tidak bisa dihapus.');
        }

        $userCount = \App\Models\User::where('site', $site->code)->count();
        if ($userCount > 0) {
            return back()->with('status', 'Tidak bisa menghapus site "' . $site->name . '" karena masih ada ' . $userCount . ' user yang terhubung. Nonaktifkan saja jika tidak ingin digunakan.');
        }

        ActivityLog::log('site_delete', 'Menghapus site: ' . $site->code . ' - ' . $site->name, Site::class, $site->id);

        $site->delete();

        return redirect()->route('admin.sites.index')->with('status', 'Site berhasil dihapus.');
    }

    private function authorizeManageSites(Request $request): void
    {
        abort_unless($request->user()?->canViewAllSites(), 403, 'Hanya admin HO yang bisa mengelola Master Site.');
    }
}
