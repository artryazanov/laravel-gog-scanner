<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $table = 'gog_game_genres';

    public $timestamps = false;

    protected $fillable = ['name'];
}
