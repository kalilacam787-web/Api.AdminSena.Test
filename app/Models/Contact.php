<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'name',
        'email',
        'phone_fixed',
        'phone_mobile',
        'message',
        'status'
    ];
}
