<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialogUser extends Model
{
    use SoftDeletes;

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $table = 'dialog_users';

    protected $fillable = [
        'dialog_id',
        'user_id',
    ];

    protected $dates = ['deleted_at'];

    public function dialog()
    {
        return $this->belongsTo(Dialog::class, 'dialog_id');
    }
}
