<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\v1\UserResource;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register the user into the application
     * 
     * @param Illuminate\Http\Request $request
     * @return User object
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|string'
        ]);

        if($validator->fails()) {
            return response()->json(["error" => $validator->errors() ], 422);
        }
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken(User::PASSPORT_ACCESS_TOKEN_NAME)->accessToken;

        return response()->json(['token' => $token, 'user' => new UserResource($user)], 200);
    }

     /**
     * Log the user in to the application
     * 
     * @param Illuminate\Http\Request 
     * @return Response
     */
    public function login()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = auth()->user();
            $token = $user->createToken(User::PASSPORT_ACCESS_TOKEN_NAME)->accessToken;

            return response()->json(['token' => $token, 'user' => new UserResource($user)], 200);
        }

        return response()->json(['error' => 'Unauthorised'], 401);
    }

    /**
     * Log the user out of the application
     * 
     * @param Illuminate\Http\Request
     * @return Response
     */
    public function logout()
    {
        $user = auth('api')->user();

        if ($user) {
            $user->token()->revoke();
            return response()->json(['message' => 'You have been succesfully logged out.'], 200);
        }
        return response()->json(['error' => 'Unauthorised'], 401);
       
    }
}
