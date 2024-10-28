<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiKey;
use App\Models\ApiModel;

class ApiKeyController extends Controller
{
    public function getApiKeys()
    {
        // Fetch the OpenAI and Deepgram keys from the database
        $openAiKey = ApiKey::where('name', 'OpenAI')->first();
        $deepgramKey = ApiKey::where('name', 'Deepgram')->first();

        return response()->json([
            'openai_key' => $openAiKey ? $openAiKey->key : null,
            'deepgram_key' => $deepgramKey ? $deepgramKey->key : null,
        ], 200);
    }
    // Store or update an API key by name
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string',  // The API key itself
            // Ensure the api_model exists
        ]);

        // Check if an API key with this name already exists
        $apiKey = ApiKey::where('name', $validated['name'])->first();

        if ($apiKey) {
            // If the API key already exists, update the key and model
            $apiKey->update([
                'key' => $validated['key'],
               // 'api_model_id' => $validated['api_model_id'],
            ]);

            return response()->json(['message' => 'API key updated successfully'], 200);
        } else {
            // If the API key doesn't exist, create a new one
            ApiKey::create([
                'name' => $validated['name'],
                'key' => $validated['key'],
            //    'api_model_id' => $validated['api_model_id'],
            ]);

            return response()->json(['message' => 'API key created successfully'], 201);
        }
    }

    // Retrieve an API key by ID, including its model information
    public function show($id)
    {
        $apiKey = ApiKey::with('apiModel')->findOrFail($id);
        return response()->json($apiKey);
    }

    // Retrieve all available models (optional)
    public function apiModels()
    {
        return ApiModel::all();
    }


    public function addModel(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'provider_name' => 'required|string|max:255',
            'model_name' => 'required|string|max:255',
        ]);

        // Create the new model
        $model = ApiModel::create([
            'provider_name' => $validated['provider_name'],
            'model_name' => $validated['model_name'],
        ]);

        return response()->json(['message' => 'Model added successfully', 'model' => $model], 201);
    }
}
