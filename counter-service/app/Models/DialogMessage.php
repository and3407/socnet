<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialogMessage extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'dialog_messages';

    protected $fillable = [
        'dialog_id',
        'author_user_id',
        'content',
    ];

    protected $dates = ['deleted_at'];

    public function dialog()
    {
        return $this->belongsTo(Dialog::class, 'dialog_id');
    }

    // author_user_id is just integer, no relation
}
