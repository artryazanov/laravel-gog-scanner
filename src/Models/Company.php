<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'gog_game_companies';

    public $timestamps = false;

    protected $fillable = ['name'];
}
