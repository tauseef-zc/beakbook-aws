<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPassword;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class AuthController extends Controller
{

    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @return [string] message
     */

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()
            ->json(['data' => $user, 'access_token' => $token, 'token_type' => 'Bearer',]);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @return [string] access_token
     * @return [string] token_type
     */
    public function login(Request $request)
    {

        $validate = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$validate) {
            return response()
                ->json(['message' => 'Unauthorized'], 401);
        }

        $user = User::where('email', $request->email)->first();
        
        // $roles = $user->getRoleNames();
        // $role = Role::find($user->id);


        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()
                ->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

	// temporary fix: use companyId = 4 
	$farm_details = Farm::where('company_id', 4)->first();
	       
	//$farm_details = Farm::where('manager_user_id', 4)->first();
       
        if (!$farm_details) {
            $farm_details = [];
        }


      $perm =  $user->getPermissionsViaRoles();
        return response()->json([
            'access_token' => $user->createToken('access_token')->plainTextToken,
            'user_details' => $user,
            'farm_details' => $farm_details,
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param  [string] email
     * @return [string] message
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();
        return response()
            ->json(['message' => 'You have successfully logged out and the token was successfully deleted'], 200);
    }

    /**
     * Forgot password
     * @param  [string] email
     * @return [json] user object
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()
                ->json(['message' => 'User not found'], 404);
        }

        $token = $user->createToken('access_token')->plainTextToken;

        $PasswordReset = new PasswordReset;
        $PasswordReset->email = $user->email;
        $PasswordReset->token = $token;
        $PasswordReset->save();

        $details = [
            'token' => $token,
            'user' => $user
        ];
        Mail::to($user)->send(new ResetPassword($details));

        return response()
            ->json(['message' => 'Reset password link has been sent to your email'], 200);
    }



    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [json] message
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
                return response()->json(['message' =>  $validator->errors()->first()], 400);
        }

        $token = PasswordReset::where('token', $request->token)->first();
        if (!$token) {
            return response()
                ->json(['message' => 'Token not found'], 404);
        }
        if ($token->created_at->addMinutes(60)->isPast()) {
            return response()
                ->json(['message' => 'Token is invalid or expired'], 401);
        }

        User::where('email', $token->email)->update(['password' => Hash::make($request->password)]);
        return response()
            ->json(['message' => 'Password has been reset'], 200);
    }
    
    
     /**
     * change password for logged in user
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
            'new_password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' =>  $validator->errors()->first()], 400);
        }

        if ($validator->fails()) {
            return response()->json(['message' =>  $validator->errors()->first()], 400);
        }

        try {

            $user = User::find(auth()->user()->id);
            if (!$user) {
                return response()
                    ->json(['message' => 'User not found'], 404);
            }
 
            if (!Hash::check($request->old_password, $user->password)) {
                return response()
                    ->json(['message' => 'Old password is incorrect'], 401);
            }
            
            $user->password = Hash::make($request->new_password);
            $newUser = $user->save();     
            if($newUser){
            return response()
                ->json(['message' => 'Password has been changed'], 200);
            }        
            
        } catch (\Exception $e) {
            return response()
                ->json(['message' => 'Something went wrong'], 500);
        }
    }
}
