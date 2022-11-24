<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function profile()
    {
        if (Auth::check())
        {
            $data = User::where('id', Auth::id())->get();
            return view('profile', compact('data'));
        }

        return redirect("login")->with('success', 'you are not allowed to access');
    }


    public function validate_profile(Request $request)
    {
        $request->validate([
            'name'       =>  'required',
            'gender'     =>  'required|in:Male,Female',
            'email'      =>  'required|email',
            'user_image' =>  'image|mimes:jpg,png,jpeg|max:2048|dimensions:min_width=100,min_height=100,max_width=1100,max_height=1100'

        ]);

        $user_image = $request->hidden_user_image;

        $user = User::find(Auth::id());

        if($request->user_image != '')
        {
            //$user_image = time() . '.' . $request->user_image->getClientOriginalExtension();
            $user_image =  time() . '-' . $request->user_image->getClientOriginalName();
            $request->user_image->move(public_path('images/profile-images/'), $user_image);

            $old_image = public_path('images\\profile-images\\') . $user->user_image;
            File::delete($old_image);
        }



        $user->name = $request->name;
        $user->gender = $request->gender;

        $user->email = $request->email;

        if($request->password != '')
        {
            $user->password = Hash::make($request->password);
        }

        $user->user_image = $user_image;

        $user->save();

        return redirect('profile')->with('success', 'Profile Details Updated');
    }
}
