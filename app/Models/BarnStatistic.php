<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarnStatistic extends Model
{
    use HasFactory;

//    protected $connection = 'mysql2';
//    protected $table = 'barnstatistic';

    protected $connection = 'remote';
    protected $table = 'BarnStatistic';

    public function barn()
    {
        return $this->belongsTo(Barn::class, 'barn_id', 'id');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'cycle_id', 'id');
    }
}