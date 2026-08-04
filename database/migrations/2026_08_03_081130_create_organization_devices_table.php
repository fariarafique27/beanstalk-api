<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_devices', function (Blueprint $table) {                                //Tells the database to create a brand new table named organization_devices. The $table variable inside the closure block is used to add columns to this table.
            $table->id();                                                                                   //Creates an auto-incrementing primary key column named id (type BIGINT, unsigned). This uniquely identifies every single row in your table.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();                         //Creates an unsigned big integer column named organization_id that points to the id column on the organizations table.---constrained(): Automatically tells Laravel to look for an organizations table and link them safely.--------cascadeOnDelete(): If the parent organization is deleted from the database, all devices tied to it are automatically deleted as well
            $table->string('ip');                                                                           //Creates a VARCHAR (string) column named ip to store the IP address of the device
            $table->unsignedInteger('port')->default(4370);                                                 //Creates an unsigned integer (positive numbers only) column named port with a default value of 4370.
            $table->text('password')->nullable();                                                           //Stores the device's comm password, encrypted at the model level (via an 'encrypted' cast) — never saved as plain text. Nullable since most devices don't need one.
            $table->string('name')->nullable();                                                             //Creates a string column named name to optionally label or title the device (e.g., "Front Entrance Device").--------nullable() means this field is optional; it's okay if a device doesn't have a name.
            $table->boolean('is_active')->default(true);                                                    //Creates a boolean (true/false) column named is_active to track whether the device is currently enabled or disabled. It defaults to true when a new record is created.
            $table->timestamp('last_synced_at')->nullable();                                                //Creates a datetime stamp column named last_synced_at to record the exact date and time the device last synchronized attendance logs.
            $table->timestamps();                                                                           //Automatically creates two timestamp columns: created_at (when the row was added) and updated_at (when the row was last modified). Laravel manages these automatically.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_devices');
    }
};