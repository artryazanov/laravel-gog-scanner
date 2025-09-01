<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GameSalesVisibility extends Model
{
    protected $table = 'gog_game_sales_visibilities';
    protected $fillable = [
        'game_id','is_active','from_ts','to_ts',
        'from_date','from_timezone_type','from_timezone',
        'to_date','to_timezone_type','to_timezone'
    ];
}
