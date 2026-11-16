<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{

    public function index()
    {
        if (Auth::check()) {
            // dd('User is authenticated', Auth::user());
            return redirect()->route('admin-dashboard');
        } else {
            return view('auth.login');
        }
    }

    public function authenticationApi(Request $request)
    {
        $log = Log::channel('login');
        $log->info("Login attempt email => {$request->email}, time => ", [now()]);
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
            ]);
            if ($validator->fails()) {
                $log->warning("Login validation failed for email => {$request->email}, errors => ", $validator->messages()->toArray());
                return errorResponse(null, $validator->messages()->first(), 100, 500);
            }
            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                $log->info("Login successful for email => {$request->email}, time => ", [now()]);
                $user = Auth::user();
                $user->save();
                if ($user->user_type == 'customer') {
                    $log->warning("Login failed for email => {$request->email}, user type => {$user->user_type} is not allowed to login.");
                    return errorResponse(null, "Oppes! You have entered invalid credentials", 401, 200);
                }

                if ($user->status == 0) {
                    $log->warning("Login failed for email => {$request->email}, user type => {$user->user_type} is not allowed to login.");
                    return errorResponse(null, "Oppes! Your account has been deactivated", 401, 200);
                }


                $token = $user->createToken('api')->accessToken;
                $user['token'] = $token;
                $user['token_type'] = 'Bearer ';
                $log->info("Token created for user id => {$user->id}, time => ", [now()]);
                return successResponse($user, "Great! You have Successfully loggedin", 0, 200);
            }
            $log->warning("Login failed for email => {$request->email}, invalid credentials.");
            return errorResponse(null, "Oppes! You have entered invalid credentials", 401, 200);
        } catch (Exception $e) {
            $log->error("Login error for email => {$request->email}, message => {$e->getMessage()}");
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }


    public function logoutApi(Request $request)
    {
        try {
            $userJson = $request->input('userJson');

            if (!$userJson) {
                return response()->json(['message' => 'No user data provided'], 400);
            }

            $userData = json_decode($userJson, true);

            if (!isset($userData['result']['id']) || !isset($userData['result']['token'])) {
                return response()->json(['message' => 'Invalid user data'], 400);
            }

            $userId = $userData['result']['id'];
            $token = $userData['result']['token'];

            $user = User::find($userId);

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Revoke the user's token
            if ($token) {
                $user->tokens()->where('id', $token)->delete();
            }

            Auth::logout();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out!',
                'clearLocalStorage' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function logout()
    {
        try {
            Auth::logout();
            return successResponse(null, 'Successfully logged out', 0, 200);
        } catch (Exception $e) {
            return errorResponse(null, $e->getMessage(), 100, 500);
        }
    }
}
