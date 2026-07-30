<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('zkteco_user_id')->nullable()->constrained('zkteco_users')->nullOnDelete();
 
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
 
            // stores the raw enum name from the device, e.g. "CheckIn", "CheckOut"
            $table->string('check_in_punch_state')->nullable();
            $table->string('check_out_punch_state')->nullable();
 
            $table->string('check_in_latitude')->nullable();
            $table->string('check_in_longitude')->nullable();
            $table->string('check_out_latitude')->nullable();
            $table->string('check_out_longitude')->nullable();
            $table->text('check_in_address')->nullable();
            $table->text('check_out_address')->nullable();
            $table->string('check_in_ip')->nullable();
            $table->string('check_out_ip')->nullable();
 
            $table->integer('duration')->nullable(); // in minutes, filled in later if you add pairing/reporting
 
            $table->timestamps();
 
            // a given user can only have ONE row for a given exact check-in timestamp,
            // and ONE row for a given exact check-out timestamp -- this is what makes
            // re-running the sync safe against duplicates.
            $table->unique(['zkteco_user_id', 'check_in_time'], 'attendance_logs_checkin_dedupe');
            $table->unique(['zkteco_user_id', 'check_out_time'], 'attendance_logs_checkout_dedupe');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
 