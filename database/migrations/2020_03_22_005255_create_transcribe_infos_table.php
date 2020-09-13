<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTranscribeInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transcribe_infos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('transcription')->nullable();
            $table->string('recording_sid');
            $table->string('session_sid');
            $table->string('recording_url');
            $table->string('transcribe_status')->default('not_processed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transcribe_infos');
    }
}
