<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameSupportedSystem extends Model
{
    protected $table = 'gog_game_supported_systems';
    public $timestamps = false;
    protected $fillable = ['game_id','system'];
}
