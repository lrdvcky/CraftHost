<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'ticket_id', 'user_id', 'body'
    ];

    public function ticket() { return $this->belongsTo(SupportTicket::class); }
    public function user() { return $this->belongsTo(User::class); }
}