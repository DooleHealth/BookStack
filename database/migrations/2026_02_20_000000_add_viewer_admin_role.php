<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find the existing Viewer role
        $viewerRole = DB::table('roles')->where('display_name', 'Viewer')->first();

        if (!$viewerRole) {
            return;
        }

        // Create the Viewer-Admin role
        $viewerAdminId = DB::table('roles')->insertGetId([
            'display_name'   => 'Viewer-Admin',
            'description'    => 'User can view admin-books & normal-books & their content behind authentication',
            'system_name'    => 'viewer_admin',
            'external_auth_id' => '',
            'mfa_enforced'   => false,
            'created_at'     => Carbon::now()->toDateTimeString(),
            'updated_at'     => Carbon::now()->toDateTimeString(),
        ]);

        // Copy all permissions from Viewer to Viewer-Admin
        $viewerPermissions = DB::table('permission_role')
            ->where('role_id', $viewerRole->id)
            ->get();

        $newPermissions = [];
        foreach ($viewerPermissions as $perm) {
            $newPermissions[] = [
                'permission_id' => $perm->permission_id,
                'role_id'       => $viewerAdminId,
            ];
        }

        if (!empty($newPermissions)) {
            DB::table('permission_role')->insert($newPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = DB::table('roles')->where('display_name', 'Viewer-Admin')->first();

        if ($role) {
            DB::table('permission_role')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }
    }
};
