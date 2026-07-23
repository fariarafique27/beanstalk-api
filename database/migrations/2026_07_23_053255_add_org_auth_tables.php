<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Link Users to Organizations
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->onDelete('cascade');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('org_admin')->after('email'); // 'super_admin' or 'org_admin'
            }
            if (!Schema::hasColumn('users', 'password_set_at')) {
                $table->timestamp('password_set_at')->nullable()->after('password');
            }
        });

        // 2. Organization Permissions Table
        if (!Schema::hasTable('organization_permissions')) {
            Schema::create('organization_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
                $table->string('module_key'); // e.g., 'employees', 'payroll', 'attendance', 'settings'
                $table->timestamps();
            });
        }

        // 3. Organization Invitations Table
        if (!Schema::hasTable('organization_invitations')) {
            Schema::create('organization_invitations', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
        Schema::dropIfExists('organization_permissions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'role', 'password_set_at']);
        });
    }
};