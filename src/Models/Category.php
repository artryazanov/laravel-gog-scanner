<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'gog_game_categories';
    public $timestamps = false;
    protected $fillable = ['name'];
}

