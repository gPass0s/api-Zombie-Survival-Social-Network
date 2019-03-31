<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InfectionReport extends Model
{

    protected $guarded = [];

    public function user()
    {
    	return $this->belongsTo('App\User', 'id','user_id');
    }////
}
