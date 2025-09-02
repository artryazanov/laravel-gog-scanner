<?php

namespace Artryazanov\GogScanner\Tests\Jobs;

use Artryazanov\GogScanner\Jobs\ScanGameDetailJob;
use Artryazanov\GogScanner\Models\Game;
use Artryazanov\GogScanner\Models\GameArtifact;
use Artryazanov\GogScanner\Models\GameArtifactFile;
use Artryazanov\GogScanner\Models\GameImages;
use Artryazanov\GogScanner\Models\GameScreenshot;
use Artryazanov\GogScanner\Models\GameScreenshotImage;
use Artryazanov\GogScanner\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScanGameDetailJobTest extends TestCase
{
    public function test_handle_http_failure_logs_error()
    {
        Log::spy();

        Http::fake([
            '*' => Http::response('Server error', 500),
        ]);

        $job = new ScanGameDetailJob(12345);
        $job->handle();

        Log::shouldHaveReceived('error')->once();
    }

    public function test_handle_with_missing_id_returns_early()
    {
        Log::spy();

        Http::fake([
            '*' => Http::response(['foo' => 'bar'], 200),
        ]);

        $job = new ScanGameDetailJob(12345);
        $job->handle();

        // No specific side-effects to assert; ensure no errors
        $this->assertTrue(true);
    }

    public function test_handle_persists_detail_relations()
    {
        // Seed base game
        $game = Game::create(['id' => 1207658691, 'title' => 'seed']);

        $response = [
            'id' => 1207658691,
            'title' => "Unreal Tournament 2004 Editor's Choice Edition",
            'purchase_link' => 'https://www.gog.com/en/checkout/manual/1207658691',
            'slug' => 'unreal_tournament_2004_ece',
            'content_system_compatibility' => ['windows' => true, 'osx' => false, 'linux' => false],
            'languages' => ['en' => 'English'],
            'links' => [
                'purchase_link' => 'https://www.gog.com/en/checkout/manual/1207658691',
                'product_card' => 'https://www.gog.com/en/game/unreal_tournament_2004_ece',
                'support' => 'https://www.gog.com/support/unreal_tournament_2004_ece',
                'forum' => 'https://www.gog.com/forum/unreal_series',
            ],
            'in_development' => ['active' => false, 'until' => null],
            'is_secret' => false,
            'is_installable' => true,
            'game_type' => 'game',
            'is_pre_order' => false,
            'release_date' => '2008-11-25T06:00:00+0200',
            'images' => [
                'background' => '//images/bg.jpg',
                'logo' => '//images/logo.jpg',
                'logo2x' => '//images/logo2x.jpg',
                'icon' => '//images/icon.png',
                'sidebarIcon' => '//images/sb.png',
                'sidebarIcon2x' => '//images/sb2x.png',
                'menuNotificationAv' => '//images/mnav.png',
                'menuNotificationAv2' => '//images/mnav2.png',
            ],
            'dlcs' => [],
            'downloads' => [
                'installers' => [[
                    'id' => 'installer_windows_en', 'name' => 'UT2004 ECE', 'os' => 'windows', 'language' => 'en', 'language_full' => 'English', 'version' => '1.0', 'total_size' => 2795503616,
                    'files' => [
                        ['id' => 'en1installer0', 'size' => 1048576, 'downlink' => 'https://api.gog.com/products/1207658691/downlink/installer/en1installer0'],
                        ['id' => 'en1installer1', 'size' => 2794455040, 'downlink' => 'https://api.gog.com/products/1207658691/downlink/installer/en1installer1'],
                    ],
                ]],
                'patches' => [],
                'language_packs' => [],
                'bonus_content' => [
                    ['id' => 6093, 'name' => 'manual (33 pages)', 'type' => 'manuals', 'count' => 1, 'total_size' => 2097152, 'files' => [['id' => 6093, 'size' => 2097152, 'downlink' => 'https://api.gog.com/products/1207658691/downlink/product_bonus/6093']]],
                    ['id' => 6073, 'name' => 'HD wallpapers', 'type' => 'wallpapers', 'count' => 12, 'total_size' => 120586240, 'files' => [['id' => 6073, 'size' => 120586240, 'downlink' => 'https://api.gog.com/products/1207658691/downlink/product_bonus/6073']]],
                    ['id' => 6083, 'name' => 'avatars', 'type' => 'avatars', 'count' => 8, 'total_size' => 1048576, 'files' => [['id' => 6083, 'size' => 1048576, 'downlink' => 'https://api.gog.com/products/1207658691/downlink/product_bonus/6083']]],
                ],
            ],
            'description' => [
                'lead' => 'lead',
                'full' => 'full',
                'whats_cool_about_it' => 'cool',
            ],
            'screenshots' => [[
                'image_id' => 'de2a...ef08',
                'formatter_template_url' => 'https://images-4.gog-statics.com/de2a..._ {formatter}.png',
                'formatted_images' => [
                    ['formatter_name' => 'ggvgt', 'image_url' => 'https://images-3.gog-statics.com/.._ggvgt.jpg'],
                    ['formatter_name' => 'ggvgt_2x', 'image_url' => 'https://images-2.gog-statics.com/.._ggvgt_2x.jpg'],
                ],
            ]],
            'videos' => [],
            'related_products' => [],
            'changelog' => null,
        ];

        Http::fake(['*' => Http::response($response, 200)]);

        (new ScanGameDetailJob($game->id))->handle();

        $g = Game::find($game->id);
        $this->assertSame('unreal_tournament_2004_ece', $g->slug);
        $this->assertTrue((bool) $g->content_windows);
        $this->assertSame(1, $g->languages()->count());
        $this->assertNotNull($g->purchase_link);
        $this->assertNotNull($g->product_card);
        $this->assertNotNull($g->support);
        $this->assertNotNull($g->forum);
        $this->assertFalse((bool) $g->is_in_development);
        $this->assertNull($g->in_development_until);
        $this->assertNotNull(GameImages::where('game_id', $game->id)->first());
        $this->assertSame(4, GameArtifact::where('game_id', $game->id)->count());
        $this->assertSame(5, GameArtifactFile::count());
        $this->assertSame('lead', $g->lead);
        $this->assertSame('full', $g->full);
        $this->assertSame(1, GameScreenshot::where('game_id', $game->id)->count());
        $this->assertSame(2, GameScreenshotImage::count());
    }

    public function test_dlcs_from_products_replaces_existing()
    {
        $game = Game::create(['id' => 1685505421, 'title' => 'seed']);

        // Pre-seed an outdated DLC to verify it gets cleared
        \Artryazanov\GogScanner\Models\GameDlc::create([
            'game_id' => $game->id,
            'dlc_product_id' => 999999999,
        ]);

        $response = [
            'id' => $game->id,
            'title' => 'Card Shark Deluxe Edition',
            'slug' => 'card_shark_deluxe_edition',
            'dlcs' => [
                'products' => [
                    ['id' => 1845782087],
                    ['id' => 1670665331],
                ],
                'all_products_url' => 'https://api.gog.com/products?ids=1845782087,1670665331',
            ],
            'downloads' => ['installers' => [], 'patches' => [], 'language_packs' => [], 'bonus_content' => []],
        ];

        Http::fake(['*' => Http::response($response, 200)]);

        (new ScanGameDetailJob($game->id))->handle();

        $dlcs = \Artryazanov\GogScanner\Models\GameDlc::where('game_id', $game->id)->pluck('dlc_product_id')->all();
        sort($dlcs);
        $this->assertSame([1670665331, 1845782087], $dlcs);
    }

    public function test_dlcs_from_expanded_dlcs_when_products_absent()
    {
        $game = Game::create(['id' => 777000111, 'title' => 'seed']);

        $response = [
            'id' => $game->id,
            'title' => 'Some Pack',
            'slug' => 'some_pack',
            // no dlcs key
            'expanded_dlcs' => [
                ['id' => 111111111],
                ['id' => 222222222],
            ],
            'downloads' => ['installers' => [], 'patches' => [], 'language_packs' => [], 'bonus_content' => []],
        ];

        Http::fake(['*' => Http::response($response, 200)]);

        (new ScanGameDetailJob($game->id))->handle();

        $dlcs = \Artryazanov\GogScanner\Models\GameDlc::where('game_id', $game->id)->pluck('dlc_product_id')->all();
        sort($dlcs);
        $this->assertSame([111111111, 222222222], $dlcs);
    }

    public function test_dlcs_legacy_flat_array_still_supported()
    {
        $game = Game::create(['id' => 1357902468, 'title' => 'seed']);

        $response = [
            'id' => $game->id,
            'title' => 'Legacy Format',
            'slug' => 'legacy_format',
            'dlcs' => [['id' => 333333333], 444444444],
            'downloads' => ['installers' => [], 'patches' => [], 'language_packs' => [], 'bonus_content' => []],
        ];

        Http::fake(['*' => Http::response($response, 200)]);

        (new ScanGameDetailJob($game->id))->handle();

        $dlcs = \Artryazanov\GogScanner\Models\GameDlc::where('game_id', $game->id)->pluck('dlc_product_id')->all();
        sort($dlcs);
        $this->assertSame([333333333, 444444444], $dlcs);
    }

    public function test_existing_dlcs_remain_when_no_dlc_info_in_response()
    {
        $game = Game::create(['id' => 999000111, 'title' => 'seed']);
        \Artryazanov\GogScanner\Models\GameDlc::create(['game_id' => $game->id, 'dlc_product_id' => 555555555]);

        $response = [
            'id' => $game->id,
            'title' => 'No DLC Info',
            'slug' => 'no_dlc_info',
            // note: no dlcs and no expanded_dlcs keys present at all
            'downloads' => ['installers' => [], 'patches' => [], 'language_packs' => [], 'bonus_content' => []],
        ];

        Http::fake(['*' => Http::response($response, 200)]);

        (new ScanGameDetailJob($game->id))->handle();

        $dlcs = \Artryazanov\GogScanner\Models\GameDlc::where('game_id', $game->id)->pluck('dlc_product_id')->all();
        $this->assertSame([555555555], $dlcs);
    }
}
