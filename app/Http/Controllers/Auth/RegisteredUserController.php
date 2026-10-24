<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        if (!SiteSetting::get('enable_registration', '0')) {
            abort(404, 'Halaman pendaftaran tidak tersedia.');
        }

        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        if (!SiteSetting::get('enable_registration', '0')) {
            abort(404, 'Pendaftaran akun sedang dinonaktifkan.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'position' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $position = $request->position ?? $request->jabatan ?? '-';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'position' => $position,
            'jabatan' => $position,
            'password' => Hash::make($request->password),
            'locale' => 'id',
            'theme' => 'light',
            'is_active' => true,
        ]);

        // Assign standard staff role
        $user->assignRole('sekdes-operator');

        Auth::login($user);

        ActivityLog::record('register', "Pengguna baru mendaftar: {$user->name} ({$user->position})");

        return redirect(route('dashboard'))->with('success', 'Akun berhasil didaftarkan dan Anda telah masuk ke sistem.');
    }
}
