<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolStudent extends Model
{
    protected $table = 'school_students';

    protected $fillable = [
        'request_id',
        'academic_grade',
        'school_name',
        'family_book_photo',
    ];

    public function request()
    {
        return $this->belongsTo(RequestModel::class);
    }
}


