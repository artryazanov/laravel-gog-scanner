<?php

namespace Artryazanov\GogScanner\Tests\Jobs;

use Artryazanov\GogScanner\Jobs\ScanGameDetailJob;
use Artryazanov\GogScanner\Jobs\ScanPageJob;
use Artryazanov\GogScanner\Models\{
    Game, GamePrice, GameSalesVisibility,
    GameGallery, GameVideo
};
use Artryazanov\GogScanner\Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class ScanPageJobTest extends TestCase
{
    public function test_handle_http_failure_releases_and_logs_error()
    {
        Queue::fake();
        Log::spy();

        Http::fake([
            '*' => Http::response('Server error', 500),
        ]);

        $job = new ScanPageJob(1);
        $job->handle();

        Queue::assertNothingPushed();
        Log::shouldHaveReceived('error')->once();
    }

    public function test_handle_with_empty_products_logs_warning_and_does_not_dispatch()
    {
        Queue::fake();
        Log::spy();

        Http::fake([
            '*' => Http::response(['page' => 1, 'totalPages' => 1], 200),
        ]);

        $job = new ScanPageJob(1);
        $job->handle();

        Queue::assertNothingPushed();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_handle_persists_models_and_dispatches_detail_jobs()
    {
        Bus::fake();

        $payload = [
            'products' => [
                [
                    'developer' => 'Mohawk Games',
                    'publisher' => 'Stardock Entertainment',
                    'gallery' => [
                        "//images-1.gog-statics.com/a.png",
                        "//images-1.gog-statics.com/b.png",
                    ],
                    'video' => null,
                    'supportedOperatingSystems' => ['windows','mac'],
                    'genres' => ['Simulation','Strategy','Sci-fi'],
                    'globalReleaseDate' => 1551304800,
                    'isTBA' => false,
                    'price' => [
                        'currency' => 'USD','amount' => '1.59','baseAmount' => '3.99','finalAmount' => '1.59',
                        'isDiscounted' => true,'discountPercentage' => 60,'discountDifference' => '2.40','symbol' => '$',
                        'isFree' => false,'discount' => 60,'isBonusStoreCreditIncluded' => false,'bonusStoreCreditAmount' => '0.00','promoId' => '2025_back_to_school_3'
                    ],
                    'isDiscounted' => true,
                    'isInDevelopment' => false,
                    'id' => 1841631965,
                    'releaseDate' => 1551304800,
                    'availability' => ['isAvailable' => true,'isAvailableInAccount' => true],
                    'salesVisibility' => [
                        'isActive' => true,
                        'fromObject' => ['date' => '2024-07-12 15:55:00.000000','timezone_type' => 3,'timezone' => 'Europe/Nicosia'],
                        'from' => 1720788900,
                        'toObject' => ['date' => '2037-12-31 23:59:59.000000','timezone_type' => 3,'timezone' => 'Europe/Nicosia'],
                        'to' => 2145909599,
                    ],
                    'buyable' => true,
                    'title' => ' Offworld Trading Company - Market Corrections',
                    'image' => '//images-3.gog-statics.com/x.png',
                    'url' => '/en/game/offworld_trading_company_market_corrections',
                    'supportUrl' => '/support/offworld_trading_company_market_corrections',
                    'forumUrl' => '/forum/offworld_trading_company',
                    'worksOn' => ['Windows' => true,'Mac' => true,'Linux' => false],
                    'category' => 'Simulation',
                    'originalCategory' => 'Simulation',
                    'rating' => 0,
                    'type' => 3,
                    'isComingSoon' => false,
                    'isPriceVisible' => true,
                    'isMovie' => false,
                    'isGame' => true,
                    'slug' => 'offworld_trading_company_market_corrections',
                    'isWishlistable' => true,
                    'ageLimit' => 0,
                    'boxImage' => '//images-3.gog-statics.com/z.png',
                    'isMod' => false,
                ],
                [
                    'developer' => 'Pixel Perfect Dude',
                    'publisher' => 'Pixel Perfect Dude',
                    'gallery' => ["//images-1.gog-statics.com/ga.png"],
                    'video' => ['id' => 'Ru2e4c9B4bk','provider' => 'youtube'],
                    'supportedOperatingSystems' => ['windows','mac'],
                    'genres' => ['Racing','Arcade','Rally'],
                    'globalReleaseDate' => 1744750800,
                    'isTBA' => false,
                    'price' => [
                        'currency' => 'USD','amount' => '13.99','baseAmount' => '19.99','finalAmount' => '13.99',
                        'isDiscounted' => true,'discountPercentage' => 30,'discountDifference' => '6.00','symbol' => '$',
                        'isFree' => false,'discount' => 30,'isBonusStoreCreditIncluded' => false,'bonusStoreCreditAmount' => '0.00','promoId' => '2025_back_to_school_2'
                    ],
                    'isDiscounted' => true,
                    'isInDevelopment' => false,
                    'id' => 1511212118,
                    'releaseDate' => 1744750800,
                    'availability' => ['isAvailable' => true,'isAvailableInAccount' => true],
                    'salesVisibility' => [
                        'isActive' => true,
                        'fromObject' => ['date' => '2024-09-25 17:55:00.000000','timezone_type' => 3,'timezone' => 'Europe/Nicosia'],
                        'from' => 1727276100,
                        'toObject' => ['date' => '2037-12-31 23:59:59.000000','timezone_type' => 3,'timezone' => 'Europe/Nicosia'],
                        'to' => 2145909599,
                    ],
                    'buyable' => true,
                    'title' => '#DRIVE Rally',
                    'image' => '//images-2.gog-statics.com/7f923480.png',
                    'url' => '/en/game/drive_rally',
                    'supportUrl' => '/support/drive_rally',
                    'forumUrl' => '/forum/drive_rally',
                    'worksOn' => ['Windows' => true,'Mac' => true,'Linux' => false],
                    'category' => 'Racing',
                    'originalCategory' => 'Racing',
                    'rating' => 0,
                    'type' => 1,
                    'isComingSoon' => false,
                    'isPriceVisible' => true,
                    'isMovie' => false,
                    'isGame' => true,
                    'slug' => 'drive_rally',
                    'isWishlistable' => true,
                    'ageLimit' => 0,
                    'boxImage' => '//images-3.gog-statics.com/box.png',
                    'isMod' => false,
                ],
            ],
            'page' => 1,
            'totalPages' => 351,
        ];

        Http::fake(['*' => Http::response($payload, 200)]);

        (new ScanPageJob(1))->handle();

        $this->assertSame(2, Game::count());
        $g1 = Game::find(1841631965);
        $this->assertNotNull($g1);
        $this->assertNotNull(GamePrice::where('game_id', $g1->id)->first());
        $this->assertTrue((bool) $g1->is_available);
        $this->assertTrue((bool) $g1->is_available_in_account);
        $this->assertNotNull(GameSalesVisibility::where('game_id', $g1->id)->first());
        $this->assertTrue((bool) $g1->works_on_windows);
        $this->assertTrue((bool) $g1->works_on_mac);
        $this->assertFalse((bool) $g1->works_on_linux);
        $this->assertSame(2, $g1->supportedSystems()->count());
        $this->assertSame(3, $g1->genres()->count());
        $this->assertSame(2, GameGallery::where('game_id', $g1->id)->count());
        // Companies
        $this->assertSame(1, $g1->developers()->count());
        $this->assertSame(1, $g1->publishers()->count());

        $g2 = Game::find(1511212118);
        $this->assertNotNull($g2);
        $this->assertSame(1, GameVideo::where('game_id', $g2->id)->count());
        $this->assertSame(1, $g2->developers()->count());
        $this->assertSame(1, $g2->publishers()->count());

        Bus::assertDispatchedTimes(ScanGameDetailJob::class, 2);
    }
}
