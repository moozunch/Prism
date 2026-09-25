<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Filament Resources
        |--------------------------------------------------------------------------
        */
        $resources = [
            'project',
            'ticket',
            'ticket_priority',
            'ticket_comment',
            'notification',
            'user',
        ];

        $actions = [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Permissions
        |--------------------------------------------------------------------------
        */
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    [
                        'name' => "{$action}_{$resource}",
                        'guard_name' => 'web',
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create Roles
        |--------------------------------------------------------------------------
        */
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $member = Role::firstOrCreate([
            'name' => 'member',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        | Super Admin mendapatkan semua permission.
        */
        $superAdmin->syncPermissions(
            Permission::all()
        );

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        | Admin mendapatkan semua permission kecuali delete user.
        */
        $adminPermissions = Permission::whereNotIn(
            'name',
            ['delete_user']
        )->get();

        $admin->syncPermissions(
            $adminPermissions
        );

        /*
        |--------------------------------------------------------------------------
        | MEMBER
        |--------------------------------------------------------------------------
        | Member hanya dapat melihat data dan update ticket
        | untuk kebutuhan board / drag & drop.
        */
        $memberPermissions = Permission::whereIn('name', [
            'view_project',
            'view_any_project',

            'view_ticket',
            'view_any_ticket',
            'update_ticket',

            'view_ticket_priority',
            'view_any_ticket_priority',

            'view_ticket_comment',
            'view_any_ticket_comment',

            'view_notification',
            'view_any_notification',
        ])->get();

        $member->syncPermissions(
            $memberPermissions
        );
    }
}