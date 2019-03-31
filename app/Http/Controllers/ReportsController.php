<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\User;

class ReportsController extends Controller
{
    
    public function zssnReports(User $user)
    {
    	if ($this->checkUser($user)!="ok") return response()->json($this->checkUser($user));

    	$users = User::all()->count();
    	$infected = User::where('infected',TRUE)->count();

    	$ptsLost = User::where('infected',TRUE)->sum('total_points');

    	if ($users>0) return response()->json(["Percentage of infected survivors" => round(100*($infected/$users),2) . "%", "Percentage of non-infected survivors" => round(100*(1-($infected/$users)),2) . "%", "Points lost because of infected survivor" => $ptsLost]);

    	response()->json(["percentage of infected survivors" => 0 . "%", "percentage of non-infected survivors" => 0,"Points lost because of infected survivor" => 0]);
    }


    private function checkUser($user)
    {
        if($user->id != auth()->id()) return 'User not signed';
        if ($user->infected) return "Error 100: This user is infected";
        return 'ok';
    }//
}
