<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Belonging;
use App\Item;
use App\User;
use App\Offer;
use Illuminate\Support\Facades\DB;

class OffersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(User $user, Request $request)
    {   
         if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        
        return response()->json($user->addOffer($request));//
    }

    public function myOffers(User $user)
    {
         if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        return response()->json($user->myOpenOffers());
    }

    public function destroy(User $user, Request $request)
    {
         if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        return response()->json($user->deleteOffer($request["offer_id"]));//
    }

    public function closeTrade(User $user, Request $request)
    {
         if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));

        return response()->json($user->closeTrade($request["trade_id"]));
    }

     public function myOpenTrades(User $user, Request $request)
    {
         if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));

        return response()->json($user->myOpenTrades($request["trade_id"]));
    }

    public function offerDetails(User $user, Request $request)
    {
         
          if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
         $offer = Offer::find($request['offer_id']);
         if (!$offer)  return response()->json("Error 019: Offer not found"); 
         return $offer->items()->where('offering',FALSE)->get();
    }

    public function findOffersNearBy(User $user)
    {   
         if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        
        $offers = DB::select("SELECT *, SQRT(POW(69.1 * (latitude - '$user->latitude'), 2) + POW(69.1 * ('$user->longitude' - longitude) * COS(latitude / 57.3), 2))*1.60934 AS distance FROM offers WHERE status = 'open' and user_id != '$user->id' HAVING distance <= '$user->offer_range' ORDER BY distance");

        return response()->json($offers);
    }

     private function checkUser($user)
    {
        if($user->id != auth()->id()) return 'User not signed';
        if ($user->infected) return "Error 100: This user is infected";
        return 'ok';
    }

}
