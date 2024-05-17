<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Models\Calls;
use App\Models\Credentials;

class AmoCrmController extends Controller
{

    /**
     * Integration Initialisation
     */

    public function auth()
    {
        $data = Credentials::where('id', 1)->first();
        return view('amo.auth', compact('data'));
    }

    /**
     * Response/Callback URL assigned in AMOCRM API
     */
    public function callback(Request $request)
    {
        $data = $this->genToken($request->code);
        return response()->json($data);
    }

    /**
     * Test Form
     */

    public function form()
    {
        return view('amo.index');
    }


    /**
     * Test Method to Add Lead to AMOCRM API
     */

    public function getAccountInfo(Request $request)
    {

        //Test Limit
        $records = Calls::where('is_added', 0)->get()->toArray();


        foreach($records as $call){
            //Call Info

            $agent = json_decode($call['agents']);
            $name = $agent[0]->name;
            $lead  = [
                [
                    "name" => $call['state'] . ' - ' . $name,
                    "price" => 0,
                    "responsible_user_id" => 0,
                    "pipeline_id" => 8150934,
                    "_embedded" => [
                        "contacts" => [
                            [
                                "first_name" => $call['calleeNumber'],
                                "responsible_user_id" => 0,
                                "updated_by" => 0,
                                "custom_fields_values" => [
                                    [
                                        "field_id" => 393491,
                                        "values" => [
                                            [
                                                "enum_id" => 477953,
                                                "value" => $call['calleeNumber']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                    
                    
                ]
            ];

            $response = $this->createLead($request, $lead);

            
           
            $entity_id = $response[0]['id'];
            
            // dd($entity_id);
            //Add notes
            $note = [
                [
                    "entity_id" => $entity_id,
                    "note_type" => "common",
                    "params" => [
                        "text" => $call['agents'],
                    ]
                ]
            ];

           $this->createNote($request, $note);

           $note1 = [
            [
                "entity_id" => $entity_id,
                "note_type" => "common",
                "params" => [
                    "text" => 'Callee Number: ' . $call['calleeNumber'] . ' | Call Duration: ' . $call['duration'],
                ]
            ]
        ];

       $this->createNote($request, $note1);

            //Call Data
            $callData = [
                [
                    "entity_id" => $entity_id,
                    "note_type" => "call_out",
                    "params" => [
                        "uniq" => $agent[0]->identifier,
                        "duration" => $call['duration'],
                        "source" => "Maqsam",
                        "link" => "https://amo.naicatech.com/storage/mp3/recording_1715587689.mp3",
                        "phone" => $call['calleeNumber']
                    ]
                ]
            ];

           $this->createNote($request, $callData);


           //Update Record Status

           $update = Calls::find($call['id']);
           $update->update(['is_added' => 1]);

        }
        return $response;
    }


    /**
     * Generate new Token if the Token Expires
     */
    private function refreshToken($refreshToken)
    {
        $newAccessToken = $this->getNewToken();
        return $newAccessToken;
    }

    /**
     *  Generate Token after Auth
     *  @Param $code = taken from Callback URL response parameter
     */

    public function genToken($code){
        $regenToken = Credentials::where('id', 1)->first();
        $subdomain = 'ehproperties'; // Replace 'test' with your actual subdomain
        $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token';

        // Data for the request
        $data = [
            'client_id' => $regenToken->client_id, // Replace with your client ID
            'client_secret' => $regenToken->client_secret, // Replace with your client secret
            'grant_type' => 'authorization_code',
            'code' => $code, // Replace with the authorization code
            'redirect_uri' => env('AMO_CLIENT_REDIRECT_URI'), // Replace with your redirect URI
        ];

        try {
            $response = Http::post($link, $data);

            $statusCode = $response->status();

            if ($statusCode < 200 || $statusCode > 204) {
                throw new Exception('Error: ' . $response->body(), $statusCode);
            }

            $responseData = $response->json();
            // Process $responseData as needed
            $access_token = $response['access_token']; //Access токен
            $refresh_token = $response['refresh_token']; //Refresh токен
            $token_type = $response['token_type']; //Тип токена
            $expires_in = $response['expires_in']; //Через сколько действие токена истекает

            $records = Credentials::find(1);

            $records->access_token = $access_token;
            $records->refresh_token = $refresh_token;
            $records->expires_in = $expires_in;
            $records->token_type = $token_type;
            $records->code = $code;
            $records->save();

            session(['access_toke' => $access_token, 'refresh_token' => $refresh_token, 'token_type' => $token_type, 'expires_in' => $expires_in]);

            return $responseData;
            // return response()->json($responseData);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
        }

    }

    /**
     * Method to Regerate New Token from AMOCRM API
     */

    public function getNewToken()
    {
        $regenToken = Credentials::where('id', 1)->first();

        // dd($regenToken);
        $subdomain = 'ehproperties'; // Replace 'test' with your actual subdomain
        $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token';

        // Data for the request
        $data = [
            'client_id' => $regenToken->client_id, // Replace with your client ID
            'client_secret' => $regenToken->client_secret, // Replace with your client secret
            'grant_type' => 'refresh_token',
            'refresh_token' => $regenToken->refresh_token, // Replace with the authorization code
            'redirect_uri' => env('AMO_CLIENT_REDIRECT_URI'), // Replace with your redirect URI
        ];

        try {
            $response = Http::post($link, $data);

            $statusCode = $response->status();

            if ($statusCode < 200 || $statusCode > 204) {
                throw new Exception('Error: ' . $response->body(), $statusCode);
            }

            $responseData = $response->json();
            // Process $responseData as needed
            $access_token = $response['access_token']; //Access токен
            $refresh_token = $response['refresh_token']; //Refresh токен
            $token_type = $response['token_type']; //Тип токена
            $expires_in = $response['expires_in']; //Через сколько действие токена истекает

            $records = Credentials::find(1);

            $records->access_token = $access_token;
            $records->refresh_token = $refresh_token;
            $records->expires_in = $expires_in;
            $records->token_type = $token_type;

            $records->save();

            session(['access_toke' => $access_token, 'refresh_token' => $refresh_token, 'token_type' => $token_type, 'expires_in' => $expires_in]);
            return response()->json($responseData);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }


    /**
     * Create Lead Method To Push Record to AMOCRM API
     */

    public function createLead(Request $request, $data)
    {
        $subdomain = 'ehproperties'; // Replace 'test' with your actual subdomain
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/complex';

        // Replace 'xxxx' with your actual access token
        $access_token = session('access_toke');
        $refreshToken = session('refresh_token');
        // dd(session('refresh_token'));

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
            ])->post($link, $data);

            $code = $response->status();

            if ($code === 401) {
                // Access token expired, refresh it
                $accessToken = $this->refreshToken($refreshToken);
                
                // Retry the request with the new access token
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get($link);
            }

            if ($code < 200 || $code > 204) {
                throw new Exception('Error: ' . $response->body(), $code);
            }

            // Process $response as needed
            return $response->json();
        } catch (Exception $e) {

            // dd($e->getCode());
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }

    /**
     * Create Note Method to Push Record in AMOCRM API
     */
    public function createNote(Request $request, $data)
    {
        $subdomain = 'ehproperties'; // Replace 'test' with your actual subdomain
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/notes';

        // Replace 'xxxx' with your actual access token
        $access_token = session('access_toke');
        $refreshToken = session('refresh_token');
        // dd(session('refresh_token'));

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $access_token,
            ])->post($link, $data);

            $code = $response->status();

            if ($code === 401) {
                // Access token expired, refresh it
                $accessToken = $this->refreshToken($refreshToken);
                
                // Retry the request with the new access token
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get($link);
            }

            if ($code < 200 || $code > 204) {
                throw new Exception('Error: ' . $response->body(), $code);
            }

            // Process $response as needed
            return $response->json();
        } catch (Exception $e) {

            // dd($e->getCode());
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }

    /**
     * Test GET Methods
     */

     public function getData(Request $request, $endpoint)
        {
            $subdomain = 'ehproperties'; // Replace 'test' with your actual subdomain
            $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/' .$endpoint;

            // Replace 'xxxx' with your actual access token
            $access_token = session('access_toke');
            $refreshToken = session('refresh_token');
            // dd(session('refresh_token'));

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $access_token,
                ])->get($link);

                $code = $response->status();

                if ($code === 401) {
                    // Access token expired, refresh it
                    $accessToken = $this->refreshToken($refreshToken);
                    
                    // Retry the request with the new access token
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                    ])->get($link);
                }

                if ($code < 200 || $code > 204) {
                    throw new Exception('Error: ' . $response->body(), $code);
                }

                // Process $response as needed
                return $response->json();
            } catch (Exception $e) {

                // dd($e->getCode());
                return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
            }
        }

        public function getOthers()
        {
            $endpoint = 'leads/29173631';
            $subdomain = 'ehproperties'; // Replace 'test' with your actual subdomain
            $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/' .$endpoint;

            // Replace 'xxxx' with your actual access token
            $access_token = session('access_toke');
            $refreshToken = session('refresh_token');
            // dd(session('refresh_token'));

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $access_token,
                ])->get($link);

                $code = $response->status();

                if ($code === 401) {
                    // Access token expired, refresh it
                    $accessToken = $this->refreshToken($refreshToken);
                    
                    // Retry the request with the new access token
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                    ])->get($link);
                }

                if ($code < 200 || $code > 204) {
                    throw new Exception('Error: ' . $response->body(), $code);
                }

                // Process $response as needed
                return $response->json();
            } catch (Exception $e) {

                // dd($e->getCode());
                return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
            }
        }


}
