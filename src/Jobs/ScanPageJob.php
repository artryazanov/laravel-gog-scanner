<?php

namespace Artryazanov\GogScanner\Jobs;

use Artryazanov\GogScanner\Models\Category;
use Artryazanov\GogScanner\Models\Company;
use Artryazanov\GogScanner\Models\Game;
use Artryazanov\GogScanner\Models\GameGallery;
use Artryazanov\GogScanner\Models\GamePrice;
use Artryazanov\GogScanner\Models\GameSalesVisibility;
use Artryazanov\GogScanner\Models\GameVideo;
use Artryazanov\GogScanner\Models\Genre;
use Artryazanov\GogScanner\Models\SupportedSystem;

class ScanPageJob extends BaseScanJob
{
    protected int $page;

    public function __construct(int $page)
    {
        $this->page = $page;
    }

    protected function doJob(): void
    {
        $url = rtrim(config('gogscanner.embed_base'), '/').config('gogscanner.list_endpoint');

        $params = array_merge(
            config('gogscanner.default_listing_params', []),
            ['page' => $this->page]
        );

        $payload = $this->fetchJson($url, $params, 'GOG listing request failed', ['page' => $this->page]);
        if ($payload === null) {
            return;
        }
        if (! $payload || ! isset($payload['products'])) {
            \Log::warning('GOG listing empty', ['page' => $this->page]);

            return;
        }

        $products = $payload['products'];
        $currentPage = (int) ($payload['page'] ?? $this->page);
        $totalPages = (int) ($payload['totalPages'] ?? $currentPage);

        foreach ($products as $p) {
            $gameId = (int) ($p['id'] ?? 0);
            if (! $gameId) {
                continue;
            }

            // Base game record
            $categoryId = null;
            if (! empty($p['category'])) {
                $categoryId = Category::firstOrCreate(['name' => (string) $p['category']])->id;
            }
            $originalCategoryId = null;
            if (! empty($p['originalCategory'])) {
                $originalCategoryId = Category::firstOrCreate(['name' => (string) $p['originalCategory']])->id;
            }
            $game = Game::updateOrCreate(
                ['id' => $gameId],
                [
                    'title' => $p['title'] ?? '',
                    'slug' => $p['slug'] ?? null,
                    'category_id' => $categoryId,
                    'original_category_id' => $originalCategoryId,
                    'rating' => isset($p['rating']) ? (int) $p['rating'] : null,
                    'type' => isset($p['type']) ? (int) $p['type'] : null,
                    'is_game' => (bool) ($p['isGame'] ?? false),
                    'is_movie' => (bool) ($p['isMovie'] ?? false),
                    'is_tba' => (bool) ($p['isTBA'] ?? false),
                    'is_in_development' => (bool) ($p['isInDevelopment'] ?? false),
                    'is_discounted' => (bool) ($p['isDiscounted'] ?? false),
                    'is_price_visible' => (bool) ($p['isPriceVisible'] ?? false),
                    'is_coming_soon' => (bool) ($p['isComingSoon'] ?? false),
                    'is_wishlistable' => (bool) ($p['isWishlistable'] ?? false),
                    'is_mod' => (bool) ($p['isMod'] ?? false),
                    'age_limit' => isset($p['ageLimit']) ? (int) $p['ageLimit'] : null,
                    'release_date_ts' => isset($p['releaseDate']) ? (int) $p['releaseDate'] : null,
                    'global_release_date_ts' => isset($p['globalReleaseDate']) ? (int) $p['globalReleaseDate'] : null,
                    'buyable' => (bool) ($p['buyable'] ?? false),
                    'url' => $p['url'] ?? null,
                    'support_url' => $p['supportUrl'] ?? null,
                    'forum_url' => $p['forumUrl'] ?? null,
                    'image' => $p['image'] ?? null,
                    'box_image' => $p['boxImage'] ?? null,
                ]
            );

            // Developers (comma-separated string in listing)
            if (array_key_exists('developer', $p)) {
                $devIds = [];
                foreach (preg_split('/,\s*/', (string) ($p['developer'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $name) {
                    $devIds[] = Company::firstOrCreate(['name' => $name])->id;
                }
                if ($devIds) {
                    $game->developers()->sync($devIds);
                }
            }

            // Publishers (comma-separated string in listing)
            if (array_key_exists('publisher', $p)) {
                $pubIds = [];
                foreach (preg_split('/,\s*/', (string) ($p['publisher'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $name) {
                    $pubIds[] = Company::firstOrCreate(['name' => $name])->id;
                }
                if ($pubIds) {
                    $game->publishers()->sync($pubIds);
                }
            }

            // Price (1:1)
            if (isset($p['price']) && is_array($p['price'])) {
                GamePrice::updateOrCreate(
                    ['game_id' => $game->id],
                    [
                        'currency' => $p['price']['currency'] ?? null,
                        'amount' => $p['price']['amount'] ?? null,
                        'base_amount' => $p['price']['baseAmount'] ?? null,
                        'final_amount' => $p['price']['finalAmount'] ?? null,
                        'is_discounted' => (bool) ($p['price']['isDiscounted'] ?? false),
                        'discount_percentage' => isset($p['price']['discountPercentage']) ? (int) $p['price']['discountPercentage'] : null,
                        'discount_difference' => $p['price']['discountDifference'] ?? null,
                        'symbol' => $p['price']['symbol'] ?? null,
                        'is_free' => (bool) ($p['price']['isFree'] ?? false),
                        'discount' => isset($p['price']['discount']) ? (int) $p['price']['discount'] : null,
                        'is_bonus_store_credit_included' => (bool) ($p['price']['isBonusStoreCreditIncluded'] ?? false),
                        'bonus_store_credit_amount' => $p['price']['bonusStoreCreditAmount'] ?? null,
                        'promo_id' => $p['price']['promoId'] ?? null,
                    ]
                );
            }

            // Availability (moved to gog_games)
            if (isset($p['availability']) && is_array($p['availability'])) {
                $game->update([
                    'is_available' => (bool) ($p['availability']['isAvailable'] ?? false),
                    'is_available_in_account' => (bool) ($p['availability']['isAvailableInAccount'] ?? false),
                ]);
            }

            // Sales visibility (1:1)
            if (isset($p['salesVisibility']) && is_array($p['salesVisibility'])) {
                $sv = $p['salesVisibility'];
                GameSalesVisibility::updateOrCreate(
                    ['game_id' => $game->id],
                    [
                        'is_active' => (bool) ($sv['isActive'] ?? false),
                        'from_ts' => isset($sv['from']) ? (int) $sv['from'] : null,
                        'to_ts' => isset($sv['to']) ? (int) $sv['to'] : null,
                        'from_date' => $sv['fromObject']['date'] ?? null,
                        'from_timezone_type' => isset($sv['fromObject']['timezone_type']) ? (int) $sv['fromObject']['timezone_type'] : null,
                        'from_timezone' => $sv['fromObject']['timezone'] ?? null,
                        'to_date' => $sv['toObject']['date'] ?? null,
                        'to_timezone_type' => isset($sv['toObject']['timezone_type']) ? (int) $sv['toObject']['timezone_type'] : null,
                        'to_timezone' => $sv['toObject']['timezone'] ?? null,
                    ]
                );
            }

            // Supported OS flags on parent + separate rows for supportedOperatingSystems
            if (isset($p['worksOn']) && is_array($p['worksOn'])) {
                $game->update([
                    'works_on_windows' => (bool) ($p['worksOn']['Windows'] ?? false),
                    'works_on_mac' => (bool) ($p['worksOn']['Mac'] ?? false),
                    'works_on_linux' => (bool) ($p['worksOn']['Linux'] ?? false),
                ]);
            }

            if (isset($p['supportedOperatingSystems']) && is_array($p['supportedOperatingSystems'])) {
                $ids = [];
                foreach ($p['supportedOperatingSystems'] as $sys) {
                    $system = SupportedSystem::firstOrCreate(['system' => (string) $sys]);
                    $ids[] = $system->id;
                }
                $game->supportedSystems()->sync($ids);
            }

            // Genres dictionary + pivot sync
            if (isset($p['genres']) && is_array($p['genres'])) {
                $genreIds = [];
                foreach ($p['genres'] as $genreName) {
                    $name = (string) $genreName;
                    $genre = Genre::firstOrCreate(['name' => $name]);
                    $genreIds[] = $genre->id;
                }
                $game->genres()->sync($genreIds);
            }

            // Gallery
            if (isset($p['gallery']) && is_array($p['gallery'])) {
                GameGallery::where('game_id', $game->id)->delete();
                foreach ($p['gallery'] as $imgUrl) {
                    GameGallery::create(['game_id' => $game->id, 'image_url' => (string) $imgUrl]);
                }
            }

            // Listing video (if present)
            if (isset($p['video']) && is_array($p['video'])) {
                GameVideo::updateOrCreate(
                    [
                        'game_id' => $game->id,
                        'provider' => $p['video']['provider'] ?? null,
                        'video_key' => $p['video']['id'] ?? null,
                        'source' => 'listing',
                    ],
                    ['title' => null]
                );
            }

            // Schedule detail job per game
            $this->queueDispatch(ScanGameDetailJob::dispatch($game->id));
        }

        // Dispatch the next page if available
        if ($currentPage < $totalPages) {
            $this->queueDispatch(ScanPageJob::dispatch($currentPage + 1));
        }
    }
}
