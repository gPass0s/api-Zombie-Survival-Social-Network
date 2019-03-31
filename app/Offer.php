<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Offer extends Model
{

	protected $guarded = [];

	public function user()
	{
		return $this->belongsTo('App\User', 'id');
	}//

	public function items()
	{
		return $this->hasMany('App\OfferItem', 'offer_id');
	}
}
