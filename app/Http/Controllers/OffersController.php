<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Belonging;
use App\Item;
use App\User;
use App\Offer;
use Validator;
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
        
        $validator = Validator::make($request->all(), [
            'offer_id' => ['required','numeric']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        
        return response()->json($user->deleteOffer($request["offer_id"]));//
    }

    public function closeTrade(User $user, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trade_id' => ['required','numeric']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }


        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));

        return response()->json($user->closeTrade($request["trade_id"]));
    }

     public function myOpenTrades(User $user)
    {
        
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));

        return response()->json($user->myOpenTrades());
    }

    /*public function offerDetails(User $user, Request $request)
    {
         
         
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
         
        $validator = Validator::make($request->all(), [
            'offer_id' => ['required','numeric']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

         $offer = Offer::find($request['offer_id']);
         if (!$offer)  return response()->json("Error 019: Offer not found"); 
         return $offer->items()->where('offering',FALSE)->get();
    }*/

    public function findOffersNearBy(User $user)
    {   
        return response()->json($user->findOffersNearBy($user));
    }

     private function checkUser($user)
    {
        if($user->id != auth()->id()) return 'User not signed';
        if ($user->infected) return "Error 100: This user is infected";
        return 'ok';
    }

}
