<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserBeakbook extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    //protected $connection = 'mysql2';
    //protected $table = 'user';

    protected $connection = 'remote';
    protected $table = 'User';


    public function company()
    {
        return $this->belongsTo(company::class);
    }

    public function farms()
    {
        return $this->hasMany(Farm::class, 'manager_user_id', 'id');
    }
}
