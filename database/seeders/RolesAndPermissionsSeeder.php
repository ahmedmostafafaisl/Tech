<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;




class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions per model
        $models = [
            'employees',
            'customers',
            'technicians',
            'addresses',
            'roles',
            'permissions',
            'appointments',
            'items',
            'parts',
            'stocks',
            'tasks',
            'transfers',
            'warehouses',
            'payments',
            'invoices',
            'announcements',
            'emergency_items',
            'periodic_items',
            'emergency_item_conditions'
        ];

        $permissions = [];
        foreach ($models as $model) {
            $permissions[] = Permission::firstOrCreate(['name' => "view $model"]);
            $permissions[] = Permission::firstOrCreate(['name' => "create $model"]);
            $permissions[] = Permission::firstOrCreate(['name' => "update $model"]);
            $permissions[] = Permission::firstOrCreate(['name' => "delete $model"]);
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $teamLeader = Role::firstOrCreate(['name' => 'team_leader']);
        $customerService = Role::firstOrCreate(['name' => 'customer_service']);
        // Assign all permissions to super_admin
        $superAdmin->syncPermissions(Permission::all());
        // Assign partial permissions to other roles (example)
        $admin->syncPermissions(Permission::whereIn('name', [
            'view appointments',
            'create appointments',
            'update appointments',
            'view items',
            'view parts'
        ])->get());

        $teamLeader->syncPermissions(Permission::whereIn('name', [
            'view tasks',
            'update tasks',
            'create tasks'
        ])->get());

        $customerService->syncPermissions(Permission::whereIn('name', [
            'view appointments',
            'create appointments'
        ])->get());

        // Create example users and assign roles with permissions
        $users = [
            ['username' => 'superadmin', 'email' => 'super@admin.com', 'role' => 'super_admin'],
            ['username' => 'adminuser', 'email' => 'admin@admin.com', 'role' => 'admin'],
            ['username' => 'teamlead', 'email' => 'lead@team.com', 'role' => 'team_leader'],
            ['username' => 'support', 'email' => 'support@service.com', 'role' => 'customer_service'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'username' => $userData['username'],
                    'password' => bcrypt('password'),
                    'role' => $userData['role'],
                    'type' => 'employee'
                ]
            );

            // Assign role to the user
            $user->assignRole($userData['role']);
            // Sync the permissions associated with the role
            $role = Role::findByName($userData['role']);
            $user->syncPermissions($role->permissions);
        }

        // Example for technician users (tech role)
        // $technicians = [
        //     ['username' => 'Faisal', 'email' => 'ahmed.mustafa@hs.sa', 'phone' => '+966500000000', 'tech_id' => '500000000'],
        //     ['username' => 'Khaled', 'email' => 'khalid.adel@hs.sa', 'phone' => '+966500000001', 'tech_id' => '500000001'],
        //     ['username' => 'Mamdouh', 'email' => 'dev.mamdouh@hs.sa', 'phone' => '+966500000002', 'tech_id' => '500000002'],
        //     ['username' => 'Osama', 'email' => 'osama.nuaman@hs.sa', 'phone' => '+966500000003', 'tech_id' => '500000003'],
        // ];

        // foreach ($technicians as $userData) {
        //     $user = User::firstOrCreate(
        //         ['email' => $userData['email']],
        //         [
        //             'username' => $userData['username'],
        //             'phone' => $userData['phone'],
        //             'tech_id' => $userData['tech_id'],
        //             'pin_code' => bcrypt('1234'),
        //             'type' => 'tech'
        //         ]
        //     );
        // }

        // for ($i = 1; $i <= 5; $i++) {
        //     User::create([
        //         'username' => 'customer' . $i,
        //         'email' => 'customer' . $i . '@example.com',
        //         'phone' => '050000000' . $i,
        //         'password' => bcrypt('password'), // default password
        //         'type' => 'customer',
        //         'image' => null,
        //         'status' => 'active',
        //     ]);
        // }
    }
}
