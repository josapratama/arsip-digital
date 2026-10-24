<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('Profile/Edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'jabatan'  => 'nullable|string|max:100',
            'locale'   => 'required|in:id,en',
            'theme'    => 'required|in:light,dark',
        ]);

        $user = auth()->user();
        $position = $request->has('position') ? $request->position : $request->jabatan;
        $user->update([
            'name' => $request->name,
            'position' => $position,
            'locale' => $request->locale,
            'theme' => $request->theme,
        ]);

        app()->setLocale($request->locale);
        session(['locale' => $request->locale]);

        return back()->with('success', __('messages.profile_updated'));
    }

    /**
     * Update only theme or locale (no name required).
     * Used by the quick-toggle buttons in the header.
     */
    public function updatePreference(Request $request)
    {
        $request->validate([
            'locale' => 'sometimes|in:id,en',
            'theme'  => 'sometimes|in:light,dark',
        ]);

        $user = auth()->user();
        $data = $request->only(array_filter(['locale', 'theme'], fn($k) => $request->has($k)));
        $user->update($data);

        if ($request->has('locale')) {
            app()->setLocale($request->locale);
            session(['locale' => $request->locale]);
        }

        return back();
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        auth()->user()->update(['password' => $request->password]);

        return back()->with('success', __('messages.password_updated'));
    }
}
