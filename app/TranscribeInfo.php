<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TranscribeInfo extends Model
{
    //
    protected $fillable = ['start_time','recording_url','end_time', 'recording_sid','transcribe_status','transcription'];
    

}
