<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class SupportedSystem extends Model
{
    protected $table = 'gog_game_supported_systems';
    public $timestamps = false;
    protected $fillable = ['system'];
}

