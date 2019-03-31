<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Trade extends Model
{	
	

	protected $guarded = [];
    public function offer01()
    {
    	return $this->hasOne('App\Offer', 'id','offer_id_01'); //
    }

    public function offer02()
    {
    	return $this->hasOne('App\Offer', 'id','offer_id_02'); //
    }

    public function user01()
    {
    	return $this->hasOne('App\User', 'id','user_id_01'); //
    }

     public function user02()
    {
    	return $this->hasOne('App\User', 'id','user_id_02'); //
    }
}
