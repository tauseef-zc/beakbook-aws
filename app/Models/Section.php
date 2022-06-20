<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    //protected $connection = 'mysql2';
    //protected $table = 'section';

    protected $connection = 'remote';
    protected $table = 'Section';

    
     public function barn()
    {
        return $this->belongsTo(Barn::class, 'barn_id', 'id');
    }

    public function sectionStatistic()
    {
        return $this->hasMany(SectionStatistic::class, 'section_id', 'id');
    }
}
