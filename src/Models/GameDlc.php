<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameDlc extends Model
{
    protected $table = 'gog_game_dlcs';

    public $timestamps = false;

    protected $fillable = ['game_id', 'dlc_product_id'];
}
