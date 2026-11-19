<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'title',
        'image_path',
        'description',
        'order',
        'is_active'
    ];
}
