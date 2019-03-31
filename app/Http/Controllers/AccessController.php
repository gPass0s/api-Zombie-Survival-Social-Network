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
            'gender' => ['required', 'string', 'max:10'],
            'offer_range' => ['required', 'numeric', 'min:0.05'],
            'location' => ['required','string','max:255'],
            'secret_question' => ['required', 'string', 'max:255'],
            'secret_answer' => ['required', 'string', 'max:255'],
        ]);
        
        if ($validator->fails()) return response()->json(['error' => $validator->errors()]);

        # Validating invetory
        $invetory = $request['invetory'];
        $items_invetory = array_unique($invetory);
        $items = Item::all();
        $items_validation = TRUE;
        foreach ($items_invetory as $item)
        {
            $item_found = Item::where('item',strtoupper($item))->get();
            if($item_found->count()==0)
            {
                $items_validation = FALSE;
                break;
            }
        }
        
        if (!$items_validation) return response()->json("Error 003: There are invalid items in your invetory");

        $location = app('App\Http\Controllers\UsersController')->getCoordinates($request['location']);
        
        if ($location == "Address not found") return response()->json(['error' =>$location]);

        $user = User::create([
            'username' =>utf8_decode($request['username']),
            'password' => Hash::make($request['password']),
            'first_name' =>utf8_decode(strtoupper($request['first_name'])),
            'last_name' =>utf8_decode(strtoupper($request['last_name'])),
            'age' =>$request['age'],
            'gender' =>$request['gender'],
            'latitude' =>$location['latitude'],
            'longitude' =>$location['longitude'],
            'location' =>$location['location'],
            'offer_range' =>$request['offer_range'],
            'secret_question' =>utf8_decode($request['secret_question']),
            'secret_answer' =>utf8_decode($request['secret_answer']),
        ]);

        $this->createInventory($user,$invetory);

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
            'offer_range' => $user->offer_range,
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
