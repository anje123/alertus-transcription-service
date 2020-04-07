<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RobbieP\CloudConvertLaravel\CloudConvert;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Core\ExponentialBackoff;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use App\TranscribeInfo;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class TranscribeController extends Controller
{

    public function __construct()
    { 
        $this->path = public_path('audio-contents/');
        $this->apikey = config('cloudconvert.api_key');
        $this->bucket_name = env('GOOGLE_CLOUD_STORAGE_BUCKET', 'femmy2');
        $this->processed = 'processed';
        $this->processing = 'processing';
        $this->not_processed = 'not_processed';
        $this->failed = 'failed';

    }

    /**
     * Initializes the SpeechClient
     * @return object \SpeechClient
     */
    public function createInstance()
    {
        $project_id = env('PROJECT_ID');
        $speech = new SpeechClient([
            'projectId' => $project_id,
            'languageCode' => 'en-US',
        ]);
        return $speech;
    }

    public function test()
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('transcribe_queue', false, true, false, false);
        $callback = function($msg) {
            //Convert the data to array
            $data = json_decode($msg->body, true);
            Log::info($data);
            foreach ($data as $sdata) {
                TranscribeInfo::create([
                    'recording_url' => $sdata['response'],
                    'recording_sid' => $sdata['recording_sid']
                ]);
            }

     
            echo "Finished Processing\n";
        };
        $channel->basic_consume('transcribe_queue', '', false, false, false, false, $callback);

        //Listen to requests
        while (count($channel->callbacks)) {
            $channel->wait();
        }

    }

    public function transcribe()
    {
        $questionResponse = TranscribeInfo::where('transcribe_status',$this->not_processed)->first();
        if(!$questionResponse){
            return;
        }
        $this->transcribeFromUrl($questionResponse);
    }

    public function transcribeFromUrl($questionResponse)
    {
       $start_time = date("h:i:sa");
       Log::info($start_time);
       $audio = $questionResponse->recording_url;
       $_filename = $questionResponse->recording_sid;
       Log::info($questionResponse->recording_url);
       $audioFile = $this->convertFile($audio, $_filename);

        // change these variables if necessary
        $encoding = AudioEncoding::FLAC;
        $sampleRateHertz = 44100;
        $languageCode = 'en-NG';

        // get contents of a file into a string
        $content = file_get_contents($audioFile);

        // set string as audio content
        $audio = (new RecognitionAudio())
            ->setContent($content);

        // set config
        $config = (new RecognitionConfig())
            ->setEncoding($encoding)
            ->setSampleRateHertz($sampleRateHertz)
            ->setLanguageCode($languageCode);

        // create the speech client
        $client = new SpeechClient();
        $result_str = '';

        try {
            $response = $client->recognize($config, $audio);
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                $mostLikely = $alternatives[0];
                $transcript = $mostLikely->getTranscript();
                $confidence = $mostLikely->getConfidence();
                printf('Transcript: twilio %s' . PHP_EOL, $transcript);
                printf('Confidence: %s' . PHP_EOL, $confidence);
                $result_str .= $transcript;
                $end_time = date("h:i:sa");
            } 
            } catch(Exception $e){
                $this->updateTranscribingStatusIfFailed($questionResponse);

            }finally {
                $client->close();
            }
       
        $this->updateTranscribeStatusWhenTranscribed($result_str,$questionResponse,$start_time, $end_time);
        $this->deleteFile($_filename);
        printf($result_str);

    }

    public static function getFilename($name)
    {
        return $name.".flac";
    } 

    public function convertFile($audio,$_filename)
    {
        $cloudconvert = new CloudConvert([

            'api_key' => $this->apikey
        ]);

        $filename = self::getFilename($_filename);

        try {

            $cloudconvert->file($audio)->to($this->path . $filename);

        } catch (ClientException $e) {
           
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

         $file_path = $this->path.$filename;
         return $file_path;
    }

    public function deleteFile($filename)
    {
        $file = $this->path.$filename;

        $filesystem = new Filesystem;

        if ($filesystem->exists($file)) {

            $filesystem->delete($file);
        }
        return;
    }

 
    public function updateTranscribeStatusWhenTranscribed($result_str, $questionResponse,$start_time, $end_time)
    {
        $questionResponse->transcribe_status = $this->processed;
        $questionResponse->transcription = $result_str;
        $questionResponse->start_time = $start_time;
        $questionResponse->end_time = $end_time;
        $questionResponse->save();
        Log::info('Transcription start-time: '.$start_time);
        Log::info('Transcription end-time: '.$end_time);
        Log::info('Transcription: '.$result_str);
        Log::info('Transcription status: '.$this->processed);

    }


    public function updateTranscribingStatusIfFailed($questionResponse)
    {
        $questionResponse->transcribe_status = $this->failed;
        $questionResponse->save();
    }

}
