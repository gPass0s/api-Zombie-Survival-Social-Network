<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class OfferItem extends Model
{
    protected $guarded = [];
	
    public function Offer()

    {
    	return $this->belongsTo(Offer::class);
    }//

    public function belonging()

    {
    	return $this->hasOne('App\Belonging', 'id','belonging_id');
    }

    public function item()

    {
    	return $this->belongsTo('App\Item', 'id', 'item_id');
    }///
}
