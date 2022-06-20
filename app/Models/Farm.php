<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    use HasFactory;

    //protected $connection = 'mysql2';
    //protected $table = 'farm';

    protected $connection = 'remote';
    protected $table = 'Farm';


    public function barns()
    {
        return $this->hasMany(Barn::class, 'farm_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'manager_user_id', 'id');
    }

    public function userbeakbook()
    {
        return $this->belongsTo(UserBeakbook::class, 'manager_user_id', 'id');
    }
}
