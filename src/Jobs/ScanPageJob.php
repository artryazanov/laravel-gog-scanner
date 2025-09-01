<?php

namespace Artryazanov\GogScanner\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Artryazanov\GogScanner\Models\{
    Game, GameAvailability, GamePrice, GameSalesVisibility, GameWorksOn,
    GameGenre, GameGallery, GameSupportedSystem, GameVideo
};

class ScanPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Number of attempts for the job */
    public int $tries = 3;

    protected int $page;

    public function __construct(int $page)
    {
        $this->page = $page;
    }

    public function handle(): void
    {
        $timeout = (int) config('gogscanner.http_timeout', 30);

        $url = rtrim(config('gogscanner.embed_base'), '/') . config('gogscanner.list_endpoint');

        $params = array_merge(
            config('gogscanner.default_listing_params', []),
            ['page' => $this->page]
        );

        $resp = Http::timeout($timeout)->get($url, $params);
        if ($resp->failed()) {
            \Log::error('GOG listing request failed', ['page' => $this->page, 'status' => $resp->status()]);
            $this->release(60);
            return;
        }

        $payload = $resp->json();
        if (!$payload || !isset($payload['products'])) {
            \Log::warning('GOG listing empty', ['page' => $this->page]);
            return;
        }

        $products    = $payload['products'];
        $currentPage = (int) ($payload['page'] ?? $this->page);
        $totalPages  = (int) ($payload['totalPages'] ?? $currentPage);

        foreach ($products as $p) {
            $gameId = (int) ($p['id'] ?? 0);
            if (!$gameId) {
                continue;
            }

            // Base game record
            $game = Game::updateOrCreate(
                ['id' => $gameId],
                [
                    'title'                 => $p['title'] ?? '',
                    'slug'                  => $p['slug'] ?? null,
                    'developer'             => $p['developer'] ?? null,
                    'publisher'             => $p['publisher'] ?? null,
                    'category'              => $p['category'] ?? null,
                    'original_category'     => $p['originalCategory'] ?? null,
                    'rating'                => isset($p['rating']) ? (int) $p['rating'] : null,
                    'type'                  => isset($p['type']) ? (int) $p['type'] : null,
                    'is_game'               => (bool) ($p['isGame'] ?? false),
                    'is_movie'              => (bool) ($p['isMovie'] ?? false),
                    'is_tba'                => (bool) ($p['isTBA'] ?? false),
                    'is_in_development'     => (bool) ($p['isInDevelopment'] ?? false),
                    'is_discounted'         => (bool) ($p['isDiscounted'] ?? false),
                    'is_price_visible'      => (bool) ($p['isPriceVisible'] ?? false),
                    'is_coming_soon'        => (bool) ($p['isComingSoon'] ?? false),
                    'is_wishlistable'       => (bool) ($p['isWishlistable'] ?? false),
                    'is_mod'                => (bool) ($p['isMod'] ?? false),
                    'age_limit'             => isset($p['ageLimit']) ? (int) $p['ageLimit'] : null,
                    'release_date_ts'       => isset($p['releaseDate']) ? (int) $p['releaseDate'] : null,
                    'global_release_date_ts'=> isset($p['globalReleaseDate']) ? (int) $p['globalReleaseDate'] : null,
                    'buyable'               => (bool) ($p['buyable'] ?? false),
                    'url'                   => $p['url'] ?? null,
                    'support_url'           => $p['supportUrl'] ?? null,
                    'forum_url'             => $p['forumUrl'] ?? null,
                    'image'                 => $p['image'] ?? null,
                    'box_image'             => $p['boxImage'] ?? null,
                ]
            );

            // Price (1:1)
            if (isset($p['price']) && is_array($p['price'])) {
                GamePrice::updateOrCreate(
                    ['game_id' => $game->id],
                    [
                        'currency'                        => $p['price']['currency'] ?? null,
                        'amount'                          => $p['price']['amount'] ?? null,
                        'base_amount'                     => $p['price']['baseAmount'] ?? null,
                        'final_amount'                    => $p['price']['finalAmount'] ?? null,
                        'is_discounted'                   => (bool) ($p['price']['isDiscounted'] ?? false),
                        'discount_percentage'             => isset($p['price']['discountPercentage']) ? (int) $p['price']['discountPercentage'] : null,
                        'discount_difference'             => $p['price']['discountDifference'] ?? null,
                        'symbol'                          => $p['price']['symbol'] ?? null,
                        'is_free'                         => (bool) ($p['price']['isFree'] ?? false),
                        'discount'                        => isset($p['price']['discount']) ? (int) $p['price']['discount'] : null,
                        'is_bonus_store_credit_included'  => (bool) ($p['price']['isBonusStoreCreditIncluded'] ?? false),
                        'bonus_store_credit_amount'       => $p['price']['bonusStoreCreditAmount'] ?? null,
                        'promo_id'                        => $p['price']['promoId'] ?? null,
                    ]
                );
            }

            // Availability (1:1)
            if (isset($p['availability']) && is_array($p['availability'])) {
                GameAvailability::updateOrCreate(
                    ['game_id' => $game->id],
                    [
                        'is_available'            => (bool) ($p['availability']['isAvailable'] ?? false),
                        'is_available_in_account' => (bool) ($p['availability']['isAvailableInAccount'] ?? false),
                    ]
                );
            }

            // Sales visibility (1:1)
            if (isset($p['salesVisibility']) && is_array($p['salesVisibility'])) {
                $sv = $p['salesVisibility'];
                GameSalesVisibility::updateOrCreate(
                    ['game_id' => $game->id],
                    [
                        'is_active'           => (bool) ($sv['isActive'] ?? false),
                        'from_ts'             => isset($sv['from']) ? (int) $sv['from'] : null,
                        'to_ts'               => isset($sv['to']) ? (int) $sv['to'] : null,
                        'from_date'           => $sv['fromObject']['date'] ?? null,
                        'from_timezone_type'  => isset($sv['fromObject']['timezone_type']) ? (int) $sv['fromObject']['timezone_type'] : null,
                        'from_timezone'       => $sv['fromObject']['timezone'] ?? null,
                        'to_date'             => $sv['toObject']['date'] ?? null,
                        'to_timezone_type'    => isset($sv['toObject']['timezone_type']) ? (int) $sv['toObject']['timezone_type'] : null,
                        'to_timezone'         => $sv['toObject']['timezone'] ?? null,
                    ]
                );
            }

            // Supported OS (1:1 worksOn) + separate rows for supportedOperatingSystems
            if (isset($p['worksOn']) && is_array($p['worksOn'])) {
                GameWorksOn::updateOrCreate(
                    ['game_id' => $game->id],
                    [
                        'windows' => (bool) ($p['worksOn']['Windows'] ?? false),
                        'mac'     => (bool) ($p['worksOn']['Mac'] ?? false),
                        'linux'   => (bool) ($p['worksOn']['Linux'] ?? false),
                    ]
                );
            }

            if (isset($p['supportedOperatingSystems']) && is_array($p['supportedOperatingSystems'])) {
                GameSupportedSystem::where('game_id', $game->id)->delete();
                foreach ($p['supportedOperatingSystems'] as $sys) {
                    GameSupportedSystem::create([
                        'game_id' => $game->id,
                        'system'  => (string) $sys,
                    ]);
                }
            }

            // Genres
            if (isset($p['genres']) && is_array($p['genres'])) {
                GameGenre::where('game_id', $game->id)->delete();
                foreach ($p['genres'] as $genre) {
                    GameGenre::create(['game_id' => $game->id, 'name' => (string) $genre]);
                }
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
                        'game_id'  => $game->id,
                        'provider' => $p['video']['provider'] ?? null,
                        'video_key'=> $p['video']['id'] ?? null,
                        'source'   => 'listing'
                    ],
                    ['title' => null]
                );
            }

            // Schedule detail job per game
            ScanGameDetailJob::dispatch($game->id)
                ->onConnection(config('gogscanner.queue.connection'))
                ->onQueue(config('gogscanner.queue.queue'));
        }

        // Dispatch the next page if available
        if ($currentPage < $totalPages) {
            ScanPageJob::dispatch($currentPage + 1)
                ->onConnection(config('gogscanner.queue.connection'))
                ->onQueue(config('gogscanner.queue.queue'));
        }
    }
}
