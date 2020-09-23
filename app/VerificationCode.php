<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    protected $guarded = [];

    public static function generate($email)
    {
        $otp = mt_rand(100000, 999999);
        static::create([
            "email" => $email,
            "otp" => $otp,
            "expired_at" => Carbon::now()->addMinutes(5),
        ]);
        return $otp;
    }

    public static function getCode($email, $otp)
    {
        return static::where([
            ['email', '=', $email],
            ['otp', '=', $otp],
        ])->first();
    }
}
