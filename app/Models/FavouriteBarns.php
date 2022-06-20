<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavouriteBarns extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'favouritebarns';
    public $timestamps = false;

    public function barn()
    {
        return $this->belongsTo(Barn::class, 'barn_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
