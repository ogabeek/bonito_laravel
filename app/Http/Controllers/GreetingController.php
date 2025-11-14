<?php

namespace App\Http\Controllers;


class GreetingController extends Controller
{
    public function show($name, $ip)
    {
        return view('greeting', ['name' => $name, 'ip'=>$ip]);
    }
    //
}
