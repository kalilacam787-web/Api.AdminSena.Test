<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial',
        'number',
        'brand',
        'image_path',
        'image_public_id',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return filter_var($this->image_path, FILTER_VALIDATE_URL)
            ? $this->image_path
            : '/storage/' . ltrim($this->image_path, '/');
    }

    public function apprentices()
    {
        return $this->hasMany(Apprentice::class);
    }

    public function apprentice()
    {
        return $this->hasOne(Apprentice::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
