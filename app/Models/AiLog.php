<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'prompt',
        'response',
        'model',
        'error_message',
        'request_id',
        'call_site',
    ];
}
