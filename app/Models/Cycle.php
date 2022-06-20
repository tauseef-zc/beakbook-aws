<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory;

    //protected $connection = 'mysql2';
    //protected $table = 'cycle';

    protected $connection = 'remote';
    protected $table = 'Cycle';


    public function barn()
    {
        return $this->belongsTo(Barn::class, 'barn_id', 'id');
    }
    
    public function barnStatistics()
    {
        return $this->hasMany(BarnStatistic::class, 'cycle_id', 'id');
    }
    
     public function sectionStatistics()
    {
        return $this->hasMany(SectionStatistic::class, 'cycle_id', 'id');
    }
}
