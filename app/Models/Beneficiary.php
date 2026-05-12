<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
protected $fillable = [
    'full_name',
    'mother_name',
    'address',
    'email',
    'phone',
];
public function requests()
{
    return $this->hasMany(RequestModel::class);
}

}

