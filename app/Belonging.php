<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Belonging extends Model
{
    protected $guarded = [];//

    public function user()
    {
    	return $this->belongsTo('App\User', 'id','user_id');
    }

    public function item()
    {
    	return $this->hasOne('App\Item', 'id','item_id');
    }
}
