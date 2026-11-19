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
        Schema::create('computers', function (Blueprint $table) {
            $table->id();
            $table->string('serial', 20)->nullable()->unique();
            $table->string('number')->unique();
            $table->string('brand');
            $table->string('assigned_name')->nullable();
            $table->string('assigned_document')->nullable();
            $table->string('assigned_email')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_public_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};