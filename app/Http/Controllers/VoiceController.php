<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use Illuminate\Support\Facades\Mail;
use App\Mail\Transcrip;
// use Carbon\Carbon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\{Email,CompanyNumber,GeneratedNumber};


class VoiceController extends Controller
{
    //

    public function getPromptFromDatabase()
    {
        try {
            // Fetch all prompts from the 'prompts' table
            $prompts = DB::table('prompts')->get();

            if ($prompts->isEmpty()) {
                // If no prompts are found in the database, return an empty array
                return [];
            }

            return $prompts;
        } catch (\Exception $e) {
            // Log the specific error message for debugging
            Log::error('Failed to retrieve prompts from database: ' . $e->getMessage());

            // Return a generic error response
            return "Failed to retrieve prompts from database.";
        }
    }


    public function transcribe(Request $request)
    {

        $apiKey = env('CHAT_GPT_KEY');
        $model = env('CHAT_GPT_MODEL');

        $deepgramapi = env('DEEPGRAM_KEY');
        log::info('log', [$deepgramapi]);

        $userLoginId = $request->input('user_login_id');

        try {
            $audioFile = fopen($request->file('audio')->getPathName(), 'r');
            $client = new Client();
            $response = $client->request('POST', 'https://api.deepgram.com/v1/listen?model=whisper-small&detect_language=true', [
                'headers' => [
                    'Authorization' => 'Token ' . $deepgramapi,
                    'Content-Type' => 'audio/mp3',
                    'language' => 'de',
                    'numerals' => true,
                ],
                // 'json' => [
                //     'language' => 'de',
                //     'numerals' => true,
                // ],
                'body' => $audioFile,
            ]);

            $transcription = json_decode($response->getBody()->getContents(), true);

            // Apply numerals conversion to the transcribed text
            $transcribedText = $transcription['results']['channels'][0]['alternatives'][0]['transcript'];
            $summary = $this->summarizeTranscription2($transcribedText, $apiKey, $model, $userLoginId);

            // Generate summary
            $summary = $this->summarizeTranscription($transcribedText, $apiKey, $model, $userLoginId);

            return response()->json(['transcription' => $transcription, 'summary' => $summary]);
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            return response()->json([
                'error' => $errorMessage,
                'details' => json_decode($responseBody)
            ], 500);
        } catch (\Exception $e) {
            // Catch any other exceptions and return a generic error message
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }



    private function summarizeTranscription($transcriptionText, $apiKey, $model, $userLoginId)
    {
        $client = new Client();

        try {
            // Fetch the prompt from the database
            // $prompt = DB::table('prompts')->value('prompt');
            // log::info('log' , [$prompt]);

            // if (!$prompt) {
            //     return response()->json(['error' => 'Prompt not found in database'], 404);
            // }

            $prompt = "
          
        $transcriptionText
        
        Das Format der Zusammenfassung sollte wie folgt aussehen:
        
        Allgemein
        [TEXT_HERE]
        Vertriebsthemen
        [TEXT_HERE]
        Einkaufsthemen
        [TEXT_HERE]
        Aufgaben
        [TEXT_HERE]
        Eigenmarke
        [TEXT_HERE]
        
        Der Text lautet \"$transcriptionText\"
        Bitte fassen Sie den Text in diesem Format zusammen und machen Sie die Überschriften fett. 
        ";

            $response = $client->request('POST', 'https://api.openai.com/v1/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo-instruct',
                    'prompt' => $prompt,
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                    'top_p' => 1.0,
                    'frequency_penalty' => 0.0,
                    'presence_penalty' => 0.0,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            Log::info('summary ' . json_encode($body));

            // Get the total tokens used
            $totalTokens = $body['usage']['total_tokens'] ?? 0;

            if ($totalTokens > 0 && $userLoginId) {
                // Log the incoming tokens
                Log::info('Incrementing tokens for user2', [
                    'user_login_id' => $userLoginId,
                    'incoming_tokens' => $totalTokens,
                ]);

                $pricePerToken = 0.03 / 1000; // Adjust the price per token as necessary
                $totalPrice = $totalTokens * $pricePerToken;
                $totalPrice = round($totalPrice, 5);

                log::info('log', [$totalPrice]);


                // Increment the total price for the user
            //     DB::table('users')
            //         ->where('id', $userLoginId)
            //         ->increment('voice_price', $totalPrice);


            //     // Increment the total tokens for the user
            //     DB::table('users')
            //         ->where('id', $userLoginId)
            //         ->increment('voice_tool', $totalTokens);
            }

            // Extract the actual text from the response
            return $body['choices'][0]['text'] ?? 'Summary generation failed';
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Catch Guzzle-specific errors
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorBody = json_decode($response->getBody()->getContents(), true);
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown error';
            } else {
                $statusCode = 500;
                $errorMessage = $e->getMessage();
            }
            Log::error('Error from OpenAI API: ' . $errorMessage);
            return response()->json(['error' => $errorMessage], $statusCode);
        } catch (\Exception $e) {
            // Catch all other errors
            $errorMessage = $e->getMessage();
            Log::error('General error: ' . $errorMessage);
            return response()->json(['error' => 'An error occurred: ' . $errorMessage], 500);
        }
    }



    private function summarizeTranscription2($transcriptionText, $apiKey, $model, $userLoginId)
    {
        $client = new Client();

        try {
            $prompt = "Wir haben eine deutsche Transkription aus einer aufgezeichneten Audiodatei, die wir zusammenfassen müssen. 
            Beispiel für eine Audio-Transkription
            $transcriptionText.
            
            Wir benötigen ein Format für die Zusammenfassung, das generiert wird. 
            Die Zusammenfassung muss im JSON-Format vorliegen.
            {
             'date': DATE_HERE,
              'topic': TOPIC_HERE,
              'shareholder': 'Number_here',
              'participant': 'Teilnehmer hier mit Bezeichnung, falls vorhanden',
              'author': 'Autorenname hier',
               'branch_manager' : ' Niederlassungsleiter name '

              'general_information': 'Der vollständige Überblick/die Zusammenfassung im Detail für die Transkription',
              'sales_topic': 'Die Vertriebsthemen hier definieren',
              'tasks': 'Die Aufgaben hier hinzufügen',
              'author_message': 'Nachricht vom Autor basierend auf der Transkription',
              'summary': 'Alle Parameter einschließlich aller Details und deren Verknüpfung, um eine Zusammenfassung in Textform zu erstellen'
            }
            
            Beispiel für 'summary' (Dieses Beispiel dient nur zur Erläuterung, wie wir es benötigen)
            
            Datum: 24-10-23 
            Thema: SPS Bauen + Modernisieren 
            Aktionär: 143922 
            Teilnehmer: Olaf Jordan, Niederlassungsleiter
            Autor: Frank Große
            
            Allgemein
            Der Standort ist seit dem 1.1.2024 neu im SPS BuM. Herrn Jordan wurde das SPS und die Maßnahmen2024 vorgestellt. Insbesondere wurde auf die Kampagne zur Energetischen Sanierung eingegangen. Das Thema kommt sehr gut an. Problematisch wird die Umsetzung gesehen, weil Herr Jordan über 3 Mitarbeiter verfügt, die altersbedingt in naher Zukunft das Unternehmen verlassen werden und dann neben ihm nur ein weiterer Verkäufer zur Verfügung steht, welcher im Moment krank ist. Wichtig erachtet er, dass Kunden frühzeitig über die neuen Leistungen informiert werden.
            
            Vertriebsthemen
            Konzentration soll auf der Umsetzung der Kampagne KES gelegt werden.
            
            Einkaufsthemen
            nicht besprochen
            
            Aufgaben
            Umsetzung der digitalen Themen mit der Zentrale in Ebern absprechen. / KW 16 / Frank Große
            Mitarbeiter für KES motivieren und zur Schulung anmelden. / KW 20 / Olaf Jordan
            
            Eigenmarke
            Nicht besprochen
            
            
            Stellen Sie sicher, dass das Format korrekt ist und die Zusammenfassung gemäß den anderen Felddaten bereitgestellt wird und keine zusätzlichen Details enthält und im JSON-Format vorliegt
            ";

            $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messages' => [['role' => 'system', 'content' => $prompt]],
                    'model' => 'gpt-4-turbo',
                    'max_tokens' => 1024,
                    'temperature' => 0.5
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            // Log the API response for debugging
            Log::info('OpenAI API Response: ' . json_encode($body));

            // Get the total tokens used
            $totalTokens = $body['usage']['total_tokens'] ?? 0;

            // Increment the total tokens for the user
            if ($totalTokens > 0 && $userLoginId) {
                // Log the incoming tokens
                Log::info('Incrementing tokens for user2', [
                    'user_login_id' => $userLoginId,
                    'incoming_tokens' => $totalTokens,
                ]);
                $pricePerToken = 0.03 / 1000; // Adjust the price per token as necessary
                $totalPrice = $totalTokens * $pricePerToken;
                $totalPrice = round($totalPrice, 5);

                log::info('log', [$totalPrice]);


                // Increment the total price for the user
                // DB::table('users')
                //     ->where('id', $userLoginId)
                //     ->increment('voice_price', $totalPrice);

                // // Increment the total tokens for the user
                // DB::table('users')
                //     ->where('id', $userLoginId)
                //     ->increment('voice_tool', $totalTokens);
            }


            // Check if the response contains valid data and a number
            if (!isset($body['choices'][0]['message']['content'])) {
                Log::error('Summary generation failed: Invalid response from OpenAI API');
                return response()->json(['error' => 'Summary generation failed'], 500);
            }

            // Extract the generated number if available
            $numberResponse = json_decode($body['choices'][0]['message']['content'], true);
            $generatedNumber = isset($numberResponse['number']) ? preg_replace('/\./', '', $numberResponse['number']) : '';

            $thema = $numberResponse['topic'] ?? null;
            $Niederlassungsleiter = $numberResponse['author'] ?? null;
            $datum = $numberResponse['date'] ?? null;
            $formattedDatum = $datum ? date('d-m-y', strtotime(str_replace('.', '-', $datum))) : null;
            $number = $numberResponse['shareholder'] ?? null;
            $formattedNumber = $number ? str_replace('.', '', $number) : null;
            $Teilnehmer = $numberResponse['participant'] ?? null;
            $auter = $numberResponse['author'] ?? null;
            $BM = $numberResponse['branch_manager'] ?? null;
            $totalTokens = $body['usage']['total_tokens'] ?? null;

            // Convert the Teilnehmer array to a string
            $TeilnehmerString = is_array($Teilnehmer) ? implode(', ', $Teilnehmer) : $Teilnehmer;
            // Log Thema, Datum, number, and Teilnehmer
            Log::info('Thema: ' . $thema);
            Log::info('Datum: ' . $datum);
            Log::info('number: ' . $number);
            Log::info('Teilnehmer: ' . $TeilnehmerString);
            Log::info('Niederlassungsleiter: ' . $Niederlassungsleiter);
            Log::info('author: ' . $auter);
            Log::info('BM: ' . $BM);

            // Insert the generated data into the database if number is not empty
            if (!empty($number)) {
                GeneratedNumber::create([
                    'number' => $formattedNumber,
                    'Thema' => $thema,
                    'Datum' => $formattedDatum,
                    'Teilnehmer' => $TeilnehmerString,
                    'BM' => $BM,
                    'Niederlassungsleiter' => $Niederlassungsleiter,
                ]);
            }

            return $generatedNumber;
        } catch (\Exception $e) {
            Log::error('Error from OpenAI API: ' . $e->getMessage());
            return response()->json(['error from OpenAI API' => $e->getMessage()], 500);
        }
    }



    public function generateSummary(Request $request)
    {
        $transcriptionText = $request->input('recordedText');
        $apiKey = env('CHAT_GPT_KEY');
        $model = env('CHAT_GPT_MODEL');

    
        $userLoginId = $request->input('user_login_id');
        $customPrompt = $request->input('prompts');
    
        Log::info('Received transcription text: ' . $transcriptionText);
        Log::info('Received prompt: ' . $customPrompt);
    
        // Default prompt if no custom prompt is provided
        $defaultPrompt = "
        $transcriptionText
        
        Das Format der Zusammenfassung sollte wie folgt aussehen:
        
        Allgemein
        [TEXT_HERE]
        Vertriebsthemen
        [TEXT_HERE]
        Einkaufsthemen
        [TEXT_HERE]
        Aufgaben
        [TEXT_HERE]
        Eigenmarke
        [TEXT_HERE]
        
        Der Text lautet \"$transcriptionText\"
        Bitte fassen Sie den Text in diesem Format zusammen und machen Sie die Überschriften fett. 
        ";
    
        // Use the provided prompt or the default prompt
        $summaryPrompt = $customPrompt ? "$customPrompt\n\n$transcriptionText" : $defaultPrompt;
    
        Log::info('Using summary prompt: ' . $summaryPrompt);
    
        $client = new \GuzzleHttp\Client();
    
        // Method 1: Generating a summary
        $summaryModel = 'gpt-3.5-turbo-instruct';
    
        $summaryResponse = null;
        $body = null;
    
        try {
            $response = $client->request('POST', 'https://api.openai.com/v1/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $summaryModel,
                    'prompt' => $summaryPrompt,
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                    'top_p' => 1.0,
                    'frequency_penalty' => 0.0,
                    'presence_penalty' => 0.0,
                ],
            ]);
    
            $body = json_decode($response->getBody()->getContents(), true);
            Log::info('OpenAI API Response: ' . json_encode($body));
    
            $summaryResponse = $body['choices'][0]['text'] ?? 'Zusammenfassung konnte nicht generiert werden';
        } catch (\Exception $e) {
            Log::error("OpenAI GPT-3.5 Summary Request Failed: " . $e->getMessage());
        }
    
        // Method 2: Generating structured JSON
        $jsonModel = 'gpt-4-turbo';
        $jsonPrompt = " Wir haben eine deutsche Transkription aus einer aufgezeichneten Audiodatei, die wir zusammenfassen müssen. 
        Beispiel für eine Audio-Transkription
        $transcriptionText.
        
        Wir benötigen ein Format für die Zusammenfassung, das generiert wird. 
        Die Zusammenfassung muss im JSON-Format vorliegen.
        {
          'date': DATE_HERE,
          'topic': TOPIC_HERE,
          'shareholder': 'Number_here',
          'participant': 'Teilnehmer hier , falls vorhanden',
          'author': 'Autorenname hier',
         'branch_manager' : ' Niederlassungsleiter name '
          'general_information': 'Der vollständige Überblick/die Zusammenfassung im Detail für die Transkription',
          'sales_topic': 'Die Vertriebsthemen hier definieren',
          'tasks': 'Die Aufgaben hier hinzufügen',
          'author_message': 'Nachricht vom Autor basierend auf der Transkription',
          'summary': 'Alle Parameter einschließlich aller Details und deren Verknüpfung, um eine Zusammenfassung in Textform zu erstellen'
        }
        
        Beispiel für 'summary' (Dieses Beispiel dient nur zur Erläuterung, wie wir es benötigen)
        
        Datum: 24-10-23 
        Thema: SPS Bauen + Modernisieren 
        Aktionär: 143922 
        Teilnehmer: Olaf Jordan, Niederlassungsleiter
        Autor: Frank Große
        
        Allgemein
        Der Standort ist seit dem 1.1.2024 neu im SPS BuM. Herrn Jordan wurde das SPS und die Maßnahmen2024 vorgestellt. Insbesondere wurde auf die Kampagne zur Energetischen Sanierung eingegangen. Das Thema kommt sehr gut an. Problematisch wird die Umsetzung gesehen, weil Herr Jordan über 3 Mitarbeiter verfügt, die altersbedingt in naher Zukunft das Unternehmen verlassen werden und dann neben ihm nur ein weiterer Verkäufer zur Verfügung steht, welcher im Moment krank ist. Wichtig erachtet er, dass Kunden frühzeitig über die neuen Leistungen informiert werden.
        
        Vertriebsthemen
        Konzentration soll auf der Umsetzung der Kampagne KES gelegt werden.
        
        Einkaufsthemen
        nicht besprochen
        
        Aufgaben
        Umsetzung der digitalen Themen mit der Zentrale in Ebern absprechen. / KW 16 / Frank Große
        Mitarbeiter für KES motivieren und zur Schulung anmelden. / KW 20 / Olaf Jordan
        
        Eigenmarke
        Nicht besprochen
        
        Stellen Sie sicher, dass das Format korrekt ist und die Zusammenfassung gemäß den anderen Felddaten bereitgestellt wird und keine zusätzlichen Details enthält und im JSON-Format vorliegt";
    
        $jsonResponse = null;
        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'json' => [
                    'model' => $jsonModel,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant that converts text to structured JSON.'],
                        ['role' => 'user', 'content' => $jsonPrompt]
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 1500,
                    'top_p' => 1.0,
                    'frequency_penalty' => 0.0,
                    'presence_penalty' => 0.0,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 120,
            ]);
    
            $body = json_decode($response->getBody()->getContents(), true);
            $jsonResponse = $body['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error("OpenAI GPT-4 JSON Request Failed: " . $e->getMessage());
        }
    
        // Parse the JSON response to extract details if available
        $numberResponse = $jsonResponse ? json_decode($jsonResponse, true) : null;
    
        $thema = $numberResponse['topic'] ?? null;
        $Niederlassungsleiter = $numberResponse['author'] ?? null;
        $datum = $numberResponse['date'] ?? null;
        $formattedDatum = $datum ? date('d-m-y', strtotime(str_replace('.', '-', $datum))) : null;
        $number = $numberResponse['shareholder'] ?? null;
        $formattedNumber = $number ? str_replace('.', '', $number) : null;
        $Teilnehmer = $numberResponse['participant'] ?? null;
        $BM = $numberResponse['branch_manager'] ?? null;
        $totalTokens = $body['usage']['total_tokens'] ?? null;
    
        // Convert the Teilnehmer array to a string if it is an array
        $TeilnehmerString = is_array($Teilnehmer) ? implode(', ', $Teilnehmer) : $Teilnehmer;
    
        // Log Thema, Datum, number, and Teilnehmer
        Log::info('Thema: ' . $thema);
        Log::info('Datum: ' . $datum);
        Log::info('number: ' . $number);
        Log::info('Teilnehmer: ' . $TeilnehmerString);
        Log::info('auther: ' . $Niederlassungsleiter);
        Log::info('Total Tokens: ' . $totalTokens);
        Log::info('BM: ' . $BM);
    
        // Insert the generated data into the database, allowing for null values
        GeneratedNumber::create([
            'number' => $formattedNumber,
            'Thema' => $thema,
            'Datum' => $formattedDatum,
            'Teilnehmer' => $TeilnehmerString,
            'BM' => $BM,
            'Niederlassungsleiter' => $Niederlassungsleiter,
        ]);
    
        if ($totalTokens !== null && $userLoginId !== null) {
            try {
                // DB::table('users')
                //     ->where('id', $userLoginId)
                //     ->increment('voice_tool', $totalTokens);
    
                $pricePerToken = 0.03 / 1000; // Adjust the price per token as necessary
                $totalPrice = $totalTokens * $pricePerToken;
                $totalPrice = round($totalPrice, 5);
    
                Log::info('log', [$totalPrice]);
    
                // Increment the total price for the user
                // DB::table('users')
                //     ->where('id', $userLoginId)
                //     ->increment('voice_price', $totalPrice);
    
                // Log success message
                Log::info('Total tokens updated for user: ' . $userLoginId);
            } catch (\Exception $e) {
                // Log error if updating failed
                Log::error("Updating total tokens failed: " . $e->getMessage());
            }
        }
    
        return response()->json([
            'summary' => $summaryResponse,
            'json_summary' => $jsonResponse,
        ]);
    }
    
    
    









    public function sendEmail2(Request $request)
    {
        // Retrieve data from request
        $data = [
            'title' => $request->input('title'),
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'listeningText' => $request->input('listeningText'), // Use listeningText instead of transcriptionText
            'summary' => $request->input('summary'), // Add the summary to the data array
        ];

        try {
            // Insert data into the database
            // Email::create([
            //     'title' => $data['title'],
            //     'email' => $data['email'],
            //     'name' => $data['name'],
            //     'listeningText' => $data['listeningText'], // Use listeningText instead of transcriptionText
            //     'summary' => $data['summary'],
            // ]);

            // Send email
            Mail::to($data['email'])->send(new Transcrip($data));

            return response()->json(['message' => 'Email sent successfully'], 200);
        } catch (\Exception $e) {
            // Return error response with actual error message
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }






    public function sendEmail(Request $request)
    {
        // Retrieve data from request
        $data = [
            // 'title' => $request->input('title'),
            'email' => $request->input('email'),
            // 'name' => $request->input('name'),
            'transcriptionText' => $request->input('transcriptionText'),
            'listeningText' => $request->input('listeningText'),


            'summary' => $request->input('summary'), // Add the summary to the data array
            'date' => $request->input('date'), // Add date to the data array
            'theme' => $request->input('theme'), // Add theme to the data array
            'partnerNumber' => $request->input('partnerNumber'), // Add partnerNumber to the data array
            'branchManager' => $request->input('branchManager'), // Add branchManager to the data array
            'participants' => $request->input('participants'), // Add participants to the data array
            'author' => $request->input('author'), // Add author to the data array
        ];

        try {
            // Insert data into the database
            Email::create([
                // 'title' => $data['title'],
                'email' => $data['email'],
                // 'name' => $data['name'],
                'transcriptionText' => $data['transcriptionText'],
                'summary' => $data['summary'],
                'date' => $data['date'], // Insert date into the database
                'theme' => $data['theme'], // Insert theme into the database
                'partnerNumber' => $data['partnerNumber'], // Insert partnerNumber into the database
                'branchManager' => $data['branchManager'], // Insert branchManager into the database
                'participants' => $data['participants'], // Insert participants into the database
                'author' => $data['author'], // Insert author into the database
            ]);

            // Send email
            Mail::to($data['email'])->send(new Transcrip($data));

            return response()->json(['message' => 'Email sent successfully'], 200);
        } catch (\Exception $e) {
            // Return error response with actual error message
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getSentEmails()
    {
        try {
           
            $emails = Email::all(); 
           
            return response()->json(['emails' => $emails], 200);
        } catch (\Exception $e) {
           
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getemailId($userId)
    {
        try {
            
            $emails = Email::where('id', $userId)->get(); 

            
            return response()->json(['emails' => $emails], 200);
        } catch (\Exception $e) {
           
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    public function sendResend(Request $request)
    {
        // Retrieve data from request
        $data = [
            'title' => $request->input('title'),
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'transcriptionText' => $request->input('transcriptionText'),
            'summary' => $request->input('summary'), // Add the summary to the data array
        ];

        try {
            // Insert data into the database


            // Send email
            Mail::to($data['email'])->send(new Transcrip($data));

            return response()->json(['message' => 'Email sent successfully'], 200);
        } catch (\Exception $e) {
            // Return error response with actual error message
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getData()
    {
        // Assuming you have a table named 'your_table_name' in your database
        // Replace 'your_table_name' with the actual name of your table
        $data = CompanyNumber::all();
        

        // Check if data exists
        if ($data->isEmpty()) {
            return response()->json(['message' => 'No data found'], 404);
        }

        // If data exists, return it
        return response()->json(['data' => $data], 200);
    }




    public function getLatestNumber()
    {
        try {
            return GeneratedNumber::latest()->first(); // Retrieve the entire record
        } catch (\Exception $e) {
            Log::error('Error retrieving latest data from database: ' . $e->getMessage());
            return null;
        }
    }
}
