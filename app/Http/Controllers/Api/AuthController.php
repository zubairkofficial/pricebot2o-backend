<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{User,Service,Organization};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'services' => 'required|array',
        ]);
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        if($request->services){
            $user->services = $request->services;
        }
        if($request->org_id){
            $user->org_id = $request->org_id;
        }
        $user->save();

        $token = $user->createToken('user_token')->plainTextToken;
        return response()->json([
            "message" => "You are registered successfully. Please verify your email to continue",
            "user" => $user,
            "token" => $token,
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $email = $request->email;
        $password = $request->password;
        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            // if (Auth::user()->hasVerifiedEmail()) {
                $user = Auth::user();
                $token = $user->createToken('user_token')->plainTextToken;
                return response()->json([
                    "message" => "Logged in successfully",
                    "user" => $user,
                    "token" => $token,
                ], 200);
            // } else {
            //     return response()->json(["message" => "Your email is not verified."], 422);
            // }
        } else {
            return response()->json(["message" => "Invalid email or password"], 422);
        }
    }

    public function changePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
        $user->password = Hash::make($request->password);
        $user->save();

        // Return success response
        return response()->json([
            'message' => 'Password updated successfully',
            'user' => $user,
        ]);
    }


    public function getuser($id){
        $user = User::with('organization')->findOrFail($id);
        $services_ids = Service::all()->keyBy('id');
        $services = Service::all();
        $orgs = Organization::all();
        if ($user->services) {
            $user->service_names = collect($user->services)->map(function ($serviceId) use ($services_ids) {
                return $services_ids->get($serviceId)->name ?? '';
            })->toArray();
        }

        return response()->json(['user' => $user, 'services' => $services, 'orgs' => $orgs], 200);
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'services' => 'sometimes|array', // Ensure services is an array
        ]);

        $user = User::findOrFail($id);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }
        if ($request->has('services')) {
            $user->services = $request->services;
        }
        if($request->has('org_id')){
            $user->org_id = $request->org_id;
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully', $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }


    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }


}
