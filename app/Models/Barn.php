<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barn extends Model
{
    use HasFactory;

    //protected $connection = 'mysql2';
    //protected $table = 'barn';
	
    protected $connection = 'remote';
    protected $table = 'Barn';

    protected $fillable = ['location', 'name', 'farm_id'];
    public $timestamps = false;

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function cycles()
    {
        return $this->hasMany(Cycle::class, 'barn_id', 'id');
    }
    
     public function device()
    {
        return $this->hasMany(Device::class, 'barn_id', 'id');
    }
    
    public function favouriteBarns()
    {
        return $this->hasMany(FavouriteBarns::class, 'barn_id', 'id');
    }
    
     public function section()
    {
        return $this->hasMany(Section::class, 'barn_id', 'id');
    }

}

