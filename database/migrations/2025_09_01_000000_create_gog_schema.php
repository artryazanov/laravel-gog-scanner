<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // gog_games — base table
        Schema::create('gog_games', function (Blueprint $t) {
            $t->unsignedBigInteger('id')->primary();
            $t->string('title');
            $t->string('slug')->nullable();
            $t->string('developer')->nullable();
            $t->string('publisher')->nullable();
            $t->string('category')->nullable();
            $t->string('original_category')->nullable();
            $t->integer('rating')->nullable();
            $t->integer('type')->nullable();
            $t->boolean('is_game')->default(false);
            $t->boolean('is_movie')->default(false);
            $t->boolean('is_tba')->default(false);
            $t->boolean('is_in_development')->default(false);
            $t->boolean('is_discounted')->default(false);
            $t->boolean('is_price_visible')->default(false);
            $t->boolean('is_coming_soon')->default(false);
            $t->boolean('is_wishlistable')->default(false);
            $t->boolean('is_mod')->default(false);
            $t->integer('age_limit')->nullable();
            $t->unsignedBigInteger('release_date_ts')->nullable();
            $t->unsignedBigInteger('global_release_date_ts')->nullable();
            $t->boolean('buyable')->default(false);
            $t->string('url')->nullable();
            $t->string('support_url')->nullable();
            $t->string('forum_url')->nullable();
            $t->string('image')->nullable();
            $t->string('box_image')->nullable();

            // Detailed extra fields
            $t->text('changelog')->nullable();
            $t->string('game_type')->nullable();
            $t->boolean('is_pre_order')->default(false);
            $t->boolean('is_secret')->default(false);
            $t->boolean('is_installable')->default(false);
            $t->string('release_date_iso')->nullable();

            $t->timestamps();
        });

        // 1:1 tables
        Schema::create('gog_game_prices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->string('currency', 16)->nullable();
            $t->string('amount', 32)->nullable();
            $t->string('base_amount', 32)->nullable();
            $t->string('final_amount', 32)->nullable();
            $t->boolean('is_discounted')->default(false);
            $t->integer('discount_percentage')->nullable();
            $t->string('discount_difference', 32)->nullable();
            $t->string('symbol', 8)->nullable();
            $t->boolean('is_free')->default(false);
            $t->integer('discount')->nullable();
            $t->boolean('is_bonus_store_credit_included')->default(false);
            $t->string('bonus_store_credit_amount', 32)->nullable();
            $t->string('promo_id', 64)->nullable();
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_availabilities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->boolean('is_available')->default(false);
            $t->boolean('is_available_in_account')->default(false);
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_sales_visibilities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->boolean('is_active')->default(false);
            $t->unsignedBigInteger('from_ts')->nullable();
            $t->unsignedBigInteger('to_ts')->nullable();
            $t->string('from_date')->nullable();
            $t->integer('from_timezone_type')->nullable();
            $t->string('from_timezone', 64)->nullable();
            $t->string('to_date')->nullable();
            $t->integer('to_timezone_type')->nullable();
            $t->string('to_timezone', 64)->nullable();
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_works_on', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->boolean('windows')->default(false);
            $t->boolean('mac')->default(false);
            $t->boolean('linux')->default(false);
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_content_compatibilities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->boolean('windows')->default(false);
            $t->boolean('osx')->default(false);
            $t->boolean('linux')->default(false);
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->string('purchase_link')->nullable();
            $t->string('product_card')->nullable();
            $t->string('support')->nullable();
            $t->string('forum')->nullable();
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_in_developments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->boolean('active')->default(false);
            $t->string('until')->nullable();
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_images', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->string('background')->nullable();
            $t->string('logo')->nullable();
            $t->string('logo2x')->nullable();
            $t->string('icon')->nullable();
            $t->string('sidebar_icon')->nullable();
            $t->string('sidebar_icon2x')->nullable();
            $t->string('menu_notification_av')->nullable();
            $t->string('menu_notification_av2')->nullable();
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_descriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id')->unique();
            $t->longText('lead')->nullable();
            $t->longText('full')->nullable();
            $t->longText('whats_cool_about_it')->nullable();
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        // 1:many tables
        Schema::create('gog_game_genres', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('name');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->unique(['game_id','name']);
        });

        Schema::create('gog_game_galleries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('image_url');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_supported_systems', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('system', 32);
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->unique(['game_id','system']);
        });

        Schema::create('gog_game_languages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('code', 16);
            $t->string('name');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->unique(['game_id','code']);
        });

        Schema::create('gog_game_dlcs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->unsignedBigInteger('dlc_product_id');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->unique(['game_id','dlc_product_id']);
        });

        // Unified artifacts (installers, patches, language_packs, bonus_content, related_product)
        Schema::create('gog_game_artifacts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('type', 32); // installer|patch|language_pack|bonus_content|related_product
            $t->string('artifact_id')->nullable();
            $t->string('name')->nullable();
            $t->string('os', 32)->nullable();
            $t->string('language', 16)->nullable();
            $t->string('language_full', 64)->nullable();
            $t->string('version', 64)->nullable();
            $t->integer('count')->nullable();
            $t->unsignedBigInteger('total_size')->nullable();
            $t->string('extra_type', 64)->nullable(); // for bonus_content (manuals, wallpapers, etc.)
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_artifact_files', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('artifact_id');
            $t->string('file_id')->nullable();
            $t->unsignedBigInteger('size')->nullable();
            $t->string('downlink')->nullable();
            $t->foreign('artifact_id')->references('id')->on('gog_game_artifacts')->cascadeOnDelete();
        });

        // Screenshots
        Schema::create('gog_game_screenshots', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('image_id')->nullable();
            $t->string('formatter_template_url')->nullable();
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        Schema::create('gog_game_screenshot_images', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('screenshot_id');
            $t->string('formatter_name')->nullable();
            $t->string('image_url')->nullable();

            $t->foreign('screenshot_id')->references('id')->on('gog_game_screenshots')->cascadeOnDelete();
        });

        // Videos
        Schema::create('gog_game_videos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('provider', 32)->nullable(); // youtube, etc.
            $t->string('video_key')->nullable();
            $t->string('title')->nullable();
            $t->string('source', 16)->default('detail'); // listing|detail
            $t->timestamps();

            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gog_game_videos');
        Schema::dropIfExists('gog_game_screenshot_images');
        Schema::dropIfExists('gog_game_screenshots');
        Schema::dropIfExists('gog_game_artifact_files');
        Schema::dropIfExists('gog_game_artifacts');
        Schema::dropIfExists('gog_game_dlcs');
        Schema::dropIfExists('gog_game_languages');
        Schema::dropIfExists('gog_game_supported_systems');
        Schema::dropIfExists('gog_game_galleries');
        Schema::dropIfExists('gog_game_genres');
        Schema::dropIfExists('gog_game_descriptions');
        Schema::dropIfExists('gog_game_images');
        Schema::dropIfExists('gog_game_in_developments');
        Schema::dropIfExists('gog_game_links');
        Schema::dropIfExists('gog_game_content_compatibilities');
        Schema::dropIfExists('gog_game_works_on');
        Schema::dropIfExists('gog_game_sales_visibilities');
        Schema::dropIfExists('gog_game_availabilities');
        Schema::dropIfExists('gog_game_prices');
        Schema::dropIfExists('gog_games');
    }
};
