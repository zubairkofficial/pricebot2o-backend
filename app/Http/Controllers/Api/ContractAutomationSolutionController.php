<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\ContractSolutions;

class ContractAutomationSolutionController extends Controller
{
    public function fetchContractAutomation(Request $request)
    {
        // Increase maximum execution time
        set_time_limit(300);

        // Validate that the document and doctype are present in the request
        $validated = $request->validate([
            'document' => 'required|file',
            'doctype' => 'required|string',
        ]);

        $file = $request->file('document');
        $doctype = $validated['doctype'];
        $fileName = $file->getClientOriginalName();

        // Log::info('Request Data:', $validated);
        // Log::info('File Path: ' . $file->getPathname());
        // Log::info('File Mime Type: ' . $file->getMimeType());
        // Log::info('File Original Name: ' . $fileName);

        // API URL
        $url = 'https://dhn.services/contract_automation';

        // Static credentials
        $username = 'api_user';
        $password = 'g*f>G31B=9D7';

        // Create a Guzzle client with increased timeout
        $client = new Client([
            'timeout' => 300,
        ]);

        try {
            $response = $client->post($url, [
                'auth' => [$username, $password],
                'multipart' => [
                    [
                        'name'     => 'username',
                        'contents' => $username,
                    ],
                    [
                        'name'     => 'password',
                        'contents' => $password,
                    ],
                    [
                        'name'     => 'doctype',
                        'contents' => $doctype,
                    ],
                    [
                        'name'     => 'document',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $fileName,
                    ],
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            Log::info('Raw Response: ' . substr($responseBody, 0, 300) . '...');

            // Save the raw response to the database
            ContractSolutions::create([
                'file_name' => $fileName,
                'doctype' => $doctype,
                'data' => base64_encode($responseBody), // Store the binary data as a base64 encoded string
            ]);

            return response()->json(['message' => 'File uploaded and response data saved successfully']);
        } catch (RequestException $e) {
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
        }
    }
}
