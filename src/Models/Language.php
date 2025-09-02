<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'gog_game_languages';

    public $timestamps = false;

    protected $fillable = ['code', 'name'];
}
