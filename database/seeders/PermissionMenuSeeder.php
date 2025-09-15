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
            ['id' => 14, 'name' => 'setting outlet', 'guard_name' => 'web'],
            ['id' => 15, 'name' => 'view product', 'guard_name' => 'web'],
            ['id' => 16, 'name' => 'create product', 'guard_name' => 'web'],
            ['id' => 17, 'name' => 'update product', 'guard_name' => 'web'],
            ['id' => 18, 'name' => 'delete product', 'guard_name' => 'web'],
            ['id' => 19, 'name' => 'view customer', 'guard_name' => 'web'],
            ['id' => 20, 'name' => 'create customer', 'guard_name' => 'web'],
            ['id' => 21, 'name' => 'update customer', 'guard_name' => 'web'],
            ['id' => 22, 'name' => 'delete customer', 'guard_name' => 'web'],
            ['id' => 23, 'name' => 'view cashier', 'guard_name' => 'web'],
            ['id' => 24, 'name' => 'view sales', 'guard_name' => 'web'],
            ['id' => 25, 'name' => 'create refund', 'guard_name' => 'web'],
            ['id' => 26, 'name' => 'view refund', 'guard_name' => 'web'],
            ['id' => 27, 'name' => 'view debts', 'guard_name' => 'web'],
            ['id' => 28, 'name' => 'settle debts', 'guard_name' => 'web'],
            ['id' => 29, 'name' => 'view unit', 'guard_name' => 'web'],
            ['id' => 30, 'name' => 'create unit', 'guard_name' => 'web'],
            ['id' => 31, 'name' => 'update unit', 'guard_name' => 'web'],
            ['id' => 32, 'name' => 'delete unit', 'guard_name' => 'web'],
            ['id' => 33, 'name' => 'view category', 'guard_name' => 'web'],
            ['id' => 34, 'name' => 'create category', 'guard_name' => 'web'],
            ['id' => 35, 'name' => 'update category', 'guard_name' => 'web'],
            ['id' => 36, 'name' => 'delete category', 'guard_name' => 'web'],

        ];

        Permission::insert($permissions);
        $permissions = Permission::all();

        $role = Role::create(['name' => 'super-admin']);
        $role->syncPermissions($permissions);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@tokomulia.com',
        ]);
        $admin->assignRole($role);

        $roleUser = Role::create(['name' => 'Kasir']);
        $roleUser->syncPermissions([1, 19, 20, 21, 23, 24, 25, 26]);

        $user = User::factory()->create([
            'name' => 'Kasir 1',
            'email' => 'kasir1@tokomulia.com',
        ]);
        $user->assignRole($roleUser);

        $user2 = User::factory()->create([
            'name' => 'Kasir 2',
            'email' => 'kasir2@tokomulia.com',
        ]);
        $user2->assignRole($roleUser);

        $user3 = User::factory()->create([
            'name' => 'Kasir 3',
            'email' => 'kasir3@tokomulia.com',
        ]);
        $user3->assignRole($roleUser);

        $menus = [
            ['id' => 1, 'title' => 'Dashboard', 'url' => 'dashboard', 'parent_id' => null, 'icon' => 'LayoutDashboard', 'sort' => 1],
            ['id' => 2, 'title' => 'Master', 'url' => 'master', 'parent_id' => null, 'icon' => 'Settings2', 'sort' => 10],
            ['id' => 3, 'title' => 'Pengguna', 'url' => 'master.users.index', 'parent_id' => 2, 'icon' => 'UsersRound', 'sort' => 1],
            ['id' => 4, 'title' => 'Menu', 'url' => 'master.menus.index', 'parent_id' => 2, 'icon' => 'Logs', 'sort' => 2],
            ['id' => 5, 'title' => 'Peran', 'url' => 'master.roles.index', 'parent_id' => 2, 'icon' => 'UserRoundCog', 'sort' => 3],
            ['id' => 6, 'title' => 'Manajemen Outlet', 'url' => 'outlet', 'parent_id' => null, 'icon' => 'Warehouse', 'sort' => 9],
            ['id' => 7, 'title' => 'Pengaturan', 'url' => 'outlet.settings.index', 'parent_id' => 6, 'icon' => 'Settings', 'sort' => 1],
            ['id' => 8, 'title' => 'Produk', 'url' => 'outlet.products.index', 'parent_id' => 6, 'icon' => 'Package', 'sort' => 2],
            ['id' => 9, 'title' => 'Pelanggan', 'url' => 'outlet.customers.index', 'parent_id' => 6, 'icon' => 'Users', 'sort' => 5],
            ['id' => 10, 'title' => 'Kasir', 'url' => 'cashier.index', 'parent_id' => null, 'icon' => 'Store', 'sort' => 1],
            ['id' => 11, 'title' => 'Transaksi', 'url' => 'transaction', 'parent_id' => null, 'icon' => 'ArrowLeftRight', 'sort' => 2],
            ['id' => 12, 'title' => 'Penjualan', 'url' => 'transaction.sales.index', 'parent_id' => 11, 'icon' => 'ShoppingCart', 'sort' => 1],
            ['id' => 13, 'title' => 'Piutang', 'url' => 'transaction.debts.index', 'parent_id' => 11, 'icon' => 'FileText', 'sort' => 2],
            ['id' => 14, 'title' => 'Refund', 'url' => 'transaction.refunds.index', 'parent_id' => 11, 'icon' => 'Undo2', 'sort' => 3],
            ['id' => 15, 'title' => 'Satuan', 'url' => 'outlet.units.index', 'parent_id' => 6, 'icon' => 'Package2', 'sort' => 3],
            ['id' => 16, 'title' => 'Kategori', 'url' => 'outlet.categories.index', 'parent_id' => 6, 'icon' => 'Package2', 'sort' => 4],
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
            ['id' => 19, 'menu_id' => 6, 'permission_id' => 19],
            ['id' => 20, 'menu_id' => 7, 'permission_id' => 14],
            ['id' => 21, 'menu_id' => 8, 'permission_id' => 15],
            ['id' => 22, 'menu_id' => 8, 'permission_id' => 16],
            ['id' => 23, 'menu_id' => 8, 'permission_id' => 17],
            ['id' => 24, 'menu_id' => 8, 'permission_id' => 18],
            ['id' => 25, 'menu_id' => 9, 'permission_id' => 19],
            ['id' => 26, 'menu_id' => 9, 'permission_id' => 20],
            ['id' => 27, 'menu_id' => 9, 'permission_id' => 21],
            ['id' => 28, 'menu_id' => 9, 'permission_id' => 22],
            ['id' => 29, 'menu_id' => 10, 'permission_id' => 23],
            ['id' => 30, 'menu_id' => 11, 'permission_id' => 24],
            ['id' => 31, 'menu_id' => 11, 'permission_id' => 26],
            ['id' => 32, 'menu_id' => 11, 'permission_id' => 27],
            ['id' => 33, 'menu_id' => 12, 'permission_id' => 24],
            ['id' => 34, 'menu_id' => 12, 'permission_id' => 25],
            ['id' => 35, 'menu_id' => 13, 'permission_id' => 26],
            ['id' => 36, 'menu_id' => 14, 'permission_id' => 27],
            ['id' => 37, 'menu_id' => 14, 'permission_id' => 28],
            ['id' => 38, 'menu_id' => 15, 'permission_id' => 29],
            ['id' => 39, 'menu_id' => 15, 'permission_id' => 30],
            ['id' => 40, 'menu_id' => 15, 'permission_id' => 31],
            ['id' => 41, 'menu_id' => 15, 'permission_id' => 32],
        ];

        DB::table('menu_has_permissions')->insert($menuPermissions);
    }
}
