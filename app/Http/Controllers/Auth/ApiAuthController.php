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
                'token' => $token,
            ], 201);
        } else {
            Log::error('Failed to save user.');
            return response()->json(['error' => 'Provide proper details'], 400);
        }
    }

    public function login2(Request $request)
    {
        $user = adminUsers::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        Log::info('User successfully logged in: ' . $user->email);

        return response()->json([
            'message' => 'Successfully logged in!',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    public function login(Request $request)
    {
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
            'token' => $token,
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

    public function Getuser()
    {
        $user = adminUsers::all();
        return response()->json($user);
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'services' => 'sometimes|required|array', // Ensure services is an array
        ]);

        $user = adminUsers::findOrFail($id);

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

        $user->save();

        return response()->json(['message' => 'User updated successfully', $user]);
    }

    public function getUserById($id)
    {
        $user = adminUsers::findOrFail($id);
        return response()->json($user);
    }

    public function delete($id)
    {
        $user = adminUsers::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

 
    


    public function changePassword(Request $request, $id)
{
    // Validate request data
    // $request->validate([
    //     "email" => "required|email|unique:users,email,$id",
    //     "password" => "required|string|min:6",
    // ]);

    // Find the user by ID
    $user = adminUsers::find($id);

    // Check if user exists
    if (!$user) {
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }

    // Hash the password
    $hashedPassword = Hash::make($request->password);

    // Update the user's email and password
    $user->email = $request->email;
    $user->password = $hashedPassword;
    $user->save();

    // Return success response
    return response()->json([
        'message' => 'User updated successfully',
        'user' => $user,
    ]);
}







    public function getUserCredentials($id)
    {
        $user = adminUsers::findOrFail($id);


        return response()->json([
            'email' => $user->email,
            'password' => $user->password, // This will be the hashed password
        ]);
    }
}
