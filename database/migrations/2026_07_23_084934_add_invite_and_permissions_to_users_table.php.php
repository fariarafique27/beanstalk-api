<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->string('role')->default('admin')->after('email'); // 'super_admin' or 'admin'
            // $table->unsignedBigInteger('organization_id')->nullable()->after('role');
            // $table->json('permissions')->nullable()->after('organization_id'); // e.g. ["employees.manage", "attendance.manage"]
            // $table->string('invite_token', 64)->nullable()->after('permissions');
            // $table->timestamp('invite_token_expires_at')->nullable()->after('invite_token');
            // $table->timestamp('invited_at')->nullable()->after('invite_token_expires_at');
            // $table->boolean('is_active')->default(false)->after('invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'organization_id',
                'permissions',
                'invite_token',
                'invite_token_expires_at',
                'invited_at',
                'is_active'
            ]);
        });
    }
};  