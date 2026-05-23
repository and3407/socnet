<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialogUnreadCount extends Model
{
    protected $table = 'dialog_unread_counts';

    protected $fillable = [
        'dialog_id',
        'user_id',
        'unread_count',
    ];

    public $timestamps = true;
}
