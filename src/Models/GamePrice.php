<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;

class GamePrice extends Model
{
    protected $table = 'gog_game_prices';

    protected $fillable = [
        'game_id',
        'currency', 'amount', 'base_amount', 'final_amount',
        'is_discounted', 'discount_percentage', 'discount_difference', 'symbol',
        'is_free', 'discount', 'is_bonus_store_credit_included',
        'bonus_store_credit_amount', 'promo_id',
    ];
}
