<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameAvailability extends Model
{
    protected $table = 'gog_game_availabilities';
    protected $fillable = ['game_id','is_available','is_available_in_account'];
}
