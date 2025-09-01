<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameImages extends Model
{
    protected $table = 'gog_game_images';
    protected $fillable = [
        'game_id','background','logo','logo2x','icon',
        'sidebar_icon','sidebar_icon2x','menu_notification_av','menu_notification_av2'
    ];
}
