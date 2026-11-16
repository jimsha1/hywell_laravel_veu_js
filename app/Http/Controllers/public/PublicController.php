<?php

namespace App\Http\Controllers\public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicController extends Controller
{
    public function showLoginPage()
    {
        return view('public.public-login');
    }
    public function showHomePage()
    {
        return view('public.public-home');
    }
    public function privacy()
    {
        return view('public.privacy-policy');
    }

    public function removedAccount()
    {
        return view('public.remove-my-account');
    }

    public function removedAccountPost(Request $request)
    {
        Log::info($request->all());
    }


}
