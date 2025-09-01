<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameGallery extends Model
{
    protected $table = 'gog_game_galleries';
    public $timestamps = false;
    protected $fillable = ['game_id','image_url'];
}
