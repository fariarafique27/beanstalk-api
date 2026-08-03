<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zkteco_users', function (Blueprint $table) {                                                          //Schema::table instead of Schema::create. This tells Laravel to modify an existing table named zkteco_users rather than building a brand new one.
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();                 //Creates the column linked to organizations.----Allows existing rows to have a null value-Links it as a foreign key that safely deletes records if the parent organization is deleted.
        });

        Schema::table('zkteco_users', function (Blueprint $table) {
            $table->dropUnique('zkteco_users_uid_unique');                                                                   //Drops an old unique index from the table.
            $table->unique(['organization_id', 'uid']);                                                                      //Creates a composite unique constraint.
        });
    }

    public function down(): void
    {
        Schema::table('zkteco_users', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'uid']);
            $table->unique('uid');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};