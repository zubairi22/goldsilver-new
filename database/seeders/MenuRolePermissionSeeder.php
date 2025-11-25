<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MenuRolePermissionSeeder extends Seeder
{
    public function run(): void
    {

        $permissions = [
            ['id' => 1, 'name' => 'view dashboard', 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'manage users', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'manage roles', 'guard_name' => 'web'],
            ['id' => 4, 'name' => 'manage store settings', 'guard_name' => 'web'],
            ['id' => 5, 'name' => 'manage sales', 'guard_name' => 'web'],
            ['id' => 6, 'name' => 'manage buyback', 'guard_name' => 'web'],

            ['id' => 7, 'name' => 'manage item types', 'guard_name' => 'web'],
            ['id' => 8, 'name' => 'manage items', 'guard_name' => 'web'],
        ];

        Permission::insert($permissions);

        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::all());

        $admin = User::firstOrCreate(
            ['email' => 'admin@temantekno.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole($role);

        $menus = [
            ['id' => 1,  'title' => 'Dashboard', 'url' => 'dashboard', 'parent_id' => null, 'icon' => 'LayoutDashboard', 'sort' => 1],
            ['id' => 2,  'title' => 'Master', 'url' => 'master', 'parent_id' => null, 'icon' => 'Database', 'sort' => 10],
            ['id' => 3,  'title' => 'Pengguna', 'url' => 'master.users.index', 'parent_id' => 2, 'icon' => 'UsersRound', 'sort' => 1],
            ['id' => 4,  'title' => 'Peran', 'url' => 'master.roles.index', 'parent_id' => 2, 'icon' => 'UserCog', 'sort' => 2],

            ['id' => 5,  'title' => 'Manajemen Toko', 'url' => 'store', 'parent_id' => null, 'icon' => 'Warehouse', 'sort' => 9],
            ['id' => 6,  'title' => 'Pengaturan', 'url' => 'store.settings.index', 'parent_id' => 5, 'icon' => 'Settings2', 'sort' => 1],

            ['id' => 10, 'title' => 'Jenis Item', 'url' => 'store.item-types.index', 'parent_id' => 5, 'icon' => 'Tags', 'sort' => 2],
            ['id' => 11, 'title' => 'Daftar Item', 'url' => 'store.items.index', 'parent_id' => 5, 'icon' => 'PackageSearch', 'sort' => 3],

            ['id' => 7,  'title' => 'Transaksi', 'url' => 'transactions', 'parent_id' => null, 'icon' => 'ShoppingCart', 'sort' => 2],
            ['id' => 8,  'title' => 'Daftar Penjualan', 'url' => 'transactions.sales.index', 'parent_id' => 7, 'icon' => 'Receipt', 'sort' => 1],
            ['id' => 9,  'title' => 'Buyback', 'url' => 'transactions.buybacks.index', 'parent_id' => 7, 'icon' => 'RefreshCw', 'sort' => 2],
        ];

        Menu::insert($menus);

        $menuPermissions = [
            ['menu_id' => 1, 'permission_id' => 1],
            ['menu_id' => 2, 'permission_id' => 2],
            ['menu_id' => 2, 'permission_id' => 3],
            ['menu_id' => 3, 'permission_id' => 2],
            ['menu_id' => 4, 'permission_id' => 3],
            ['menu_id' => 5, 'permission_id' => 4],
            ['menu_id' => 6, 'permission_id' => 4],

            ['menu_id' => 10, 'permission_id' => 7],
            ['menu_id' => 11, 'permission_id' => 8],

            ['menu_id' => 7, 'permission_id' => 5],
            ['menu_id' => 8, 'permission_id' => 5],
            ['menu_id' => 9, 'permission_id' => 6],
        ];

        DB::table('menu_has_permissions')->insert($menuPermissions);
    }
}
