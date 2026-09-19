<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappSession extends Model
{
    protected $fillable = ['name', 'session_id', 'api_key', 'status'];
}
