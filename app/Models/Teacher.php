<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'name',
        'document_number',
        'email',
        'access_code',
        'area_id',
        'training_center_id'
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function trainingCenter()
    {
        return $this->belongsTo(Training_Center::class, 'training_center_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course__teachers');
    }
}
