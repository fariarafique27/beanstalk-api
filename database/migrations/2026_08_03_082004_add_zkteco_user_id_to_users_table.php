<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('zkteco_user_id')->nullable()->after('id')                               //Tells Laravel this foreign key points to the id column on the zkteco_users table.
            ->constrained('zkteco_users')->nullOnDelete();                                         //Important difference! Unlike cascadeOnDelete() which deletes the row, nullOnDelete() means that if the biometric record is deleted, this user account won't be deleted—instead, its zkteco_user_id column will simply be safely set back to NULL.
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropConstrainedForeignId('zkteco_user_id');
    });
}
};
