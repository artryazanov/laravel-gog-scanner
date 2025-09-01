<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameVideo extends Model
{
    protected $table = 'gog_game_videos';
    protected $fillable = ['game_id','provider','video_key','title','source']; // source: listing|detail
}
