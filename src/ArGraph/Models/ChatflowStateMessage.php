<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Models;

use Illuminate\Database\Eloquent\Model;

class ChatflowStateMessage extends Model
{
    protected $table = 'argraph_chatflow_state_messages';

    protected $fillable = ['argraph_chatflow_state_id', 'tag', 'argraph_prism_class_type', 'content', 'tool_calls', 'tool_results', 'additional_content', 'provider_options'];

    protected $casts = [
        'tool_calls' => 'array',
        'tool_results' => 'array',
        'additional_content' => 'array',
        'provider_options' => 'array',
    ];
}
