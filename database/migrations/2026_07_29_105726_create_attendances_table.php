<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            // $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('organization_id');
            $table->date('attendance_date');
            $table->integer('total_minutes')->nullable()->comment('total worked minutes across all sessions');
            $table->enum('status', ['present', 'absent', 'half_day', 'late'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast searching and filtering
            $table->index(['employee_id', 'attendance_date']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};