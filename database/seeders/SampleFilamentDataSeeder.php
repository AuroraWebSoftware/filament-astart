<?php

namespace Database\Seeders;

use AuroraWebSoftware\AAuth\Models\OrganizationNode;
use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\AAuth\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SampleFilamentDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('database.default') == 'pgsql') {
            $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' ORDER BY table_name;');

            $ignores = [
                'admin_setting', 'model_has_permissions', 'model_has_roles', 'password_resets',
                'role_has_permissions', 'sessions', 'cache', 'cache_locks', 'job_batches', 'password_reset_tokens'
            ];

            foreach ($tables as $table) {
                if (!in_array($table->table_name, $ignores)) {
                    $column = DB::selectOne("
                SELECT data_type FROM information_schema.columns
                WHERE table_name = '{$table->table_name}' AND column_name = 'id'
            ");

                    if ($column && in_array($column->data_type, ['integer', 'bigint'])) {
                        $seq = DB::table($table->table_name)->max('id') + 1;
                        DB::select('ALTER SEQUENCE ' . $table->table_name . '_id_seq RESTART WITH ' . $seq);
                    }
                }
            }
        }

        $user1 = User::create(
            [
                'name' => 'Example User 1',
                'email' => 'user1@example.com',
                'password' => Hash::make('password'),
            ]
        );

        $organizationScope1 = OrganizationScope::whereName('Root Scope')->first();

        $organizationScope2 = OrganizationScope::create([
            'name' => 'Sub-Scope',
            'level' => 10,
            'status' => 'active',
        ]);

        $organizationScope3 = OrganizationScope::create([
            'name' => 'Sub-Sub-Scope',
            'level' => 20,
            'status' => 'active',
        ]);

        $organizationNode1 = OrganizationNode::whereName('Root Node')->first();

        $organizationNode2 = OrganizationNode::create(
            [
                'organization_scope_id' => $organizationScope2->id,
                'name' => 'Organization Node 1.2',
                'model_type' => null,
                'model_id' => null,
                'path' => '1/temp',
                'parent_id' => $organizationNode1->id,
            ]
        );
        $organizationNode2->path = $organizationNode1->id . '/' . $organizationNode2->id;
        $organizationNode2->save();

        $organizationNode3 = OrganizationNode::create(
            [
                'organization_scope_id' => $organizationScope2->id,
                'name' => 'Organization Node 1.3',
                'model_type' => null,
                'model_id' => null,
                'path' => '1/temp',
                'parent_id' => $organizationNode1->id,
            ]
        );
        $organizationNode3->path = $organizationNode1->id . '/' . $organizationNode3->id;
        $organizationNode3->save();

        $organizationNode4 = OrganizationNode::create(
            [
                'organization_scope_id' => $organizationScope3->id,
                'name' => 'Organization Node 1.2.4',
                'model_type' => null,
                'model_id' => null,
                'path' => '1/temp',
                'parent_id' => $organizationNode2->id,
            ]
        );
        $organizationNode4->path = $organizationNode2->path . '/' . $organizationNode4->id;
        $organizationNode4->save();

        $role1 = Role::create([
            'type' => 'organization',
            'name' => 'System Admin',
            'status' => 'active',
        ]);

        DB::table('user_role_organization_node')->insert([
            'user_id' => $user1->id,
            'role_id' => $role1->id,
        ]);

        $permissions = [
            'user_view',
            'user_edit',
            'user_delete',
            'user_create',
            'user_update',
            'user_view_any',
            'organization_scope_view',
            'organization_scope_edit',
            'organization_scope_delete',
            'organization_scope_create',
            'organization_scope_update',
            'organization_scope_view_any',
            'organization_node_view',
            'organization_node_edit',
            'organization_node_delete',
            'organization_node_create',
            'organization_node_update',
            'organization_node_view_any',
            'role_view',
            'role_edit',
            'role_delete',
            'role_create',
            'role_update',
            'role_view_any',
        ];

        foreach ($permissions as $permission) {
            DB::table('role_permission')->insert([
                'role_id' => $role1->id,
                'permission' => $permission,
            ]);
        }

        if (config('database.default') == 'pgsql') {
            $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' ORDER BY table_name;');

            $ignores = [
                'admin_setting', 'model_has_permissions', 'model_has_roles', 'password_resets',
                'role_has_permissions', 'sessions', 'cache', 'cache_locks', 'job_batches', 'password_reset_tokens'
            ];

            foreach ($tables as $table) {
                if (!in_array($table->table_name, $ignores)) {
                    $column = DB::selectOne("
                SELECT data_type FROM information_schema.columns
                WHERE table_name = '{$table->table_name}' AND column_name = 'id'
            ");

                    if ($column && in_array($column->data_type, ['integer', 'bigint'])) {
                        $seq = DB::table($table->table_name)->max('id') + 1;
                        DB::select('ALTER SEQUENCE ' . $table->table_name . '_id_seq RESTART WITH ' . $seq);
                    }
                }
            }
        }
    }
}
