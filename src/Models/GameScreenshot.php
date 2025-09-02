<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameScreenshot extends Model
{
    protected $table = 'gog_game_screenshots';

    protected $fillable = ['game_id', 'image_id', 'formatter_template_url'];

    public function formattedImages(): HasMany
    {
        return $this->hasMany(GameScreenshotImage::class, 'screenshot_id');
    }
}
