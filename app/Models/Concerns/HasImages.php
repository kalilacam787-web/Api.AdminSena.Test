<?php

namespace App\Models\Concerns;

use App\Models\Imagen;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasImages
{
    public function imagenes(): MorphMany
    {
        return $this->morphMany(Imagen::class, 'imageable')->orderBy('order');
    }

    public function imagen(): MorphOne
    {
        return $this->morphOne(Imagen::class, 'imageable')->orderBy('order');
    }
}
