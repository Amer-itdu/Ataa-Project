<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestModel extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'user_id',
        'beneficiary_id',
        'request_type',
        'status',
        'description',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function patient()
{
    return $this->hasOne(Patient::class);
}

    public function orphan()
    {
        return $this->hasOne(Orphan::class);
    }
    public function schoolStudent()
{
    return $this->hasOne(SchoolStudent::class);
}
public function universityStudent()
{
    return $this->hasOne(UniversityStudent::class);
}

}


