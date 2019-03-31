<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Item;

class ItemsController extends Controller
{
    public function create ()
    {
    	$items = array(
    		array('item' =>'Water', 'points' => 4),
    		array('item' =>'Food', 'points' => 3),
    		array('item' =>'Medication', 'points' => 2),
    		array('item' =>'Ammunition', 'points' => 1),
    	);

    	foreach ($items  as $item) $this->addItem($item);
    	
    }

    public function addItem ($item)
    {
		$item["item"] = strtoupper($item["item"]);
		Item::create($item);
    	
    }///
}
