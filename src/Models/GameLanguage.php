<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameLanguage extends Model
{
    protected $table = 'gog_game_languages';
    public $timestamps = false;
    protected $fillable = ['game_id','code','name'];
}
