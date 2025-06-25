<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleCreateRequest;
use App\Http\Requests\Role\RoleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('master/role/Index', [
            'roles' => Role::with('permissions')->paginate(15),
            'permissions' => Permission::pluck('name')
        ]);
    }

    public function store(RoleCreateRequest $request): RedirectResponse
    {
        $role = Role::create($request->validated());
        $role->syncPermissions($request->input('permissions'));

        $this->flashSuccess('Tambah Role Berhasil.');
        return Redirect::back();
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->validated());
        $role->syncPermissions($request->input('permissions'));

        $this->flashSuccess('Update Role Berhasil.');
        return Redirect::back();
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        $this->flashSuccess('Hapus Role Berhasil.');
        return Redirect::back();
    }
}
