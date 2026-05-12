<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orphan extends Model
{
    protected $fillable = [
        'request_id',
        'family_booklet',
        'father_death_certificate',
    ];

    public function request()
    {
        return $this->belongsTo(RequestModel::class);
    }
}

