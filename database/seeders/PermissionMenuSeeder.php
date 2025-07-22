<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['id' => 1, 'name' => 'view dashboard', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'view user', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'create user', 'guard_name' => 'web'],
            ['id' => 4, 'name' => 'update user', 'guard_name' => 'web'],
            ['id' => 5, 'name' => 'delete user', 'guard_name' => 'web'],
            ['id' => 6, 'name' => 'view menu', 'guard_name' => 'web'],
            ['id' => 7, 'name' => 'create menu', 'guard_name' => 'web'],
            ['id' => 8, 'name' => 'update menu', 'guard_name' => 'web'],
            ['id' => 9, 'name' => 'delete menu', 'guard_name' => 'web'],
            ['id' => 10, 'name' => 'view role', 'guard_name' => 'web'],
            ['id' => 11, 'name' => 'create role', 'guard_name' => 'web'],
            ['id' => 12, 'name' => 'update role', 'guard_name' => 'web'],
            ['id' => 13, 'name' => 'delete role', 'guard_name' => 'web'],
            ['id' => 14, 'name' => 'view product', 'guard_name' => 'web'],
            ['id' => 15, 'name' => 'create product', 'guard_name' => 'web'],
            ['id' => 16, 'name' => 'update product', 'guard_name' => 'web'],
            ['id' => 17, 'name' => 'delete product', 'guard_name' => 'web'],
            ['id' => 18, 'name' => 'view transaction', 'guard_name' => 'web'],
            ['id' => 19, 'name' => 'create transaction', 'guard_name' => 'web'],
            ['id' => 20, 'name' => 'view sales', 'guard_name' => 'web'],
        ];

        Permission::insert($permissions);
        $permissions = Permission::all();

        $role = Role::create(['name' => 'super-admin']);
        $role->syncPermissions($permissions);

        $admin = User::factory()->create([
            'name' => 'Super-Admin User',
            'email' => 'superadmin@example.com',
        ]);
        $admin->assignRole($role);

        $roleUser = Role::create(['name' => 'User']);
        $roleUser->syncPermissions([1]);

        $user = User::factory()->create([
            'name' => 'User',
            'email' => 'user@example.com',
        ]);
        $user->assignRole($roleUser);

        $menus = [
            ['id' => 1, 'title' => 'Dashboard', 'url' => 'dashboard', 'parent_id' => null, 'icon' => 'LayoutDashboard', 'sort' => 1],
            ['id' => 2, 'title' => 'Master', 'url' => 'master', 'parent_id' => null, 'icon' => 'Settings2', 'sort' => 10],
            ['id' => 3, 'title' => 'Pengguna', 'url' => 'master.users.index', 'parent_id' => 2, 'icon' => 'UsersRound', 'sort' => 1],
            ['id' => 4, 'title' => 'Menu', 'url' => 'master.menus.index', 'parent_id' => 2, 'icon' => 'Logs', 'sort' => 2],
            ['id' => 5, 'title' => 'Peran', 'url' => 'master.roles.index', 'parent_id' => 2, 'icon' => 'UserRoundCog', 'sort' => 3],
            ['id' => 6, 'title' => 'Produk', 'url' => 'products.index', 'parent_id' => null, 'icon' => 'Package', 'sort' => 4],
            ['id' => 7, 'title' => 'Transaksi', 'url' => 'transactions.index', 'parent_id' => null, 'icon' => 'ArrowLeftRight', 'sort' => 3],
            ['id' => 8, 'title' => 'Penjualan', 'url' => 'sales.index', 'parent_id' => null, 'icon' => 'Store', 'sort' => 2],
        ];

        Menu::insert($menus);

        $menuPermissions = [
            ['id' => 1, 'menu_id' => 1, 'permission_id' => 1],
            ['id' => 2, 'menu_id' => 2, 'permission_id' => 2],
            ['id' => 3, 'menu_id' => 2, 'permission_id' => 6],
            ['id' => 4, 'menu_id' => 2, 'permission_id' => 10],
            ['id' => 5, 'menu_id' => 3, 'permission_id' => 2],
            ['id' => 6, 'menu_id' => 3, 'permission_id' => 3],
            ['id' => 7, 'menu_id' => 3, 'permission_id' => 4],
            ['id' => 8, 'menu_id' => 3, 'permission_id' => 5],
            ['id' => 9, 'menu_id' => 4, 'permission_id' => 6],
            ['id' => 10, 'menu_id' => 4, 'permission_id' => 7],
            ['id' => 11, 'menu_id' => 4, 'permission_id' => 8],
            ['id' => 12, 'menu_id' => 4, 'permission_id' => 9],
            ['id' => 13, 'menu_id' => 5, 'permission_id' => 10],
            ['id' => 14, 'menu_id' => 5, 'permission_id' => 11],
            ['id' => 15, 'menu_id' => 5, 'permission_id' => 12],
            ['id' => 16, 'menu_id' => 5, 'permission_id' => 13],
            ['id' => 17, 'menu_id' => 6, 'permission_id' => 14],
            ['id' => 18, 'menu_id' => 6, 'permission_id' => 15],
            ['id' => 19, 'menu_id' => 6, 'permission_id' => 16],
            ['id' => 20, 'menu_id' => 6, 'permission_id' => 17],
            ['id' => 21, 'menu_id' => 7, 'permission_id' => 18],
            ['id' => 22, 'menu_id' => 7, 'permission_id' => 19],
            ['id' => 23, 'menu_id' => 8, 'permission_id' => 20],
        ];

        DB::table('menu_has_permissions')->insert($menuPermissions);
    }
}
