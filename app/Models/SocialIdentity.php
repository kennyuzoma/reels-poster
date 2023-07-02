<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_name',
        'provider_id',
        'access_token',
        'metadata'
    ];

    protected $casts = [
        'metadata' => AsArrayObject::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
