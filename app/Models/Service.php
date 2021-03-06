<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'draft',
        'identifier',
        'label',
        'source',
        'uri',
    ];

    public function channels()
    {
        return $this->hasMany('App\Models\Channel');
    }

    /**
     * Return roles for each service that the user belongs to
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_service_role', 'service_id', 'user_id');
    }
}
