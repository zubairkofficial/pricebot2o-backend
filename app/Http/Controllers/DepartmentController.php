<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    //
    public function CreateDepartment(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|unique:departments,name',
            'status' => 'required|string',
            'prompt' => 'required|string',
        ]);

        $name = $request->input('name');
        $status = $request->input('status');
        $prompt = $request->input('prompt');

        try {
            // Insert data into the departments table using the DB facade
            DB::table('departments')->insert([
                'name' => $name,
                'status' => $status,
                'prompt' => $prompt,
            ]);

            return response()->json(['message' => 'Department created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Department is already created'], 409);
        }
    }

    public function GetDepartments()
    {
        try {
            $departments = DB::table('departments')->get();
            return response()->json($departments, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch departments'], 500);
        }
    }

    public function deleteDepartment($departmentToDelete)
    {
        // Check if the role exists
        $role = DB::table('departments')->find($departmentToDelete);

        if (!$role) {
            return response()->json(['message' => 'departments not found'], 404);
        }

        // Delete the role from the roles table
        DB::table('departments')->where('id', $departmentToDelete)->delete();

        return response()->json(['message' => 'departments deleted successfully'], 200);
    }

    public function getDepartmentById($id)
    {
        try {
            $department = DB::table('departments')->where('id', $id)->first();
            if (!$department) {
                return response()->json(['message' => 'Department not found'], 404);
            }
            return response()->json($department, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch department'], 500);
        }
    }

    public function UpdateDepartment(Request $request, $departmentId)
    {
        // // Validate the request
        // $request->validate([
        //     'name' => 'required|string|unique:departments,name,' . $departmentId,
        //     'status' => 'required|string',
        //     'prompt' => 'required|string',
        // ]);

        try {
            // Find the department by ID and update it
            DB::table('departments')
                ->where('id', $departmentId)
                ->update([
                    'name' => $request->input('name'),
                    'status' => $request->input('status'),
                    'prompt' => $request->input('prompt'),
                ]);

            return response()->json(['message' => 'Department updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update department'], 500);
        }
    }

    public function getUserByIdd($id)
    {
        // Fetch the user by ID
        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Return user details as JSON
        return response()->json($user);
    }

}
