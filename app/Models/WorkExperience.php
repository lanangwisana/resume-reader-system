<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkExperience extends Model
{
    use HasFactory;
    protected $table = 'work_experiences';
    protected $primaryKey = 'id';
    protected $fillable = ['position', 'company', 'start_date', 'end_date'];
}
