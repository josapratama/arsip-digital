<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('roles')
            ->withCount('documents')
            ->orderBy('id', 'asc');

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('jabatan', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->paginate(12)->withQueryString()->through(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'jabatan' => $u->jabatan,
            'role' => $u->roles->first()?->name ?? 'sekdes-operator',
            'role_label' => match($u->roles->first()?->name) {
                'admin' => 'Administrator',
                'kepala-desa' => 'Kepala Desa',
                'sekdes-operator' => 'Sekdes / TU / Operator',
                'petugas-kesejahteraan' => 'Kasi Kesejahteraan',
                'petugas-pemerintahan' => 'Kasi Pemerintahan',
                default => $u->roles->first()?->name ?? 'Staff',
            },
            'is_active' => (bool) $u->is_active,
            'documents_count' => $u->documents_count,
            'created_at' => $u->created_at->format('d M Y'),
        ]);

        $roles = [
            ['name' => 'admin', 'label' => 'Administrator'],
            ['name' => 'kepala-desa', 'label' => 'Kepala Desa'],
            ['name' => 'sekdes-operator', 'label' => 'Sekdes / TU / Operator'],
            ['name' => 'petugas-kesejahteraan', 'label' => 'Kasi Kesejahteraan'],
            ['name' => 'petugas-pemerintahan', 'label' => 'Kasi Pemerintahan'],
        ];

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['q', 'role', 'status']),
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'jabatan' => 'required|string|max:255',
            'role' => 'required|string|exists:roles,name',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'jabatan' => $request->jabatan,
            'password' => Hash::make($request->password),
            'locale' => 'id',
            'theme' => 'light',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($request->role);

        ActivityLog::record('settings', "Menambahkan staf baru: {$user->name} ({$user->jabatan})");

        return back()->with('success', "Akun staf {$user->name} berhasil ditambahkan.");
    }

    public function update(Request $request, $user)
    {
        $user = $user instanceof User ? $user : User::findOrFail($user);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
            'jabatan' => 'required|string|max:255',
            'role' => 'required|string|exists:roles,name',
            'password' => 'nullable|string|min:8',
            'is_active' => 'required|boolean',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'jabatan' => $request->jabatan,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        ActivityLog::record('settings', "Memperbarui profil staf: {$user->name}");

        return back()->with('success', "Data akun {$user->name} berhasil diperbarui.");
    }

    public function destroy($user)
    {
        $user = $user instanceof User ? $user : User::findOrFail($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->documents()->count() > 0) {
            return back()->with('error', "Pengguna {$user->name} tidak dapat dihapus karena memiliki arsip dokumen yang pernah diunggah. Anda dapat menonaktifkan akunnya.");
        }

        $name = $user->name;
        $user->roles()->detach();
        $user->delete();

        ActivityLog::record('settings', "Menghapus akun staf: {$name}");

        return back()->with('success', "Akun {$name} berhasil dihapus.");
    }
}
