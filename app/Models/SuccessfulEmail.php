<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuccessfulEmail extends Model
{
    protected $fillable = [
        'affiliate_id', 'envelope', 'from', 'subject',
        'dkim', 'SPF', 'spam_score', 'email', 'raw_text',
        'sender_ip', 'to', 'timestamp',
    ];

    public $timestamps = false; // todo: could be removed after fixing db import

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('raw_text')->orWhere('raw_text', '');
    }

}
