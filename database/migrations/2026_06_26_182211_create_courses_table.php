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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_number');
            $table->string('name')->nullable();
            $table->string('day');
            $table->string('schedule')->nullable();
            $table->unsignedTinyInteger('duration_months')->nullable();

            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('training_center_id')->nullable();

            $table->foreign('area_id')
                ->references('id')
                ->on('areas')->onDelete('set null');

            $table->foreign('training_center_id')
                ->references('id')
                ->on('training__centers')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
