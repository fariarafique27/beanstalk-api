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
        Schema::create('zkteco_users', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique(); // The internal UID from the device
            $table->string('user_id')->unique(); // The badge/ID number typed on device
            $table->string('name');
            $table->unsignedInteger('role')->default(0); // Privilege value (e.g., 0, 14)
            $table->string('cardno')->nullable(); // Card number if assigned
            $table->string('password')->nullable(); // Device password if set
            $table->integer('group_id')->default(0); // Group ID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zkteco_users');
    }
};
