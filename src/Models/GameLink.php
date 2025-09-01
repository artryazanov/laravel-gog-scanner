<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameLink extends Model
{
    protected $table = 'gog_game_links';
    protected $fillable = ['game_id','purchase_link','product_card','support','forum'];
}
