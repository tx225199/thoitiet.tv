<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class UserController extends Controller
{
    public function ajaxLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $html = View::make('site.widgets.user-slot', [
                'user' => Auth::user()
            ])->render();

            return response()->json([
                'status' => true,
                'html' => $html,
                'is_login' => true
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Incorrect email or password.'
        ]);
    }

    public function ajaxRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        $html = View::make('site.widgets.user-slot', [
            'user' => $user
        ])->render();

        return response()->json([
            'status' => true,
            'html' => $html,
            'is_login' => true
        ]);
    }

    public function ajaxUpdateProfile(Request $request)
    {
        $userId = Auth::user()->id;
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Please log in again.'
            ]);
        }

        if ($request->filled('password') && !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect.'
            ]);
        }

        $user->name = $request->input('name', $user->name);

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return response()->json(['status' => true]);
    }

    public function profile()
    {
        if (!Auth::check()) {
            return redirect('/')->with('error', 'You must be logged in to access this page.');
        }

        $user = Auth::user();

        return view('site.user.profile', [
            'user' => $user
        ]);
    }

}
