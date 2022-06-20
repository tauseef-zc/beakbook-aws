<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mysql';
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];

    public function farms()
    {
        return $this->hasMany(Farm::class, 'manager_user_id', 'id');
    }
    
     public function barns()
    {
        return $this->hasMany(Barn::class, 'manager_user_id', 'id');
    }

}
