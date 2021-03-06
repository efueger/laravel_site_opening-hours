<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'label', 'service_id'
    ];

    public function openinghours()
    {
        return $this->hasMany('App\Models\Openinghours');
    }

    public function service()
    {
        return $this->belongsTo('App\Models\Service');
    }
}
