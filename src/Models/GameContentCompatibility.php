<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameContentCompatibility extends Model
{
    protected $table = 'gog_game_content_compatibilities';
    protected $fillable = ['game_id','windows','osx','linux'];
}
