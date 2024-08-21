<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ToolController extends Controller
{
    // Get all tools
    public function index()
    {
        return response()->json(Tool::all(), 200);
    }

    // Store a new tool
    public function store(Request $request)
{
    // dd($request->all());
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    $tool = new Tool();
    $tool->name = $request->name;
    $tool->description = $request->description;

    // Handling file upload
    if ($request->hasFile('image')) {
        $imageName = time().'.'.$request->image->extension();
        $request->image->move(public_path('images'), $imageName);
        $tool->image = $imageName;
    }

    $tool->save();

    return response()->json(['message' => 'Data saved successfully','data'=>$tool], 201);
}


    // Get a single tool by ID
    public function show($id)
    {
        $tool = Tool::find($id);
        if (!$tool) {
            return response()->json(['message' => 'Tool not found'], 404);
        }

        return response()->json($tool, 200);
    }

    // Update a tool
    public function update(Request $request, $id)
{
    $tool = Tool::find($id);

    if (!$tool) {
        return response()->json(['message' => 'Tool not found'], 404);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $tool->name = $request->name;
    $tool->description = $request->description;

    if ($request->hasFile('image')) {
        // Delete old image if exists
        if ($tool->image && file_exists(public_path('images/' . $tool->image))) {
            unlink(public_path('images/' . $tool->image));
        }

        $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
        $request->image->move(public_path('images'), $imageName);
        $tool->image = $imageName;
    }

    $tool->save();

    return response()->json([
        'message' => 'Data updated successfully',
        'data' => $tool
    ], 200);
}


    // Delete a tool
    public function destroy($id)
    {
        $tool = Tool::find($id);
        if (!$tool) {
            return response()->json(['message' => 'Tool not found'], 404);
        }

        $tool->delete();

        return response()->json(['message' => 'Tool deleted successfully'], 200);
    }
}
