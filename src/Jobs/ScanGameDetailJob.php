<?php

namespace Artryazanov\GogScanner\Jobs;

use Artryazanov\GogScanner\Models\Game;
use Artryazanov\GogScanner\Models\GameArtifact;
use Artryazanov\GogScanner\Models\GameArtifactFile;
use Artryazanov\GogScanner\Models\GameDlc;
use Artryazanov\GogScanner\Models\GameImages;
use Artryazanov\GogScanner\Models\GameScreenshot;
use Artryazanov\GogScanner\Models\GameScreenshotImage;
use Artryazanov\GogScanner\Models\GameVideo;
use Artryazanov\GogScanner\Models\Language;

class ScanGameDetailJob extends BaseScanJob
{
    protected int $gameId;

    public function __construct(int $gameId)
    {
        $this->gameId = $gameId;
    }

    protected function doJob(): void
    {
        $endpoint = str_replace('{id}', (string) $this->gameId, config('gogscanner.detail_endpoint'));
        $url = rtrim(config('gogscanner.api_base'), '/').$endpoint;

        $params = [];
        $expand = config('gogscanner.expand_fields');
        if ($expand) {
            $params['expand'] = $expand;
        }

        $d = $this->fetchJson($url, $params, 'GOG detail request failed', ['game_id' => $this->gameId]);
        if ($d === null) {
            return;
        }
        if (! $d || ! isset($d['id'])) {
            return;
        }

        $game = Game::find($this->gameId);
        if (! $game) {
            return;
        }

        // Update simple game fields if present in detail response
        $game->update([
            'title' => $d['title'] ?? $game->title,
            'slug' => $d['slug'] ?? $game->slug,
            'changelog' => $d['changelog'] ?? $game->changelog,
            'game_type' => $d['game_type'] ?? $game->game_type,
            'is_pre_order' => (bool) ($d['is_pre_order'] ?? $game->is_pre_order),
            'is_secret' => (bool) ($d['is_secret'] ?? $game->is_secret),
            'is_installable' => (bool) ($d['is_installable'] ?? $game->is_installable),
            'release_date_iso' => $d['release_date'] ?? $game->release_date_iso,
        ]);

        // content_system_compatibility (moved to gog_games)
        if (isset($d['content_system_compatibility'])) {
            $game->update([
                'content_windows' => (bool) ($d['content_system_compatibility']['windows'] ?? false),
                'content_osx' => (bool) ($d['content_system_compatibility']['osx'] ?? false),
                'content_linux' => (bool) ($d['content_system_compatibility']['linux'] ?? false),
            ]);
        }

        // languages (map code => name) via dictionary + pivot
        if (isset($d['languages']) && is_array($d['languages'])) {
            $ids = [];
            foreach ($d['languages'] as $code => $name) {
                $lang = Language::firstOrCreate(['code' => (string) $code], ['name' => (string) $name]);
                // Optional: keep name fresh if changed
                if ($lang->name !== (string) $name) {
                    $lang->name = (string) $name;
                    $lang->save();
                }
                $ids[] = $lang->id;
            }
            $game->languages()->sync($ids);
        }

        // links (moved to gog_games)
        if (isset($d['links']) && is_array($d['links'])) {
            $game->update([
                'purchase_link' => $d['links']['purchase_link'] ?? null,
                'product_card' => $d['links']['product_card'] ?? null,
                'support' => $d['links']['support'] ?? null,
                'forum' => $d['links']['forum'] ?? null,
            ]);
        }

        // in_development (moved to gog_games)
        if (isset($d['in_development']) && is_array($d['in_development'])) {
            $game->update([
                'is_in_development' => (bool) ($d['in_development']['active'] ?? false),
                'in_development_until' => $d['in_development']['until'] ?? null,
            ]);
        }

        // images (1:1)
        if (isset($d['images']) && is_array($d['images'])) {
            GameImages::updateOrCreate(
                ['game_id' => $game->id],
                [
                    'background' => $d['images']['background'] ?? null,
                    'logo' => $d['images']['logo'] ?? null,
                    'logo2x' => $d['images']['logo2x'] ?? null,
                    'icon' => $d['images']['icon'] ?? null,
                    'sidebar_icon' => $d['images']['sidebarIcon'] ?? null,
                    'sidebar_icon2x' => $d['images']['sidebarIcon2x'] ?? null,
                    'menu_notification_av' => $d['images']['menuNotificationAv'] ?? null,
                    'menu_notification_av2' => $d['images']['menuNotificationAv2'] ?? null,
                ]
            );
        }

        // DLCs — API returns object with `products` array; fallback to `expanded_dlcs`
        if ((isset($d['dlcs']) && is_array($d['dlcs'])) || (isset($d['expanded_dlcs']) && is_array($d['expanded_dlcs']))) {
            $dlcIds = [];

            // Preferred: dlcs.products
            if (isset($d['dlcs']) && is_array($d['dlcs']) && isset($d['dlcs']['products']) && is_array($d['dlcs']['products'])) {
                foreach ($d['dlcs']['products'] as $p) {
                    if (is_array($p) && isset($p['id'])) {
                        $dlcIds[] = (int) $p['id'];
                    } elseif (! is_array($p)) {
                        // rare fallback: scalar ID
                        $dlcIds[] = (int) $p;
                    }
                }
            }

            // Fallback: expanded_dlcs (array of DLC objects)
            if (! $dlcIds && isset($d['expanded_dlcs']) && is_array($d['expanded_dlcs'])) {
                foreach ($d['expanded_dlcs'] as $p) {
                    if (is_array($p) && isset($p['id'])) {
                        $dlcIds[] = (int) $p['id'];
                    }
                }
            }

            // Legacy fallback: dlcs as a flat array of ids/objects with id
            if (! $dlcIds && isset($d['dlcs']) && is_array($d['dlcs'])) {
                foreach ($d['dlcs'] as $dlc) {
                    $dlcId = is_array($dlc) ? ($dlc['id'] ?? null) : $dlc;
                    if ($dlcId) {
                        $dlcIds[] = (int) $dlcId;
                    }
                }
            }

            // Replace current DLC links with a fresh set (maybe empty)
            GameDlc::where('game_id', $game->id)->delete();
            foreach (array_unique($dlcIds) as $dlcId) {
                GameDlc::create(['game_id' => $game->id, 'dlc_product_id' => $dlcId]);
            }
        }

        // downloads: installers / patches / language_packs / bonus_content
        if (isset($d['downloads']) && is_array($d['downloads'])) {
            // remove old artifacts for this game
            $artifactIds = GameArtifact::where('game_id', $game->id)->pluck('id')->all();
            if ($artifactIds) {
                GameArtifactFile::whereIn('artifact_id', $artifactIds)->delete();
                GameArtifact::whereIn('id', $artifactIds)->delete();
            }

            $map = [
                'installers' => 'installer',
                'patches' => 'patch',
                'language_packs' => 'language_pack',
                'bonus_content' => 'bonus_content',
            ];

            foreach ($map as $key => $type) {
                if (! isset($d['downloads'][$key]) || ! is_array($d['downloads'][$key])) {
                    continue;
                }

                foreach ($d['downloads'][$key] as $item) {
                    $artifact = GameArtifact::create([
                        'game_id' => $game->id,
                        'type' => $type,
                        'artifact_id' => (string) ($item['id'] ?? ($item['name'] ?? uniqid($type.'_'))),
                        'name' => $item['name'] ?? null,
                        'os' => $item['os'] ?? null,
                        'language' => $item['language'] ?? null,
                        'language_full' => $item['language_full'] ?? null,
                        'version' => $item['version'] ?? null,
                        'count' => isset($item['count']) ? (int) $item['count'] : null,
                        'total_size' => isset($item['total_size']) ? (int) $item['total_size'] : null,
                        'extra_type' => $item['type'] ?? null, // for bonus_content: manuals/wallpapers/etc.
                    ]);

                    if (isset($item['files']) && is_array($item['files'])) {
                        foreach ($item['files'] as $f) {
                            GameArtifactFile::create([
                                'artifact_id' => $artifact->id,
                                'file_id' => (string) ($f['id'] ?? ''),
                                'size' => isset($f['size']) ? (int) $f['size'] : null,
                                'downlink' => $f['downlink'] ?? null,
                            ]);
                        }
                    }
                }
            }
        }

        // description (moved to gog_games)
        if (isset($d['description']) && is_array($d['description'])) {
            $game->update([
                'lead' => $d['description']['lead'] ?? null,
                'full' => $d['description']['full'] ?? null,
                'whats_cool_about_it' => $d['description']['whats_cool_about_it'] ?? null,
            ]);
        }

        // screenshots (array + formatted_images)
        if (isset($d['screenshots']) && is_array($d['screenshots'])) {
            $shotIds = GameScreenshot::where('game_id', $game->id)->pluck('id')->all();
            if ($shotIds) {
                GameScreenshotImage::whereIn('screenshot_id', $shotIds)->delete();
                GameScreenshot::whereIn('id', $shotIds)->delete();
            }

            foreach ($d['screenshots'] as $s) {
                $screenshot = GameScreenshot::create([
                    'game_id' => $game->id,
                    'image_id' => $s['image_id'] ?? null,
                    'formatter_template_url' => $s['formatter_template_url'] ?? null,
                ]);

                if (isset($s['formatted_images']) && is_array($s['formatted_images'])) {
                    foreach ($s['formatted_images'] as $fi) {
                        GameScreenshotImage::create([
                            'screenshot_id' => $screenshot->id,
                            'formatter_name' => $fi['formatter_name'] ?? null,
                            'image_url' => $fi['image_url'] ?? null,
                        ]);
                    }
                }
            }
        }

        // videos (array) — remove detail-source then add
        if (isset($d['videos']) && is_array($d['videos'])) {
            GameVideo::where('game_id', $game->id)->where('source', 'detail')->delete();
            foreach ($d['videos'] as $v) {
                GameVideo::create([
                    'game_id' => $game->id,
                    'provider' => $v['provider'] ?? null,
                    'video_key' => $v['id'] ?? null,
                    'title' => $v['title'] ?? null,
                    'source' => 'detail',
                ]);
            }
        }

        // related_products — store as artifacts with type 'related_product'
        if (isset($d['related_products']) && is_array($d['related_products'])) {
            GameArtifact::where('game_id', $game->id)->where('type', 'related_product')->delete();

            foreach ($d['related_products'] as $rp) {
                $rpId = is_array($rp) ? ($rp['id'] ?? null) : $rp;
                if ($rpId) {
                    GameArtifact::create([
                        'game_id' => $game->id,
                        'type' => 'related_product',
                        'artifact_id' => (string) $rpId,
                        'name' => is_array($rp) ? ($rp['title'] ?? null) : null,
                    ]);
                }
            }
        }
    }
}
