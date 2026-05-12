<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityStudent extends Model
{
    protected $table = 'university_students';

    protected $fillable = [
        'request_id',
        'academic_year',
        'university_id_photo',
        'support_type',
    ];

    public function request()
    {
        return $this->belongsTo(RequestModel::class);
    }
}
