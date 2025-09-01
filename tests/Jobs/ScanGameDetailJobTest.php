<?php

namespace Artryazanov\GogScanner\Tests\Jobs;

use Artryazanov\GogScanner\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Artryazanov\GogScanner\Jobs\ScanGameDetailJob;
use Artryazanov\GogScanner\Models\{
    Game, GameContentCompatibility, GameLanguage, GameLink, GameInDevelopment,
    GameImages, GameArtifact, GameArtifactFile, GameDescription, GameScreenshot, GameScreenshotImage
};

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
}

class ScanGameDetailJobPositiveTest extends TestCase
{
    public function test_handle_persists_detail_relations()
    {
        // Seed base game
        $game = Game::create(['id' => 1207658691, 'title' => 'seed']);

        $response = [
            'id' => 1207658691,
            'title' => "Unreal Tournament 2004 Editor's Choice Edition",
            'purchase_link' => 'https://www.gog.com/en/checkout/manual/1207658691',
            'slug' => 'unreal_tournament_2004_ece',
            'content_system_compatibility' => ['windows' => true,'osx' => false,'linux' => false],
            'languages' => ['en' => 'English'],
            'links' => [
                'purchase_link' => 'https://www.gog.com/en/checkout/manual/1207658691',
                'product_card'  => 'https://www.gog.com/en/game/unreal_tournament_2004_ece',
                'support'       => 'https://www.gog.com/support/unreal_tournament_2004_ece',
                'forum'         => 'https://www.gog.com/forum/unreal_series',
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
                    'id' => 'installer_windows_en', 'name' => "UT2004 ECE", 'os' => 'windows', 'language' => 'en', 'language_full' => 'English', 'version' => '1.0', 'total_size' => 2795503616,
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

        $this->assertSame('unreal_tournament_2004_ece', Game::find($game->id)->slug);
        $this->assertTrue(GameContentCompatibility::where('game_id', $game->id)->value('windows'));
        $this->assertSame(1, GameLanguage::where('game_id', $game->id)->count());
        $this->assertNotNull(GameLink::where('game_id', $game->id)->first());
        $this->assertNotNull(GameInDevelopment::where('game_id', $game->id)->first());
        $this->assertNotNull(GameImages::where('game_id', $game->id)->first());
        $this->assertSame(4, GameArtifact::where('game_id', $game->id)->count());
        $this->assertSame(5, GameArtifactFile::count());
        $this->assertNotNull(GameDescription::where('game_id', $game->id)->first());
        $this->assertSame(1, GameScreenshot::where('game_id', $game->id)->count());
        $this->assertSame(2, GameScreenshotImage::count());
    }
}
