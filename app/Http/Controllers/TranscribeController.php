<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use RobbieP\CloudConvertLaravel\CloudConvert;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Core\ExponentialBackoff;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use App\QuestionResponse;
use App\ResponseTranscription;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

class TranscribeController extends Controller
{
  
    public function __construct()
    { 
        $this->path = public_path('audio-contents/');
        $this->apikey = config('cloudconvert.api_key');
        $this->bucket_name = env('GOOGLE_CLOUD_STORAGE_BUCKET', 'femmy2');
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

    public function transcribe()
    {
        $questionResponse = QuestionResponse::where('transcribe_completed',0)->first();
        if(!$questionResponse){
            printf('no data to transcribe for now....');
            return;
        }
        if($questionResponse->storage_completed == 0){
            $this->transcribeFromTwilio($questionResponse);
        }else{
            $this->transcribeFromGoogleStorage($questionResponse);
        }
        
    }

    //
    public function transcribeFromTwilio($questionResponse)
    {
       $audio = $questionResponse->response . '.mp3';
       $_filename = $questionResponse->recording_sid;
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
            }
        } finally {
            $client->close();
        }

        $questionResponse->responseTranscription()->create(
            ['transcription' => $result_str]
        );
        $questionResponse->transcribe_completed = 1;
        $questionResponse->save();
        $this->deleteFile($_filename);
        printf($result_str);

    }

    public function transcribeFromGoogleStorage($questionResponse)
    {
                // change these variables if necessary
        $encoding = AudioEncoding::FLAC;
        $languageCode = 'en-NG';

        // set string as audio content
        $audio = (new RecognitionAudio())
            ->setUri('gs://femmy2/'.$questionResponse->response);

        // set config
        $config = (new RecognitionConfig())
            ->setEncoding($encoding)
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
                printf('Transcript: from gcs %s' . PHP_EOL, $transcript);
                printf('Confidence: %s' . PHP_EOL, $confidence);
                $result_str .= $transcript;
            }
        } finally {
            $client->close();
        }

        $questionResponse->responseTranscription()->create(
            ['transcription' => $result_str]
        );
        $questionResponse->transcribe_completed = 1;
        $questionResponse->save();
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
}
