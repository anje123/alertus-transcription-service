# ALERTUS Transciption System

## Guidelines

ALERTUS Transciption System uses Google-Speech-to-Text to transcribe audio file to readable text


HOW TO SETUP:
* Create An Account With Google Cloud check https://cloud.google.com/speech-to-text/
* Create a /google-credential/key.json File
* Store the API key Json in file created above the check https://cloud.google.com/speech-to-text/docs/              quickstart-gcloud for quickstart
* Create account on Cloudconvert to generate an API key check https://cloudconvert.com/ for more info
<<<<<<< HEAD
* Please Note the CloudConvert Platform is useful for converting the audio file to FLAC before transcription for more efficiency. check https://cloud.google.com/speech-to-text/docs/encoding for more info
* clone this repo
* composer install
* php artisan migrate, to migrate table
* cp .env.example .env fill the Keys in the .env accordingly with the API keys generated above

## RESTful URLs
* To transcribe an audio URL:
=======
* Please Note the CloudConvert Platform is useful for converting the audio file to FLAC before transcription for      more efficiency. check https://cloud.google.com/speech-to-text/docs/encoding for more info
* composer install
* cp .env.example .env fill the Keys in the .env accordingly with the API keys generated above

## RESTful URLs
* To transcribe a URL:
>>>>>>> 1893d0c2024d538beaa3599f52668fe3cd9141ea
    * POST /api/transcribe
    field: recording_sid,recording_url
* To get all Transcription:
    * GET /api/transcribe
* To get One Transcription By Id:
    * GET /api/transcribe/{id}
## HTTP Verbs

| HTTP METHOD | POST            | GET       | PUT         | DELETE |
| ----------- | --------------- | --------- | ----------- | ------ |
<<<<<<< HEAD
| CRUD OP     | CREATE          | READ      | UPDATE      | DELETE |
=======
| CRUD OP     | CREATE          | READ      | UPDATE      | DELETE |
>>>>>>> 1893d0c2024d538beaa3599f52668fe3cd9141ea
