<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emailstatus extends Model
{
    use HasFactory;
    protected $table = 'email_status';
    protected $fillable = [
        'type', 'mail_from', 'mail_to', 'mail_subject', 'bounceType', 'error_response', 'timestamp'
    ];
}
