<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\MenuCreateRequest;
use App\Http\Requests\Menu\MenuUpdateRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class MenusController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('master/menu/Index', [
            'menus' => Menu::with('permissions')->paginate(15),
            'parents' => Menu::whereNull('parent_id')->pluck('title', 'id')
        ]);
    }

    public function store(MenuCreateRequest $request): RedirectResponse
    {
        $menu = Menu::create($request->validated());

        $permissionNames = $request->input('permissions', []);
        $permissionIds = [];
        foreach ($permissionNames as $permissionName) {
            $permission = Permission::create(['name' => $permissionName]);
            $permissionIds[] = $permission->id;
        }
        $menu->permissions()->sync($permissionIds);

        $this->flashSuccess('Tambah Menu Berhasil.');
        return Redirect::back();
    }

    public function update(MenuUpdateRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validated());
        $permissionNames = $request->input('permissions', []);

        $permissionIds = [];
        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $permissionIds[] = $permission->id;
        }
        $menu->permissions()->sync($permissionIds);

        $this->flashSuccess('Update Menu Berhasil.');
        return Redirect::back();
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        $this->flashSuccess('Hapus Menu Berhasil.');
        return Redirect::back();
    }
}
