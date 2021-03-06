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

Route::post('/user/create',[ 'uses' => 'API\UserController@create']);

Route::group(['middleware' => ['auth:api']], function () {
Route::put('/user/update/token',[ 'uses' => 'API\UserController@updateToken']);
Route::put('/user/update',[ 'uses' => 'API\UserController@updateUser']);   

Route::get('/transcribe',
    ['uses' => 'API\TranscriptionController@transcribeResponse']
);

Route::post('/transcribeFromUrl',
    ['uses' => 'API\TranscriptionController@transcribeResponseFromUrl']
);

Route::get('/transcribe/Session/{id}',
    ['uses' => 'API\TranscriptionController@getTranscribedResponseBySessionId']
);

Route::get('/transcribe/Recording/{id}',
    ['uses' => 'API\TranscriptionController@getTranscribedResponseByRecordingId']
);

Route::get('/transcribe/{id}',
    ['uses' => 'API\TranscriptionController@getTranscribedResponseById']
);

Route::get('/transcribeResponses',
    ['uses' => 'API\TranscriptionController@getAllTranscribedResponses']
);

});
