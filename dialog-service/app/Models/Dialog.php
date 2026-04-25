<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dialog extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'dialogs';

    protected $fillable = [
        'name',
        'creater_user_id',
    ];

    protected $dates = ['deleted_at'];

    public function messages()
    {
        return $this->hasMany(DialogMessage::class, 'dialog_id');
    }

    public function dialogUsers()
    {
        return $this->hasMany(DialogUser::class, 'dialog_id');
    }
}
