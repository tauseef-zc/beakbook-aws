<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserBeakbook;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use DB;

class UserController extends Controller
{
    public function fetchUsers()
    {
    
    $users = DB::connection('remote')
            ->table('User')
            ->get();

    dd($users);

        $beakbookUsers = UserBeakbook::all()->toArray();

        foreach ($beakbookUsers as $beakbookUser) {
            $validator = Validator::make($beakbookUser, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users'
            ]);

            if (!$validator->fails()) {
                $user = User::where('email', $beakbookUser['email'])->first();
                if (!$user) {
                    User::create([
                        'name' => $beakbookUser['name'],
                        'email' => $beakbookUser['email'],
                        'password' => Hash::make(Str::random(10))
                    ]);
                }
            }
        }
    }
    
    
    
}
