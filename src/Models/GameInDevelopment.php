<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameInDevelopment extends Model
{
    protected $table = 'gog_game_in_developments';
    protected $fillable = ['game_id','active','until'];
}
