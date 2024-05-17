<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Calls;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MaqsamController extends Controller
{

    /**
     * Fetch Call Records from Maqsam API
     */
    public function maqsam(Request $request)
    {
        $url = "https://api.maqsam.com/v1/calls";

        try {
            $response = Http::withBasicAuth(env('MAQSAM_ACCESS_ID'), env('MAQSAM_SECRET'))
                        ->get($url);
            
            // Check if the request was successful
            if ($response->successful()) {
                $apiResult = $response->json();
                
                // Check if the 'result' key exists and if its value is 'success'
                if (isset($apiResult['result']) && $apiResult['result'] === 'success') {
                    $messages = $apiResult['message'];

                    // Process messages in batches
                    $batchSize = 10; // Adjust batch size as needed
                    $chunks = array_chunk($messages, $batchSize);

                    foreach ($chunks as $chunk) {
                        $this->processBatch($chunk);
                    }

                    return response()->json(['message' => 'Calls stored successfully'], 201);
                } else {
                    return response()->json(['error' => 'API result is not successful'], 500);
                }
            } else {
                return response()->json(['error' => 'HTTP request failed'], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error occurred: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Process method to reduce memory usage.
     */
    protected function processBatch($messages)
    {
        foreach ($messages as $message) {
            $existingCall = Calls::where('call_id', $message['id'])->first();

            if ($existingCall) {
                continue;
            }

            try {
                $recordingUrl = $this->getRecordingUrl($message['id']);

                Calls::create([
                    'caller' => $message['caller'],
                    'callee' => $message['callee'],
                    'callerNumber' => $message['callerNumber'],
                    'calleeNumber' => $message['calleeNumber'],
                    'agents' => json_encode($message['agents']),
                    'state' => $message['state'],
                    'direction' => $message['direction'],
                    'type' => $message['type'],
                    'timestamp' => date('Y-m-d H:i:s', $message['timestamp']),
                    'duration' => $message['duration'],
                    'is_added' => 0,
                    'call_id' => $message['id'],
                    'recording' => $recordingUrl,
                ]);
            } catch (\Exception $e) {
                // Log the error or handle it as needed
                continue; // Skip this message and continue with the next one
            }
        }
    }

    /**
     * Fecth Recording & Store to server from Maqsam API
     */
    protected function getRecordingUrl($id)
    {
        $url = "https://api.maqsam.com/v1/recording/" . $id;
        $response = Http::withBasicAuth(env('MAQSAM_ACCESS_ID'), env('MAQSAM_SECRET'))
                    ->get($url);
        
        if ($response->successful()) {
            $mp3Content = $response->body();
            $filename = 'recording_' . time() . '.mp3';
            Storage::disk('local')->put('mp3/' . $filename, $mp3Content);
            return url(Storage::url('mp3/' . $filename));
        } else {
            throw new \Exception('Failed to fetch recording for ID: ' . $id);
        }
    }
}
