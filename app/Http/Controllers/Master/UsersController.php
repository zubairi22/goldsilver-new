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
        return Inertia::render('master/user/Index', [
            'users' => User::filter(Request::only('search'))->with('roles')->paginate(),
            'roles' => Role::all()
        ]);
    }

    public function store(UserCreateRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['password' => 'password']);
        $user->assignRole(Role::findById($request->input('role')));

        $this->flashSuccess('Tambah Pengguna Berhasil.');
        return Redirect::back();
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->update($request->only('name', 'email'));
        $user->syncRoles(Role::findById($request->input('role')));

        $this->flashSuccess('Update Pengguna Berhasil.');
        return Redirect::back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        $this->flashSuccess('Hapus Pengguna Berhasil.');
        return Redirect::back();
    }
}
