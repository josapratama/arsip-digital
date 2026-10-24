<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'jabatan'     => $user->jabatan,
                    'locale'      => $user->locale,
                    'theme'       => $user->theme,
                    'roles'       => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_admin'    => $user->hasRole('admin'),
                ] : null,
            ],

            'locale' => app()->getLocale(),

            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],

            // Current route name — used by topbar for dynamic page title
            'route_name' => $request->route()?->getName(),

            // Registration feature status
            'can_register' => fn() => (bool) (\App\Models\SiteSetting::get('enable_registration', '0')),

            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
