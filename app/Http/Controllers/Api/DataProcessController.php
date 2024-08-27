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

        set_time_limit(600); // 10 minutes
        $validated = $request->validate([
            'document' => 'required|file',
        ]);

        $file = $request->file('document');
        $fileName = $file->getClientOriginalName();

        $url = 'https://dhn.services/datasheet_process';

        $username = 'api_user';
        $password = 'g*f>G31B=9D7';

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

            $responseBody = $response->getBody()->getContents();
            $contentType = $response->getHeader('Content-Type')[0];

            if (strpos($contentType, 'application/json') !== false) {
                $responseText = json_decode($responseBody, true);

                $tempFilePath = $this->createExcelFile($responseText, $fileName);
            } else {

                $tempFilePath = tempnam(sys_get_temp_dir(), 'response') . '.xlsx';
                file_put_contents($tempFilePath, $responseBody);
            }

            DataProcess::create([
                'file_name' => $fileName,
                'data' => base64_encode($responseBody),
            ]);

            return response()->download($tempFilePath, 'API_Response_' . pathinfo($fileName, PATHINFO_FILENAME) . '.xlsx')->deleteFileAfterSend(true);
        } catch (RequestException $e) {
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return response()->json(['message' => 'Failed to upload file', 'error' => $errorResponse], $e->getCode() ?: 400);
        }
    }

    private function createExcelFile($responseText, $fileName)
    {


        if (isset($responseText['response'])) {
            $responseText = json_decode($responseText['response'], true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $columnIndex = 'B';

        $rowIndex = 1;

        $sheet->setCellValue('A' . $rowIndex, 'SR_No');
        $sheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);

        $sheet->setCellValue('A' . ($rowIndex + 1), '1');

        foreach ($responseText as $key => $value) {

            $sheet->setCellValue($columnIndex . $rowIndex, $key);
            $sheet->getStyle($columnIndex . $rowIndex)->getFont()->setBold(true);

            if (is_array($value)) {
                $sheet->setCellValue($columnIndex . ($rowIndex + 1), implode(', ', $value)); // Handle arrays by joining with a comma
            } else {
                $sheet->setCellValue($columnIndex . ($rowIndex + 1), $value);
            }

            $columnIndex++;
        }

        $tempFilePath = tempnam(sys_get_temp_dir(), 'response') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);

        return $tempFilePath;
    }
}
