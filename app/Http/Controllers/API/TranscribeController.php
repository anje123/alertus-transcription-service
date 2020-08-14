<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use RobbieP\CloudConvertLaravel\CloudConvert;
use Google\Cloud\Core\ExponentialBackoff;
use App\ResponseTranscription;
use App\TranscribeInfo;


class TranscribeController extends BaseController
{

    public function __construct()
    { 
        $this->path = public_path('audio-contents/');
        $this->apikey = config('cloudconvert.api_key');
        $this->bucket_name = env('GOOGLE_CLOUD_STORAGE_BUCKET', '');
        $this->processed = 'processed';
        $this->processing = 'processing';
        $this->not_processed = 'not_processed';
        $this->failed = 'failed';

    }


    public function getTranscribedResponse()
    {
        $transcribeData = DataInfo::all();
        return response()->json($transcribeData);
    }

    public function getTranscribedResponseById($id)
    {
        $transcribeData = TranscribeInfo::find($id);
        return response()->json($transcribeData);  
    }

    public function getTranscribedResponseBySessionId($SessionId)
    {
        $transcribeData = TranscribeInfo::where('recording_id', $SessionId);
        return response()->json($transcribeData); 
    }
}
