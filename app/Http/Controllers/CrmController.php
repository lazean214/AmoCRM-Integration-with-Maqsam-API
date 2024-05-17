<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Models\Calls;
use App\Models\Credentials;

class CrmController extends Controller
{
    private string $accessToken;
    private string $refreshToken;
    private string $leadUrl = "https://subdomain.amocrm.ru/api/v4/leads/complex";
    private string $notesUrl = "https://subdomain.amocrm.ru/api/v4/leads/notes";
    private string $endpoint;
    private array $pushData;
    public function __construct($data, $type) {
        $this->tokens();
        $this->pushData = $data;
        $this->endpoint = $type === 'lead' ? $this->leadUrl : $this->notesUrl;
    }

    public function pushData()
    {
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->accessToken])->post($this->endpoint, $this->pushData);
            $code = $response->status();
            
            if ($code === 401) {
             
              $this->refreshToken();
             
            }

            if ($code < 200 || $code > 204) {
                throw new Exception('Error: ' . $response->body(), $code);
            }
            return $response->json();
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }
    private function tokens() :void  {

        $this->refreshToken();
    }


    public function refreshToken()
    {
        $regenToken = Credentials::where('id', 1)->first();

        $subdomain = 'test'; // Replace 'test' with your actual subdomain
        $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token';

        // Data for the request
        $data = [
            'client_id' => $regenToken->client_id,
            'client_secret' => $regenToken->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $regenToken->refresh_token, 
            'redirect_uri' => env('AMO_CLIENT_REDIRECT_URI'), 
        ];

        try {
            $response = Http::post($link, $data);

            $statusCode = $response->status();

            if ($statusCode < 200 || $statusCode > 204) {
                throw new Exception('Error: ' . $response->body(), $statusCode);
            }

            $responseData = $response->json();
          
            $records = Credentials::find(1);

            $records->access_token = $response['access_token'];
            $records->refresh_token = $response['refresh_token'];
            $records->expires_in = $response['token_type'];
            $records->token_type =$response['expires_in'];

            $records->save();

            $this->accessToken = $response['access_token'];
            $this->refreshToken = $response['refresh_token'];

            return response()->json($responseData);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }
   
}
