<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;
    protected $table = 'certifications';
    protected $primaryKey = 'id';
    protected $fillable = ['certification_name', 'organizer', 'year'];
}
