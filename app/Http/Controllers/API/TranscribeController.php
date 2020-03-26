<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use RobbieP\CloudConvertLaravel\CloudConvert;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Core\ExponentialBackoff;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use App\ResponseTranscription;
use App\TranscribeInfo;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use App\Http\Controllers\BaseController as BaseController;


class TranscribeController extends BaseController
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
            'languageCode' => 'en-NG',
        ]);
        return $speech;
    }

   

  
    public function transcribeApiCall(Request $request)
    {
       $start_time = date("h:i:sa");       
       $audio = $request->recording_url;
       $_filename = $request->recording_sid;
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
                $result_str .= $transcript;
                $end_time = date("h:i:sa");
            } 
            } catch(Exception $e){
                Log::error($e);
            }finally {
                $client->close();
            }
       
        $this->updateTranscribeStatusWhenTranscribed($request,$result_str,$start_time, $end_time);
        $this->deleteFile($_filename);

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

    public function updateTranscribeStatusWhenTranscribed($request,$result_str,$start_time, $end_time)
    {
      $transcribe = TranscribeInfo::create([
            'start_time' => $start_time,
            'end_time' => $end_time,
            'transcribe_status' => $this->processed,
            'recording_sid' => $request->recording_sid,
            'transcription' => $result_str
        ]);
        return response()->json($transcribe);

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

    public function transcribedResponse()
    {
        $transcribe = TranscribeInfo::all();
        return response()->json($transcribe);
    }

    public function transcribedResponseById($id)
    {
        $transcribe = TranscribeInfo::find($id);
        return response()->json($transcribe);  
    }
}
