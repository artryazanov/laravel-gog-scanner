<?php

namespace Artryazanov\GogScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        // developer/publisher via pivot tables now
        'category_id',
        'original_category_id',
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
        // Moved 1:1 fields
        'is_available', 'is_available_in_account',
        'works_on_windows', 'works_on_mac', 'works_on_linux',
        'content_windows', 'content_osx', 'content_linux',
        'purchase_link', 'product_card', 'support', 'forum',
        'in_development_until',
        'lead', 'full', 'whats_cool_about_it',
        'created_at',
        'updated_at',
    ];

    // 1:1 relations
    public function price(): HasOne
    {
        return $this->hasOne(GamePrice::class);
    }

    public function salesVisibility(): HasOne
    {
        return $this->hasOne(GameSalesVisibility::class);
    }

    public function images(): HasOne
    {
        return $this->hasOne(GameImages::class);
    }

    // 1:many relations
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'gog_game_genre', 'game_id', 'genre_id');
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(GameGallery::class);
    }

    public function supportedSystems(): BelongsToMany
    {
        return $this->belongsToMany(SupportedSystem::class, 'gog_game_supported_system', 'game_id', 'supported_system_id');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'gog_game_language', 'game_id', 'language_id');
    }

    public function dlcs(): HasMany
    {
        return $this->hasMany(GameDlc::class);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(GameArtifact::class);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(GameScreenshot::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(GameVideo::class);
    }

    // Companies (many-to-many via role-specific pivots)
    public function developers(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'gog_game_developers', 'game_id', 'company_id');
    }

    public function publishers(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'gog_game_publishers', 'game_id', 'company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function originalCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'original_category_id');
    }
}
