<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calls extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'caller',
        'callee',
        'callerNumber',
        'calleeNumber',
        'inputs',
        'state',
        'direction',
        'type',
        'timestamp',
        'duration',
        'agents',
        'is_added',
        'recording'
    ];
}
