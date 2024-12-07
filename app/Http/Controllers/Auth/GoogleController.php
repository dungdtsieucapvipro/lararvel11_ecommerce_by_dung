<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $finduser = User::where('google_id', $user->id)->first();

            if ($finduser) {
                Auth::login($finduser);
                // Chuyển hướng người dùng về trang chủ '/'
                return redirect('/');
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'password' => encrypt('123456dummy'),
                    // 'mobile' => 'null',  // Gán giá trị mặc định nếu không có số điện thoại
                ]);

                Auth::login($newUser);
                // Chuyển hướng người dùng về trang chủ '/'
                return redirect('/');
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
