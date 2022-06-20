<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionStatistic extends Model
{
    use HasFactory;

    //protected $connection = 'mysql2';
    //protected $table = 'sectionstatistic';

    protected $connection = 'remote';
    protected $table = 'SectionStatistic';

    
     public function section()
    {
        return $this->hasMany(Section::class, 'id', 'section_id');
    }
}
