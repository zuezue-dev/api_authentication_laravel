<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\User;
use Carbon\Carbon;
use App\VerificationCode;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Notifications\PasswordResetRequest;

class ForgotPasswordController extends Controller
{
    /**    
     * Send email to the requested user
     * 
     * @param Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required | email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::whereEmail($request->email)->first();

        $otp = VerificationCode::generate($user->email);

        if ($user) {
            $user->notify(
                new PasswordResetRequest($otp)
            );
        }

        return response()->json(['message' => 'We have e-mailed your password reset code.']);
    }

    /**
     * Verify the requested otp 
     * 
     * @param Illuminate\Http\Request $request
     * @return $token
     */

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => 'required',
            "otp"   => 'required',
        ]);
       
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->email;
        $otp   = $request->otp;

        $verification = VerificationCode::getCode($email, $otp);

        if ($verification) {
            if (!Carbon::now()->gt($verification->expired_at)) {
                $verification->expired_at = Carbon::now();
                $verification->save();

                $user = User::whereEmail($verification->email)->first();

                $token = Password::broker('users')->createToken($user);

                return response()->json(["message" => 'Successfully verified', "token" => $token], 200);
            }
            return response()->json(["message" => 'Verification code expires'], 422);
        }
        return response()->json(["message" => 'Verification code not found'], 404);
    }
}
