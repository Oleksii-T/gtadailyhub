<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorialApiEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['request_id', 'editorial_key', 'entity_type', 'entity_id', 'operation', 'payload_hash', 'before_summary', 'after_summary', 'result'];

    protected $casts = ['before_summary' => 'array', 'after_summary' => 'array', 'result' => 'array', 'created_at' => 'datetime'];
}
