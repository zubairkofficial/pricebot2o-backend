<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\DataProcess;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DataProcessController extends Controller
{
    public function fetchDataProcess(Request $request)
    {
        // Increase maximum execution time
        set_time_limit(600); // 10 minutes
        $validated = $request->validate([
            'document' => 'required|file',
        ]);

        $file = $request->file('document');
        $fileName = $file->getClientOriginalName();

        // API URL
        $url = 'https://dhn.services/datasheet_process';

        // Static credentials
        $username = 'api_user';
        $password = 'g*f>G31B=9D7';

        // Create a Guzzle client with increased timeout
        $client = new Client([
            'timeout' => 600,
        ]);

        try {
            $startTime = microtime(true);

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
                        'name'     => 'document',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $fileName,
                    ],
                ],
            ]);

            $endTime = microtime(true);
            // Log::info("Request completed in " . ($endTime - $startTime) . " seconds.");

            // Get and process the response
            $responseBody = $response->getBody()->getContents();
            $contentType = $response->getHeader('Content-Type')[0];

            if (strpos($contentType, 'application/json') !== false) {
                $responseText = json_decode($responseBody, true);
                $tempFilePath = $this->createExcelFile($responseText, $fileName);
            } else {
                // Handle binary response
                $tempFilePath = tempnam(sys_get_temp_dir(), 'response') . '.xlsx';
                file_put_contents($tempFilePath, $responseBody);
            }

            // Save the response to the DataProcess table
            DataProcess::create([
                'file_name' => $fileName,
                'data' => base64_encode($responseBody), // Store the binary data as a base64 encoded string
            ]);

            // Return the Excel document as a download
            return response()->download($tempFilePath, 'API_Response_' . pathinfo($fileName, PATHINFO_FILENAME) . '.xlsx')->deleteFileAfterSend(true);

        } catch (RequestException $e) {
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            Log::error("Failed to upload file: " . $errorResponse);
            return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
        }
    }

    /**
     * Create an Excel file from the response data.
     *
     * @param array $responseText
     * @param string $fileName
     * @return string
     */
    private function createExcelFile($responseText, $fileName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add header row
        $sheet->setCellValue('A1', 'Key');
        $sheet->setCellValue('B1', 'Value');

        // Add data rows
        $row = 2;
        foreach ($responseText as $key => $value) {
            $sheet->setCellValue('A' . $row, $key);
            $sheet->setCellValue('B' . $row, is_array($value) ? json_encode($value) : $value);
            $row++;
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'response') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);

        return $tempFilePath;
    }
}
