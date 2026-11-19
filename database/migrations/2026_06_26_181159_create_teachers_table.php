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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_number')->nullable()->unique();
            $table->string('email');
            $table->string('access_code')->nullable()->unique();

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

        Schema::table('computers', function (Blueprint $table) {
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};