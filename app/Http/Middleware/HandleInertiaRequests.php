<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $appName = StoreSetting::first()->name ?? config('app.name');

        $shared = [
            ...parent::share($request),
            'name' => $appName,
            'auth' => [
                'user' => $request->user(),
                'can' => $request->user() ? $request->user()->getPermissionsViaRoles()->pluck('name') : null,
                'isAdmin' => $request->user()->hasRole('super-admin'),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'message' => fn () => $request->session()->get('message'),
                'sale' => fn () => $request->session()->get('sale'),
            ],
            'og' => [
                'title' => $appName,
                'image' => public_path('/logo.png'),
            ]
        ];

        if (Auth::check()) {
            $userPermissions = Auth::user()->getAllPermissions()->pluck('id');

            $menus = Menu::whereNull('parent_id')
                ->whereHas('permissions', function ($query) use ($userPermissions) {
                    $query->whereIn('permissions.id', $userPermissions);
                })
                ->with(['children' => function ($query) use ($userPermissions) {
                    $query->whereHas('permissions', function ($query) use ($userPermissions) {
                        $query->whereIn('permissions.id', $userPermissions);
                    })->orderBy('sort');
                }])
                ->orderBy('sort')
                ->get();

            $shared['sideBarMenus'] = $menus;
        }

        return $shared;
    }
}
