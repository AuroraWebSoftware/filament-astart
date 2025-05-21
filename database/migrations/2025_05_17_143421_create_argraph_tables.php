<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {

        Schema::create('argraph_chatflow_states', function (Blueprint $table) {
            $table->id();
            $table->string('thread')->unique();
            $table->string('next_step')->nullable();
            $table->timestamps();
        });

        Schema::create('argraph_chatflow_state_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('argraph_chatflow_state_id')->constrained();
            $table->string('tag')->nullable();
            $table->string('argraph_prism_class_type'); // UserMessage, AssistantMessage, ToolResultMessage, SystemMessage
            $table->text('content')->nullable(); // gelen giden  text veri
            $table->json('tool_calls')->nullable(); // asistant mesajı içinde bulunan array
            $table->json('tool_results')->nullable(); // çalışan took'ların çktısını veren array
            $table->json('additional_content')->nullable();
            $table->json('provider_options')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('argraph_chatflow_states');
        Schema::dropIfExists('argraph_chatflow_state_messages');
    }
};
