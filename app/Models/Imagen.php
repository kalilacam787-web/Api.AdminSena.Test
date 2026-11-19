<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Imagen extends Model
{
    use HasFactory;

    protected $table = 'imagenes';

    protected $fillable = [
        'path',
        'disk',
        'public_id',
        'alt_text',
        'order',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
