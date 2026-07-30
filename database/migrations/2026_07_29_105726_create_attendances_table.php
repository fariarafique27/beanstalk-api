<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // attendances
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('zkteco_users')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->date('attendance_date');
            $table->integer('total_minutes')->default(0);
            $table->string('status')->default('Present');
            $table->text('remarks')->nullable();
            $table->string('user_name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'attendance_date'], 'attendances_employee_date_unique');
            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};