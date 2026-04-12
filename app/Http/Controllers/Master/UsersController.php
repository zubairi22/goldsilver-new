<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserCreateRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $query = User::filter(Request::only('search'))->with('roles');

        if ($user->hasRole('admin gold')) {
            $query->role('cashier gold');
            $roles = Role::whereIn('name', ['cashier gold'])->get();
        } elseif ($user->hasRole('admin silver')) {
            $query->role('cashier silver');
            $roles = Role::whereIn('name', ['cashier silver'])->get();
        } else {
            $roles = Role::all();
        }

        return Inertia::render('master/user/Index', [
            'users' => $query->paginate(),
            'roles' => $roles,
            'can' => [
                'delete' => $user->hasRole('super-admin'),
            ]
        ]);
    }

    public function store(UserCreateRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());
        $user->assignRole(Role::findById($request->input('role')));

        $this->flashSuccess('Tambah Pengguna Berhasil.');
        return Redirect::back();
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->update($request->safe()->except('password') + ($request->filled('password') ? ['password' => $request->password] : []));
        $user->syncRoles(Role::findById($request->input('role')));

        $this->flashSuccess('Update Pengguna Berhasil.');
        return Redirect::back();
    }

    public function destroy(User $user): RedirectResponse
    {
        if (!auth()->user()->hasRole('super-admin')) {
            $this->flashError('Anda tidak memiliki akses untuk menghapus pengguna.');

            return back();
        }

        $user->delete();

        $this->flashSuccess('Hapus Pengguna Berhasil.');
        return Redirect::back();
    }
}
