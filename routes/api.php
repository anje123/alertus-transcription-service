<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:api']], function () {
    
Route::get('/transcribe',
    ['uses' => 'API\TranscriptionController@transcribedResponse']
);

Route::post('/transcribeFromUrl',
    ['uses' => 'API\TranscriptionController@transcribeResponseFromUrl']
);

Route::get('/transcribe/Session/{id}',
    ['uses' => 'API\TranscriptionController@getTranscribedResponseBySessionId']
);

Route::get('/transcribe/{id}',
    ['uses' => 'API\TranscriptionController@getTranscribedResponseById']
);

Route::get('/transcribeResponses',
    ['uses' => 'API\TranscriptionController@getAllTranscribedResponses']
);

});
