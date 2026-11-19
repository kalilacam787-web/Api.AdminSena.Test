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
        Schema::create('apprentices', function (Blueprint $table) {
            $table->id();
            //campos  
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('document_type', 10)->nullable();
            $table->string('document_number')->nullable()->unique();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('cell_number');

            // llaves foraneas de courses y computers
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('computer_id')->nullable();

            // Referencia courses
            $table->foreign('course_id')
                ->references('id')
                ->on('courses')->onDelete('set null');

            // Referencia  computers
            $table->foreign('computer_id')
                ->references('id')
                ->on('computers')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apprentices');
    }
};
