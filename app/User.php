<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 
    ];

    # Returns all belongings of a given user
    public function belongings()
    {
        return $this->hasMany('App\Belonging', 'user_id');
    }

    # Returns all notifications of a given user
    public function notifications()
    {
        return $this->hasMany('App\Notification', 'user_id');
    }

    # Returns all infections_report of a given user
    public function contaminationReports()
    {
        return $this->hasMany('App\InfectionReport', 'user_id');
    }

    # Returns all offers of a given user
    public function offers()
    {
        return $this->hasMany('App\Offer', 'user_id');
    }

    # Updates users location
    public function updateLocation($request, $flag01, $flag02)
    {
        if (!$this->infected)
        {
            $this->latitude = $request['latitude'];
            $this->longitude = $request['longitude'];
            $this->location = $request['location'];
            $this->offer_range = $request['offer_range'];
            $this->update();
            $offers = Offer::where('user_id',$this->id)->where('status','open');
            $offers->update(array("latitude" => $this->latitude));
            $offers->update(array("longitude" => $this->longitude));
            $offers->update(array("offer_range" => $this->offer_range));


            $trades = Trade::where('user_id_01',$this->id)
                                ->orWhere('user_id_02',$this->id)
                                ->where(function ($q){
                                    $q->where('status',FALSE);
                                })->get();
                
            # If it has on holding trades, do:
            #  Make the other user offer avaible again
            #  Notify it
            if ($trades->count()>0)
            { 
                  foreach ($trades as $trade)
                  {
                        if ($trade->user_id_01 == $this->id )
                        {
                            $user02 = User::find($trade->user_id_02);
                            
                        } else 
                        {
                            $user02 = User::find($trade->user_id_01);
                        }
                         $description = "User " . $this->first_name . " " . $this->last_name . " that will trade items with you has just update its location to : ". $this->location . " LAT: " . $this->latitude . " LONG: ".$this->longitude;



                         $notification = array(
                                "to_user_id" => $user02->id,
                                "from" => "SYSTEM",
                                "descripton" => $description,
                            );
                        $this->addNotification($notification);    
                   }
            }


            # Tries to match open offers based on the new location;

            $offers= Offer::where('user_id',$this->id)->where("status", "open")->get();
            
            if ($offers->count()>0) 
            {
              foreach ($offers as $offer) $this->matchOffer($offer);  
            }

            If ($flag01 && $flag02) 

            {
                return "Your location has been updated to ". $this->location . " Lat: " . $this->latitude . ", Long:" . $this->longitude . ". And your offer search distance was set to " . $this->offer_range . " km";
            } elseif($flag01) 
            {
                return "Your location has been updated to ". $this->location . " Lat: " . $this->latitude . ", Long:" . $this->longitude;
            } elseif($flag02)
            {
                return "Your offer search distance was set to " . $this->offer_range . " km";
            }

        } else 
        {
            return "Error 100: This user is infected";
        }
    }

    # Returns all belongings of a given user
    public function addBelonging($item_id)
    {
        if (!$this->infected)
        {
            $item = Item::find($item_id);
            Belonging::create([
                'user_id' => $this->id,
                'item_id' => $item->id,
                'item_name' => $item->item,
                'item_points' => $item->points,
            ]);
        }
    }
    # Returns all belongins of a given user
    public function addNotification($request)
    {

        if (!$this->infected) 
        {
            Notification::create([
                'to_user_id' => $request['to_user_id'],
                'from' => $request["from"],
                'description' => $request['descripton'],
            ]);
        }
        
    }

    # Adds a new infection report 
    public function addInfectionReport($request)
    {
        if (!($this->infected))
        {   

            $user = User::where('username',$request['username'])->get();
            
            if ($user->count()==0)return 'Error 102: User '. $request['username'] . ' not found';
            
            $user = $user[0];
            # Checking if user is reporting itself
            if ($this->id == $user->id)  return "Error 101: You can't report your own contamination";
            
            # Checking if report is registered
            if ($this->isReportRegistered($user->id)) return "You've already report user (" . $user->username .") ". $user->first_name . " ".$user->last_name . "'s contamination";   
            # Creating a report
            InfectionReport::create([
                'report_user_id' => $this->id,
                'user_id' => $user->id,
            ]);
            # Mark a user as infected in case it has mora than three reports
            $this->isUserInfected($user);

            return "You've just reported (" . $user->username .") ". $user->first_name . " ". $user->last_name . "'s contamination. Thanks for making ZSSN safer!!!";  
        } else
        {
            return "Error 100: This user is infected";
        }
    }

    # Returns all belongings of a given user
    public function addOffer($request)
    {
    
        if (!$this->infected)
        {
            $check_offer = $this->validateOffer($request);
            if($check_offer["response"]!= "ok") return $check_offer["response"];

            $new_offer = Offer::create([
                'user_id' => $this->id,
                'points' => $check_offer['points'],
                'latitude'=> $this->latitude,
                'longitude'=>$this->longitude,
                'offer_range'=>$this->offer_range,
            ]);

            $this->addOfferItems($check_offer['belongings_id'],'offering',$new_offer); 

            $this->addOfferItems($check_offer['items_id'],'receiving',$new_offer);

            $this->matchOffer($new_offer);
            return 'Your offer number '. $new_offer->id .' has been successfully created';;
        } else 
        {
            return "Error 100: This user is infected";
        }
    }

    public function findOffersNearBy($user)
    {   
        
        $offers = DB::select("SELECT *, SQRT(POW(69.1 * (latitude - '$user->latitude'), 2) + POW(69.1 * ('$user->longitude' - longitude) * COS(latitude / 57.3), 2))*1.60934 AS distance FROM offers WHERE status = 'open' and user_id != '$user->id' HAVING distance <= '$user->offer_range' ORDER BY distance");

        (count($offers)>0) ? $offres_mounted = $this->mountOffer($offers) : $offres_mounted = "No offers found.";

        return $offres_mounted;
    }

    public function myOpenOffers()
    {
        $offers = Offer::where('user_id',$this->id)->where('status', 'open')->get();
        
        ($offers->count()>0) ? $offres_mounted = $this->mountOffer($offers) : $offres_mounted = "No offers found.";

        return  $offres_mounted;             
   
    }

    private function mountOffer($offers)
    {
        $arr = array();           
        foreach ($offers as $offer)
        {
            $water = OfferItem::where("offer_id",$offer->id)->where("item_id",1)->where("offering",1)->count();
            $food = OfferItem::where("offer_id",$offer->id)->where("item_id",2)->where("offering",1)->count();
            $medication = OfferItem::where("offer_id",$offer->id)->where("item_id",3)->where("offering",1)->count();
            $ammunition = OfferItem::where("offer_id",$offer->id)->where("item_id",4)->where("offering",1)->count();

            $off = array("water"=>$water, "food" =>$food,"medication" => $medication , "ammunition" => $ammunition);

            $water = OfferItem::where("offer_id",$offer->id)->where("item_id",1)->where("offering",0)->count();
            $food = OfferItem::where("offer_id",$offer->id)->where("item_id",2)->where("offering",0)->count();
            $medication = OfferItem::where("offer_id",$offer->id)->where("item_id",3)->where("offering",0)->count();
            $ammunition = OfferItem::where("offer_id",$offer->id)->where("item_id",4)->where("offering",0)->count();

            $rec = array("water"=>$water, "food" =>$food, "medication" => $medication, "ammunition" => $ammunition);

            $user = User::find($offer->user_id);
            
            ((isset($offer->distance)) ? $res = array("offer_id" => $offer->id, "created by" =>$user->username, "points" => $offer->points,"distance" => round($offer->distance,2) . " km","location" => $user->location,"Offering_Items" => $off, "Receiving_Items" => $rec) : $res =array("offer_id" => $offer->id, "created by" => $user->username,"points" => $offer->points,"Offering_Items" => $off, "Receiving_Items" => $rec));

            array_push($arr,$res);
        }
        return $arr;
    }

    public function deleteOffer($offer_id)
    {
        
        if (!$this->infected)
        {
            $offer = Offer::find($offer_id);

            if(!$offer==null)
            {

                if ($offer->user_id == $this->id)
                {
                    if ($offer->status == 'open')
                    {
                        $offerItems = OfferItem::where('offer_id', $offer_id)
                        ->where('belonging_id','!=',0)->get();

                        if ($offerItems->count()>0)
                        {
                            foreach ($offerItems as $offerItem)
                            {
                                $belonging = $offerItem->belonging;
                                $belonging->reserved = FALSE;
                                $belonging->save();
                            }
                        }
                    
                        $offer->delete();

                        return 'Your offer number ' . $offer->id . ' has been successfully deleted';

                    } else
                    { 

                        return "This offer is closed";
                    }
                }  else 
                {

                    return "Error 105: It's impossible to delete somebody else's offer";
                } 
                    
            } else
            {
                return 'Error 013: Offer not found';
            }
        } else
        {
            return "Error 100: This user is infected";
        }
    }

    public function myOpenTrades ()
    {
        
        $trades = Trade::where('status','FALSE')
                      ->where(function($query){
                        $query->where('user_id_01', $this->id)
                        ->orWhere('user_id_02',$this->id);
                      })->get();

        if ($trades->count()>0)
        {   
            $arr = array();
            foreach ($trades as $trade)
            {
                ($trade->user_id_01 == $this->id) ? $offer = $trade->offer01 : $offer = $trade->offer02;

                ($trade->user_id_01 == $this->id) ? $user_id = $trade->user_id_02 : $user_id = $trade->user_id_01;
                
                $water = $offer->items->where("item_id",1)->where("offering",1)->count();
                $food = $offer->items->where("item_id",2)->where("offering",1)->count();
                $medication = $offer->items->where("item_id",3)->where("offering",1)->count();
                $ammunition = $offer->items->where("item_id",4)->where("offering",1)->count();

                $off = array("water"=>$water, "food" =>$food,"medication" => $medication , "ammunition" => $ammunition);

                $water = $offer->items->where("item_id",1)->where("offering",0)->count();
                $food = $offer->items->where("item_id",2)->where("offering",0)->count();
                $medication = $offer->items->where("item_id",3)->where("offering",0)->count();
                $ammunition = $offer->items->where("item_id",4)->where("offering",0)->count();

                $rec = array("water"=>$water, "food" =>$food, "medication" => $medication, "ammunition" => $ammunition);
                
                $user = User::find($user_id);

                $res = array("trade_id" => $trade->id, "with" => $user->username, "located at" => $user->location, "points" => $offer->points, "Offering_Items" => $off, "Receiving_Items" => $rec);
                array_push($arr,$res);
            }

            return $arr;
        } else
        {
            return "Not trades at the moment";
        }

        return Trade::where('status','FALSE')
                      ->where(function($query){
                        $query->where('user_id_01', $this->id)
                        ->orWhere('user_id_02',$this->id);
                      })->get();
       
    }

    public function closeTrade ($trade_id)
    {
        $trade = Trade::find($trade_id);

        if ($trade)
        {
            if ($trade->user_id_01 == $this->id)
            {
                $trade->status_user_id_01 = TRUE;
                $trade->save();
            } else
            {
                $trade->status_user_id_02= TRUE;
                $trade->save();
            }

            if ($trade->status_user_id_01 && $trade->status_user_id_02 )
            {
                $trade->status= TRUE;
                $trade->save();

                $offer_01=Offer::find($trade->offer_id_01);
                $offer_02=Offer::find($trade->offer_id_02);


                $this->tradeItems($trade->user_id_01,$trade->offer_id_01,$trade->user_id_02,$trade->offer_id_02);

                $user_01 = User::find($trade->user_id_01);
                $user_02 = User::find($trade->user_id_02);
                
                $this->notifyUser($user_01, 'You have just traded items with '. $user_02->first_name .' ' . $user_02->last_name, 'SYSTEM', null,null);
                $this->notifyUser($user_02, 'You have just traded items with '. $user_01->first_name .' ' . $user_01->last_name, 'SYSTEM', null,null);
                 
                 $trade->delete();
                 $offer_01->delete();
                 $offer_02->delete();

            }
            $trade->update();
        }
    }
    
    private function matchOffer($offer)
    {
        $offers = DB::select("SELECT *, SQRT(POW(69.1 * (latitude - '$offer->latitude'), 2) + POW(69.1 * ('$offer->longitude' - longitude) * COS(latitude / 57.3), 2))*1.60934 AS distance FROM offers WHERE points='$offer->points' and status = 'open' and user_id != '$offer->user_id' and id != '$offer->id'  HAVING distance <= '$offer->offer_range' ORDER BY distance");
        
        if (count($offers)>0)
        {
            foreach($offers as $offer_found)
            {
                $items  = $offer->items;
                $items_matched = 0;
                $items_found =OfferItem::where('offer_id',$offer_found->id)->get();

                if($offer_found->distance<=$offer_found->offer_range)
                {
                    foreach($items as $item)
                    {   
                        $found = FALSE;
                        $index = 0;
                        foreach($items_found as $item_found)
                        {
                            if ($item->item_id == $item_found->item_id && 
                                $item->offering != $item_found->offering)
                            {
                                $found = TRUE;
                                $items_matched++;
                                break;
                            }
                            $index++;
                        }

                        if ($found)
                        {
                            $items_found->forget($index);
                        }     
                    }
                }

                if ($items_matched == $items->count())
                {
                    
                    $offer_foundObject = Offer::find($offer_found->id);
                    $this->createTrade($offer, $offer_foundObject);
                    break;
                }
            }
        }   
    }

    public function updatePoints()
    {
        $this->total_points = $this->belongings->sum('item_points');
        $this->save();
    }
    
    # Updates a user infection report status
    private function isUserInfected ($user) 
    {

        if ($user->contaminationReports->count()>2)
        {
            
            if (!$user->infected)
            {
                # Sets users as infected
                $user->infected = TRUE;
                $user->save();
                # Deleting all userinfected open ffers
                $offers = Offer::where('user_id',$user->id)
                           ->where('status','open');
                
                if ($offers) $offers->delete();
                # Delete all its belongings
                Belonging::where('user_id',$user->id)->delete();
                # Search for all its on holding trades
                $trades = Trade::where('user_id_01',$user->id)
                                ->orWhere('user_id_02',$user->id)
                                ->where(function ($q){
                                    $q->where('status',FALSE);
                                })->get();
                
                # If it has on holding trades, do:
                #  Make the other user offer avaible again
                #  Notify it
                if ($trades->count()>0)
                {
                      foreach ($trades as $trade)
                      {
                            if ($trade->user_id_01 == $user->id )
                            {
                                $this->revertTrade($trade->offer_id_02,$trade->user_id_02,$user);
                                $offer = Offer::find($trade->offer_id_01);
                            } else 
                            {
                                $this->revertTrade($trade->offer_id_01,$trade->user_id_01,$user);
                                $offer = Offer::find($trade->offer_id_02);
                            }
                       }
                }

                $trades = Trade::where('user_id_01',$user->id)->orWhere('user_id_02',$user->id)->where(function ($q){$q->where('status',FALSE);});

                if ($trades->count()>0) 
                { 
                    $trades->delete();
                    $offer->delete();
                }
            }            
        } 
    }

    # Checks if this specific report was already made
    private function isReportRegistered ($user_id)
    {
    
        $report = InfectionReport::where('report_user_id',$this->id)
                                    ->where('user_id',$user_id)->get();
        
        if ($report->count()>0)
        {
            return TRUE;
        } else 
        {
            return FALSE;
        };
    
    }

    # Revert a given a trade
    private function revertTrade($offer_id, $user_id, $zombie) 
    {
        $offer = Offer::find($offer_id);
        $offer->status = 'open';
        $offer->save();
        $user = User::find($user_id);
        $message = "Caution!!! The user ". $zombie->username ." that you're about to trade items has just been infected. Stay away from it. Your offer was placed back in the offers pool";

        $notification = array(
                "to_user_id" => $user->id,
                "from" => "SYSTEM",
                "descripton" => $message,
            );
        $user->addNotification($notification);


    }


    private function createTrade($offer_01,$offer_02)
    {
        
        $user_01 = User::find($offer_01->user_id);
        $user_02 = User::find($offer_02->user_id);

        if(!($user_01->infected || $user_02->infected))
        {
            $offer_01->status = 'processing';
            $offer_02->status = 'processing';
            $offer_01->save();
            $offer_02->save();

            Trade::create([
                "offer_id_01" => $offer_01->id,
                "offer_id_02" => $offer_02->id,
                "user_id_01" => $user_01 ->id,
                "user_id_02" => $user_02->id,
            ]);


            $this->notifyUser($user_01,null,'SYSTEM',$user_02, $offer_01);
            $this->notifyUser($user_02,null,'SYSTEM',$user_01, $offer_02);
        }       
    }


    public function notifyUser($user_01, $descripton = null, $from=null, $user_02=null, $offer=null)
    {   

        if ($descripton == null)
        {

            $description = strval("Your offer number " . $offer->id . " has been matched. Here's the person you're going to trade personal information: USERNAME => ". $user_02->username . ", NAME => " . $user_02->first_name ." " . $user_02->last_name . ", AGE => " . $user_02->age . ", GENDER => " . $user_02->gender . ", LOCATION => " . $user_02->location . " LAT => " .$user_02->latitude . " LONG => " . $user_02->longitude);
            
            $notification = array(
                "to_user_id" => $user_01->id,
                "from" => $from,
                "descripton" => $description,
                );
                $user_01->addNotification($notification);
        
        } elseif ($descripton != null && $from== 'SYSTEM')
        {
            $notification = array(
                "to_user_id" => $user_01->id,
                "from" => $from,
                "descripton" => $descripton,
            );
            $user_01->addNotification($notification);
        } else
        {
            $notification = array(
                "to_user_id" => $user_02->id,
                "from" => $user_01->username,
                "descripton" => $descripton,
            );
            $user_02->addNotification($notification);
        }

        
    }

    private function tradeItems($user01_id,$offer_01_id,$user02_id,$offer_02_id)
    {

        $offerItems_user01 = OfferItem::where('offer_id',$offer_01_id)
                            ->where('belonging_id','!=',0)->get();

        $offerItems_user02 = OfferItem::where('offer_id',$offer_02_id)
                            ->where('belonging_id','!=',0)->get();

        $user01 = User::find($user01_id);
        $user02 = User::find($user02_id);


        foreach ($offerItems_user01 as $offerItem)
        {
            $belonging = Belonging::find($offerItem->belonging_id);
            echo $belonging->item_name;
            $belonging->user_id = $user02->id;
            $belonging->reserved = FALSE;
            $belonging->save();
        }

        foreach ($offerItems_user02 as $offerItem)
        {
            $belonging = Belonging::find($offerItem->belonging_id);
            echo $belonging->item_name;
            $belonging->user_id = $user01->id;
            $belonging->reserved = FALSE;
            $belonging->save();
        }

    }

    private function addOfferItems($items, $flag, $offer)
    {

        foreach($items as $item_id) 
        { 
            if ($flag == 'offering')
            {
                $belonging = Belonging::find($item_id);
                OfferItem::create([
                    'offer_id' =>$offer->id,
                    'belonging_id' =>$belonging->id,
                    'item_id' =>$belonging->item_id,
                    'item_name' =>$belonging->item_name,
                    'offering'=>TRUE,
                ]);
                $belonging->reserved=TRUE;
                $belonging->update();
            } else 
            {
                $item = Item::find($item_id);
                OfferItem::create([
                    'offer_id' =>$offer->id,
                    'belonging_id' => 0,
                    'item_id' =>$item->id,
                    'item_name' =>$item->item,
                    'offering'=>FALSE,
                ]);
            } 
        }
    }

    private function validateOffer($request)
    {
        $response = 'ok';
        $offering = array();
        $receiving = array();
        $countOffering = 0;
        $countReceiving = 0;

        if (isset($request['offering']["water"]))
        {
             if ($request['offering']["water"]>0) for ($i=0; $i<$request['offering']["water"];$i++) array_push($offering,"WATER");
        }; 
        if (isset($request['offering']["food"]))
        {
            if ($request['offering']["food"]>0) for ($i=0; $i<$request['offering']["food"];$i++) array_push($offering,"FOOD");
        }
        if (isset($request['offering']["medication"]))
        {
            if ($request['offering']["medication"]>0) for ($i=0; $i<$request['offering']["medication"];$i++) array_push($offering,"MEDICATION");

        }    
        if (isset($request['offering']["ammunition"]))
        {
            if ($request['offering']["ammunition"]>0) for ($i=0; $i<$request['offering']["ammunition"];$i++) array_push($offering,"AMMUNITION");
        }
        if(count($offering)==0) return ["response" => "Error 011: Offer is empty"];

        
        if(isset($request['receiving']["water"]))
        {
            if ($request['receiving']["water"]>0) for ($i=0; $i<$request['receiving']["water"];$i++) array_push($receiving,"WATER");
        }
        if(isset($request['receiving']["food"]))
        {
            if ($request['receiving']["food"]>0) for ($i=0; $i<$request['receiving']["food"];$i++) array_push($receiving,"FOOD");
        }

        if(isset($request['receiving']["medication"]))
        {
             if ($request['receiving']["medication"]>0) for ($i=0; $i<$request['receiving']["medication"];$i++) array_push($receiving,"MEDICATION");
        }  
        if(isset($request['receiving']["ammunition"]))
        {
            if ($request['receiving']["ammunition"]>0) for ($i=0; $i<$request['receiving']["ammunition"];$i++) array_push($receiving,"AMMUNITION");
        }
        if(count($receiving)==0) return ["response" => "Error 011: Your receiving basket is empty"];
    
        $belongings = $this->belongings;
        $addItems = 0;
        $belongings_id = array();
        $items_id = array();
        foreach ($offering as $item)
        { 
            $index = 0;
            $found = FALSE;
            foreach ($belongings as $belonging)
            {
                $item_found = Item::where('item',strtoupper($item))->get();
                if ($item_found[0]->id==$belonging->item_id && (!$belonging->reserved))
                {
                    $addItems++;
                    $found = TRUE;
                    array_push($belongings_id,$belonging->id);
                    break;
                }
                $index++;
            }
            if ($found) $belongings->forget($index);
            $countOffering = $item_found[0]->points + $countOffering;
        }

        if ($response!='ok') return ["response" =>$response];
        if ($addItems != count($offering))return ["response" => "Error 015: You don't have some of the items you're trying to offer or they are already reserved"];
    
        foreach ($receiving as $item)
        { 
            $item_found = Item::where('item',strtoupper($item))->get();
            array_push($items_id,$item_found[0]->id); 
            $countReceiving= $item_found[0]->points + $countReceiving;
        }

        if ($response!='ok') return ["response" =>$response];

        if ($countOffering == $countReceiving)
        {   
            return ["response"=> $response,"points"=>$countOffering,"belongings_id"=>$belongings_id, "items_id"=>$items_id];
        } else {
            return ["response"=>"Error 017: Your offer is not balanced. The points of offering items have to equal the points of items you want to receive"];
        }
    }
 
}
