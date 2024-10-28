<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Mail;
use App\Mail\TranscripMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\{Email, ApiKey, Organization, GeneratedNumber};


class VoiceController extends Controller
{

    public function transcribe(Request $request)
    {

        $apiKey = env('CHAT_GPT_KEY');
        $model = env('CHAT_GPT_MODEL');

        $deepgramapi = ApiKey::where('name','Deepgram')->select('key')->first()->key;;

        $user_id = Auth::user()->id;

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
                'body' => $audioFile,
            ]);

            $transcription = json_decode($response->getBody()->getContents(), true);

            return response()->json(['transcription' => $transcription]);
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            return response()->json([
                'error' => $errorMessage,
                'details' => json_decode($responseBody)
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }

    public function generateSummary(Request $request)
    {
        $transcriptionText = $request->input('recordedText');
        $apiKey = ApiKey::where('name','OpenAI')->select('key')->first()->key;




        $user_id = Auth::user()->id;
        $customPrompt = Auth::user()->organization?->prompt;

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
            return response()->json([
                'success' => false,
                'error' => " OpenAI API Request Failed. "
            ], 500);
        }

        // Method 2: Generating structured JSON
        $jsonModel = 'gpt-4o';
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

        $numberResponse = $jsonResponse ? json_decode($jsonResponse, true) : null;

        $thema = $numberResponse['topic'] ?? null;
        $Niederlassungsleiter = $numberResponse['author'] ?? null;
        $datum = $numberResponse['date'] ?? null;
        $formattedDatum = $datum ? date('d-m-y', strtotime(str_replace('.', '-', $datum))) : null;
        $number = $numberResponse['shareholder'] ?? null;
        // $formattedNumber = $number ? str_replace('.', '', $number) : null;
        $Teilnehmer = $numberResponse['participant'] ?? null;
        // $BM = $numberResponse['branch_manager'] ?? null;
        $totalTokens = $body['usage']['total_tokens'] ?? null;

        $TeilnehmerString = is_array($Teilnehmer) ? implode(', ', $Teilnehmer) : $Teilnehmer;

        $generatedSummary = GeneratedNumber::create([
            // 'number' => $formattedNumber,
            'Thema' => $thema,
            'Datum' => $formattedDatum,
            'Teilnehmer' => $TeilnehmerString,
            // 'BM' => $BM,
            'Niederlassungsleiter' => $Niederlassungsleiter,
        ]);

        if ($totalTokens !== null && $user_id !== null) {
            try {
                // DB::table('users')
                //     ->where('id', $user_id)
                //     ->increment('voice_tool', $totalTokens);

                $pricePerToken = 0.03 / 1000;
                $totalPrice = $totalTokens * $pricePerToken;
                $totalPrice = round($totalPrice, 5);

                Log::info('log', [$totalPrice]);

                // Increment the total price for the user
                // DB::table('users')
                //     ->where('id', $user_id)
                //     ->increment('voice_price', $totalPrice);

            } catch (\Exception $e) {
                Log::error("Updating total tokens failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'summary_id'=> $generatedSummary->id,
            'summary' => $summaryResponse,
            'json_summary' => $jsonResponse,
        ]);
    }

    public function sendEmail(Request $request)
    {
        $data = [
            'email' => $request->input('email'),
            'transcriptionText' => $request->input('transcriptionText'),
            'listeningText' => $request->input('listeningText'),
            'summary' => $request->input('summary'),
            'date' => $request->input('date'),
            'theme' => $request->input('theme'),
            'participants' => $request->input('participants'),
            'author' => $request->input('author'),
        ];

        try {

            $user = Auth::user();
            if ($user && $user->send_email !== $data['email']) {
                $user->send_email = $data['email'];
                $user->save();
            }

            Email::create([
                'email' => $data['email'],
                'transcriptionText' => $data['transcriptionText'],
                'summary' => $data['summary'],
                'date' => $data['date'],
                'theme' => $data['theme'],
                'participants' => $data['participants'],
                'author' => $data['author'],
            ]);

            // Send email
            Mail::to($data['email'])->send(new TranscripMail($data));

            return response()->json(['message' => 'Email sent successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSentEmails()
    {
        return response()->json(['emails' => Email::all()], 200);
    }

    public function getemailId($userId)
    {
        return response()->json(['emails' => Email::where('id', $userId)->get()], 200);
    }

    public function sendResend(Request $request)
    {
        $data = [
            'title' => $request->input('title'),
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'transcriptionText' => $request->input('transcriptionText'),
            'summary' => $request->input('summary'), // Add the summary to the data array
        ];

        try {
            // Send email
            Mail::to($data['email'])->send(new TranscripMail($data));

            return response()->json(['message' => 'Email sent successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getData()
    {
        return response()->json(Organization::all(), 200);
    }

    public function getLatestNumber($summary_id)
    {

        $latestData = GeneratedNumber::where('id',$summary_id)->first();
        return response()->json($latestData, 200);
    }
}
