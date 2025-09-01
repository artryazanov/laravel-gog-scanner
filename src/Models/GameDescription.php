<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameDescription extends Model
{
    protected $table = 'gog_game_descriptions';
    protected $fillable = ['game_id','lead','full','whats_cool_about_it'];
}
