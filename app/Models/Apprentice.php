<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apprentice extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'last_name', 'document_type', 'document_number', 'email', 'password', 'cell_number', 'course_id'];

    protected $hidden = ['password'];

    protected $casts = ['password' => 'hashed'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
}
