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

            ['id' => 9, 'name' => 'manage damaged', 'guard_name' => 'web'],
            ['id' => 10, 'name' => 'manage debts', 'guard_name' => 'web'],
            ['id' => 11, 'name' => 'manage customers', 'guard_name' => 'web'],
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

            ['id' => 7,  'title' => 'Transaksi Emas', 'url' => 'gold', 'parent_id' => null, 'icon' => 'ShoppingCart', 'sort' => 2],
            ['id' => 8,  'title' => 'Daftar Penjualan', 'url' => 'gold.transactions.sales.index', 'parent_id' => 7, 'icon' => 'Receipt', 'sort' => 1],
            ['id' => 9,  'title' => 'Buyback', 'url' => 'gold.buyback.index', 'parent_id' => 7, 'icon' => 'RefreshCw', 'sort' => 3],

            ['id' => 10, 'title' => 'Jenis Item', 'url' => 'store.item-types.index', 'parent_id' => 5, 'icon' => 'Tags', 'sort' => 2],
            ['id' => 11, 'title' => 'Daftar Item', 'url' => 'store.items.index', 'parent_id' => 5, 'icon' => 'PackageSearch', 'sort' => 3],

            ['id' => 12, 'title' => 'Barang Rusak', 'url' => 'gold.damaged.index', 'parent_id' => 7, 'icon' => 'PackageX', 'sort' => 4],
            ['id' => 13, 'title' => 'Penjualan', 'url' => 'gold.transactions.sales.create', 'parent_id' => 7, 'icon' => 'Store', 'sort' => 2],
            ['id' => 14, 'title' => 'Daftar Piutang', 'url' => 'gold.transactions.debts.index', 'parent_id' => 7, 'icon' => 'FileText', 'sort' => 5],

            ['id' => 15, 'title' => 'Pelanggan', 'url' => 'store.customers.index', 'parent_id' => 5, 'icon' => 'Users', 'sort' => 4],
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

            ['menu_id' => 7, 'permission_id' => 5],
            ['menu_id' => 8, 'permission_id' => 5],
            ['menu_id' => 9, 'permission_id' => 6],

            ['menu_id' => 10, 'permission_id' => 7],
            ['menu_id' => 11, 'permission_id' => 8],

            ['menu_id' => 12, 'permission_id' => 9],
            ['menu_id' => 13, 'permission_id' => 5],
            ['menu_id' => 14, 'permission_id' => 10],

            ['menu_id' => 15, 'permission_id' => 11],
        ];

        DB::table('menu_has_permissions')->insert($menuPermissions);
    }
}
