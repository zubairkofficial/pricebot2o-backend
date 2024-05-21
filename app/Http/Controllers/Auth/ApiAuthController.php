<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\adminUsers;

use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate input
        $request->validate([
           
            'services' => 'required|array', // Ensure services is an array
        ]);
    
        // Log the raw services input and its type
        // Log::info('Raw services input: ' . json_encode($request->input('services')));
        // Log::info('Type of services input: ' . gettype($request->input('services')));
    
        $permissions = $request->input('services');
        Log::info('Permissions array: ' . json_encode($permissions));
    
        // Create new user
        $user = new adminUsers([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'services' => $permissions, // Directly assign the array
        ]);
    
        Log::info('User data before saving: ' . json_encode($user->toArray()));
    
        if ($user->save()) {
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;
    
            Log::info('User successfully saved.');
    
            return response()->json([
                'message' => 'Successfully created user!',
                'accessToken' => $token,
            ], 201);
        } else {
            Log::error('Failed to save user.');
            return response()->json(['error' => 'Provide proper details'], 400);
        }
    }
    
    public function login2(Request $request)
{
    // Validate input
    // $request->validate([
    //     'email' => 'required|email',
    //     'password' => 'required|string|min:6',
    // ]);

    // Retrieve the user by email
    $user = adminUsers::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        // If the user doesn't exist or the password is incorrect
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Create a new personal access token
    $tokenResult = $user->createToken('Personal Access Token');
    $token = $tokenResult->plainTextToken;

    // Log the successful login
    Log::info('User successfully logged in: ' . $user->email);

    // Return the access token and user information
    return response()->json([
        'message' => 'Successfully logged in!',
        'accessToken' => $token,
        'user' => $user,
    ], 200);
}
    
    public function login(Request $request)
    {
        // $request->validate([
        //     'email' => 'required|string|email',
        //     'password' => 'required|string',
        //     'remember_me' => 'boolean'
        // ]);

        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }


    public function Getuser(){
        $user = adminUsers::all();
        return response()->json($user);
    }


    public function updateUser(Request $request, $id)
    {
        // Validate input
        $request->validate([
            
            'services' => 'sometimes|required|array', // Ensure services is an array
        ]);

        // Find user by ID
        $user = adminUsers::findOrFail($id);

        // Update user attributes
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password =  bcrypt($request->password);
        }
        if ($request->has('services')) {
            $user->services = $request->services;
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully' , $user]);
    }


    public function getUserById($id)
{
    $user = adminUsers::findOrFail($id);
    return response()->json($user);
}



public function delete($id)
    {
        // Find the user by ID
        $user = adminUsers::find($id);

        // Check if user exists
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Delete the user
        $user->delete();

        // Return success response
        return response()->json(['message' => 'User deleted successfully'], 200);
    }

}

