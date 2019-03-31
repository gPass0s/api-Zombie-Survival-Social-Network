<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Item;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AccessController extends Controller
{

    
    public function login()
    {
        $credentials = [
            'username' => request('username'), 
            'password' => request('password')
        ];

        if (Auth::attempt($credentials)) {
            
            $user = User::where('username',request('username'))->get()[0];

            $success['accessToken'] = Auth::user()->createToken('MyApp')->accessToken;

            $response = $this->reponseUser($user);

            return response()->json(['user'=>$response, 'token' => $success['accessToken']]);
        }

        return response()->json(['error' => 'Unauthorised'], 401);
    }

    public function logout(Request $request)
    {

        DB::table('oauth_access_tokens')->where('user_id', auth()->id())->delete();

        return response()->json("Logout done");
    }

    public function register(Request $request)
    {
        

        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required','min:8'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer'],
            'gender' => ['required', 'string', 'max:20'],
            'offer_search_distance' => ['required', 'numeric', 'min:0.05'],
            'location' => ['required','string','max:255'],
        ]);
        
        if ($validator->fails()) return response()->json(['error' => $validator->errors()]);



        # Validating invetory
        $invetory = $request['inventory'];

        $items = array();
        if ($invetory["water"]>0) for ($i=0; $i<$invetory["water"];$i++) array_push($items,"WATER");
        if ($invetory["food"]>0) for ($i=0; $i<$invetory["food"];$i++) array_push($items,"FOOD");
        if ($invetory["medication"]>0) for ($i=0; $i<$invetory["food"];$i++) array_push($items,"MEDICATION");
        if ($invetory["ammunition"]>0) for ($i=0; $i<$invetory["food"];$i++) array_push($items,"AMMUNITION");

        if (count($items) == 0) return response()->json(['error' => "Your inventory is empty"]);

        $location = app('App\Http\Controllers\UsersController')->getCoordinates($request['location']);
        
        if ($location == "Address not found") return response()->json(['error' =>$location]);

        $user = User::create([
            'username' =>$request['username'],
            'password' => Hash::make($request['password']),
            'first_name' =>strtoupper($request['first_name']),
            'last_name' =>strtoupper($request['last_name']),
            'age' =>$request['age'],
            'gender' =>$request['gender'],
            'latitude' =>$location['latitude'],
            'longitude' =>$location['longitude'],
            'location' =>$location['location'],
            'offer_range' =>$request['offer_search_distance'],
        ]);

        $this->createInventory($user,$items);

        $user->updatePoints();
        $response = $this->reponseUser($user);
        $success['accessToken'] = $user->createToken('MyApp')->accessToken;

        $messsage = "User " . $user->first_name . " " . $user->last_name .  " has been successfully created. WELCOME TO ZSSN!!!";

        return response()->json(["message"=>$messsage,"user" => $response,"access token"=>$success['accessToken']]);
    }

    public function reponseUser ($user)
    {
        $response = array(
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'age' => $user->age,
            'gender' => $user->gender,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'location' => $user->location,
            'offer_search_distance' => round($user->offer_range,2) . " km",
            'total_points' => $user->total_points,);

        return $response;
    }

    private function createInventory($user,$belongings)
    {
        foreach ($belongings as $item_name)
        {
            $item = Item::where('item',$item_name)->get()[0];
            $user->addBelonging($item->id);
        }
    }

    public function items()
    {
        return Item::select('item','points')->get();
    }

    private function checkUser($user)
    {
        if($user->id != auth()->id()) return 'User not signed';
        if ($user->infected) return "Error 100: This user is infected";
        return 'ok';
    }

}
