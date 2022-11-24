<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    function index()
    {
        return view('Auth.login');
    }

    function register()
    {
        return view('Auth.register');
    }

    function validate_registration(Request $request)
    {
        $request->validate([
            'name'         =>   'required',
            'gender'       =>   'required|in:Male,Female',
            'email'        =>   'required|email|unique:users',
            'password'     =>   'required|min:6'
        ]);

        $data = $request->all();

        $user_image = '' ;

        User::create([
            'name'       =>  $data['name'],
            'gender'     =>  $data['gender'],
            'email'      =>  $data['email'],
            'password'   =>  Hash::make($data['password']),
            'user_image' =>  $user_image
        ]);

        return redirect('login')->with('success', 'Registration Completed, now you can login');

    }

    function validate_login(Request $request)
    {
        $request->validate([
            'email' =>  'required',
            'password'  =>  'required'
        ]);

        $credentials = $request->only('email', 'password');

        if(Auth::attempt($credentials))
        {
            $token = md5(uniqid());
            User::where('id', Auth::id())->update(['token' => $token]);

            return redirect('Chat');
        }

        return redirect('login')->with('success', 'Login details are not valid');

    }

    function chat()
    {
        if(Auth::check())
        {
            return view('Chat');
        }

        error_log('sssss');
        return redirect('login')->with('success', 'you are not allowed to access');
    }

    function logout()
    {
        Session::flush();

        Auth::logout();

        return Redirect('login');
    }
}
