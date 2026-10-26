<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasUnoNumber = DB::table('computers')
            ->whereRaw('LOWER(number) = ?', ['uno'])
            ->exists();
        $hasNumberOne = DB::table('computers')->where('number', '1')->exists();

        if ($hasUnoNumber && $hasNumberOne) {
            throw new \RuntimeException('No se puede convertir el número "uno": ya existe un computador con el número 1.');
        }

        DB::table('computers')
            ->whereRaw('LOWER(number) = ?', ['uno'])
            ->update(['number' => '1']);

        Schema::table('computers', function (Blueprint $table) {
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
