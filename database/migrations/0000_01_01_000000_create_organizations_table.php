<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('organization_invitations', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
        //     $table->string('email')->index();
        //     $table->string('token', 64)->unique();
        //     $table->timestamp('expires_at')->nullable();
        //     $table->timestamps();
        // });
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
