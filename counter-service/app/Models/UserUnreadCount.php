<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUnreadCount extends Model
{
    protected $table = 'user_unread_counts';

    protected $fillable = [
        'user_id',
        'total_unread',
    ];

    public $timestamps = true;
}
