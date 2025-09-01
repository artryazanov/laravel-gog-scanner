<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameWorksOn extends Model
{
    protected $table = 'gog_game_works_on';
    protected $fillable = ['game_id','windows','mac','linux'];
}
