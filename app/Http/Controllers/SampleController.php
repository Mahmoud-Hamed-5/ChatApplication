<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use Session;
use App\Model\User;


class SampleController extends Controller
{
    function index()
    {
        return view('Auth.login');
    }


    function register()
    {

    }


    function logout()
    {

    }

}
