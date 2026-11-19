<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training_Center extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'name',
        'location'
    ];

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'training_center_id');
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}
