<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameScreenshotImage extends Model
{
    protected $table = 'gog_game_screenshot_images';

    public $timestamps = false;

    protected $fillable = ['screenshot_id', 'formatter_name', 'image_url'];
}
