<?php

namespace AuroraWebSoftware\FilamentAstart\ArGraph\Models;

use Illuminate\Database\Eloquent\Model;

class ChatflowState extends Model
{
    protected $table = 'argraph_chatflow_states';

    protected $fillable = [
        'thread',
        'parametric_memory',
    ];

    protected $casts = [
        'parametric_memory' => 'array',
    ];
}
