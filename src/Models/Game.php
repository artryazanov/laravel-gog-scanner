<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    protected $table = 'gog_games';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'title',
        'slug',
        'developer',
        'publisher',
        'category',
        'original_category',
        'rating',
        'type',
        'is_game',
        'is_movie',
        'is_tba',
        'is_in_development',
        'is_discounted',
        'is_price_visible',
        'is_coming_soon',
        'is_wishlistable',
        'is_mod',
        'age_limit',
        'release_date_ts',
        'global_release_date_ts',
        'buyable',
        'url',
        'support_url',
        'forum_url',
        'image',
        'box_image',
        'changelog',
        'game_type',
        'is_pre_order',
        'is_secret',
        'is_installable',
        'release_date_iso',
        'created_at',
        'updated_at'
    ];

    // 1:1 relations
    public function price(): HasOne { return $this->hasOne(GamePrice::class); }
    public function availability(): HasOne { return $this->hasOne(GameAvailability::class); }
    public function salesVisibility(): HasOne { return $this->hasOne(GameSalesVisibility::class); }
    public function worksOn(): HasOne { return $this->hasOne(GameWorksOn::class); }
    public function contentCompatibility(): HasOne { return $this->hasOne(GameContentCompatibility::class); }
    public function links(): HasOne { return $this->hasOne(GameLink::class); }
    public function inDevelopment(): HasOne { return $this->hasOne(GameInDevelopment::class); }
    public function images(): HasOne { return $this->hasOne(GameImages::class); }
    public function description(): HasOne { return $this->hasOne(GameDescription::class); }

    // 1:many relations
    public function genres(): HasMany { return $this->hasMany(GameGenre::class); }
    public function gallery(): HasMany { return $this->hasMany(GameGallery::class); }
    public function supportedSystems(): HasMany { return $this->hasMany(GameSupportedSystem::class); }
    public function languages(): HasMany { return $this->hasMany(GameLanguage::class); }
    public function dlcs(): HasMany { return $this->hasMany(GameDlc::class); }
    public function artifacts(): HasMany { return $this->hasMany(GameArtifact::class); }
    public function screenshots(): HasMany { return $this->hasMany(GameScreenshot::class); }
    public function videos(): HasMany { return $this->hasMany(GameVideo::class); }
}
