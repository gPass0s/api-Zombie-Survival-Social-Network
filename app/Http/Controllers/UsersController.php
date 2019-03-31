<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

use App\User;
use App\Belonging;
use App\Item;
use App\Notification;

class UsersController extends Controller
{

    public function updateUserLocation(User $user, Request $request)
    {
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        
        $validator = Validator::make($request->all(), [
            'offer_search_distance' => ['numeric', 'min:0.05'],
            'location' => ['string','max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $flag01 = TRUE;
        if (isset($request["location"]))
        {
          $location = $this->getCoordinates($request['location']);
          if ($location == "Address not found") return $location;
          $latitude = $location["latitude"];
          $longitude = $location["longitude"];
          $loc_formatted = $location["location"];
        } else
        {
           $latitude = $user->latitude;
           $longitude = $user->longitude;
           $flag01 = FALSE;
           $loc_formatted = $user->location;

        }
        
        $flag02 = TRUE;
        if (isset($request["offer_search_distance"]))
        {
            $offer_range = $request["offer_search_distance"];

        } else
        {
            $flag02 = FALSE;
            $offer_range = $user->offer_range;
        }
        
        $data = array(
            "latitude" => $latitude,
            "longitude" => $longitude,
            "location" => $loc_formatted,
            "offer_range" => $offer_range,
            );
        
        return response()->json($user->updateLocation($data, $flag01,$flag02));
//
    }

    public function showUserBelongings(User $user)
    {

        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        $water = $user->belongings->where("item_name","WATER")->count();
        $food = $user->belongings->where("item_name","FOOD")->count();
        $medication = $user->belongings->where("item_name","MEDICATION")->count();
        $ammunition = $user->belongings->where("item_name","AMMUNITION")->count();

        return response()->json(["owner" => $user->first_name . " " . $user->last_name, "points" => $user->total_points,"items" => array("Water"=> $water, "Food" => $food, "Medication" => $medication,"Ammuntion" =>$ammunition)]);
//
    }

    public function reportInfectedUser(User $user, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required','string', 'max:255']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        return response()->json($user->addInfectionReport($request));
//
    }

    public function notifications(User $user)
    {
        
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));

        return Notification::select('from','description')->where('to_user_id',$user->id)->orderBy('id', 'DESC')->get();//
    }


    public function notificationSeen(User $user)
    {
        
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        $notifications = Notification::where('to_user_id',$user->id)->orderBy('id', 'DESC');
        if ($notifications->count()>0) $notifications->update(array("seen" => TRUE)); 
    }

    public function notify(User $user, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_username' => ['required','string', 'max:255'],
            'message' => ['string',' min:1','max:511'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));
        $user02 = User::where('username', $request['to_username']);
        if ($user02->count()>0) 
        {
            $notification = array(
                "to_user_id" => $user02->get()[0]->id,
                "from" => $user->username,
                "descripton" => $request['message'],
            );
            $user->addNotification($notification);
        } else 
        {
            return response()->json("User " . $request['to_username'] . " not found");
        }
    }

    
    public function show(User $user)
    {
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));

        return response()->json(app('App\Http\Controllers\AccessController')->reponseUser($user));//
    }

    /*
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(User $user, Request $request)
    {
        if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));


        $validator = Validator::make($request->all(),[
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'numeric'],
            'gender' => ['required', 'string', 'max:20'],
        ]);


        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $user->first_name = strtoupper($request['first_name']);
        $user->last_name = strtoupper($request['last_name']);
        $user->age = $request['age'];
        $user->gender = $request['gender'];
        
        $user->save();

        $response =  app('App\Http\Controllers\AccessController')->reponseUser($user);
        
        return response()->json(["message" => 'Your personal information was edited', "user" => $response]);
    }


    private function checkUser($user)
    {
        if($user->id != auth()->id()) return 'User not signed';
        if ($user->infected) return "Error 100: This user is infected";
        return 'ok';
    }

    public function getCoordinates($location)
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?";
        $apiKey = "key=AIzaSyBjrTvcBLGy5eZb1tdh1FO56qxr4woFI60";
        $location_stripped = str_replace(' ', '', $location);
        $res =  file_get_contents($url."address=".$location_stripped."&".$apiKey);
        $res = json_decode(utf8_encode($res),true);
        if ($res['results']==null) return "Address not found"; 
        $lat = $res["results"][0]['geometry']['location']['lat'];
        $lng = $res["results"][0]['geometry']['location']['lng'];
        $formated_location = utf8_decode($res["results"][0]['formatted_address']);
        return array("latitude"=>$lat,"longitude"=>$lng,"location"=>$formated_location);
    }

    

    /*
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    
}
