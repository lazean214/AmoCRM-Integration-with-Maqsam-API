<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CrmController;
use App\Models\Calls;
use App\Models\Credentials;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected array $messageFile = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Fetch and process data in batches
        $url = "https://api.maqsam.com/v1/calls";
        $response = Http::withBasicAuth(env('MAQSAM_ACCESS_ID'), env('MAQSAM_SECRET'))->get($url);
        $messageData = array();
        $msg = "";
        $responseData = ['msg' => '', 'data' => null]; // Initialize response data

        if ($response->successful()) {
            $apiResult = $response->json();
            $messages = $apiResult['message'];

            foreach ($messages as $message) {
                if($message['state'] == 'in_progress'){
                    Log::info('Skipped in progress');
                    continue;
                }
                try {
                    $recordingUrl = $this->getRecordingUrl($message['id']);
                    $messageData = [
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
                    ];

                    if (Calls::where('call_id', $message['id'])->exists()) {
                        continue; // Skip if the record already exists
                    }
                        Calls::create($messageData);
                        $this->messageFile = $messageData;
                        $this->parseData();
                    
                } catch (\Exception $e) {
                    // Log the exception or handle it as required
                    // For example:
                    Log::error('Error processing message: ' . $e->getMessage());
                    continue; // Continue to the next iteration
                }

            }
       
            $msg = 'Success fetch data from API.';
        } else {
            $msg = 'Failed to fetch data from API.';
        }
        Log::info($msg);
        return $msg;
    }
    /**
     * Fetch and store the recording for a call
     */

    private function parseData(){
        $saveLead = new CrmController($this->createLead(), "lead");
        $saveLead = $saveLead->pushData();
        $entity_id = $saveLead[0]['id'];
        $saveNote= new CrmController($this->createNote($entity_id), "note");
        $saveNote = $saveNote->pushData();
        $saveCallee= new CrmController($this->createCallee($entity_id), "note");
        $saveCallee = $saveCallee->pushData();
        $saveRecording = new CrmController($this->createRecording($entity_id), "note");
        $saveRecording = $saveRecording->pushData();
        $response['lead'] = $saveLead;
        $response['note'] = $saveNote;
        $response['callee'] = $saveCallee;
        $response['recording'] = $saveRecording;
        return $response;
    }

    private function createLead() : array {
        $call = $this->messageFile;
        $agent = json_decode($call['agents']);
        $name = $agent[0]->name;
        return [[
            "name" => $call['state'] . ' - ' . $call['timestamp'] . ' - ' . $name,
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
            
        ]];
    }

   
    private function createNote($entity_id) : array {
        $call = $this->messageFile;
        return [
            [
                "entity_id" => $entity_id,
                "note_type" => "common",
                "params" => [
                    "text" => $call['agents'],
                ]
            ]
        ];
    }

    private function createCallee($entity_id) : array {
        $call = $this->messageFile;
        return [
            [
                "entity_id" => $entity_id,
                "note_type" => "common",
                "params" => [
                    "text" => $call['calleeNumber'] . ' | Duration: ' . $call['duration'] . ' | Recording Link: ' . $call['recording'],
                ]
            ]
        ];
    }

    private function createRecording($entity_id) : array {
        $call = $this->messageFile;
        return [
            [
                "entity_id" => $entity_id,
                "note_type" => "call_out",
                "params" => [
                    "uniq" => '8f52d38a-5fb3-406d-93a3-a4832dc28f8b',
                    "duration" => $call['duration'],
                    "source" => 'Maqsam API',
                    "link" => $call['recording'],
                    "phone" => '+'.$call['calleeNumber'],
                ]
            ]
        ];
    }
   


    private function getRecordingUrl($id)
    {
        $url = "https://api.maqsam.com/v1/recording/" . $id;
        $response = Http::withBasicAuth(env('MAQSAM_ACCESS_ID'), env('MAQSAM_SECRET'))
                    ->get($url);
        
        if ($response->successful()) {
            $mp3Content = $response->body();
            $filename = 'recording_' . time() . '.mp3';
            Storage::disk('public')->put('mp3/' . $filename, $mp3Content);
            return url(Storage::url('mp3/' . $filename));
        } else {
            return "Not Available";
        }
    }
}
