<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Companies dictionary (developers/publishers)
        Schema::create('gog_game_companies', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
        });

        // Categories dictionary
        Schema::create('gog_game_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
        });

        // gog_games — base table
        Schema::create('gog_games', function (Blueprint $t) {
            $t->unsignedBigInteger('id')->primary();
            $t->string('title');
            $t->string('slug')->nullable();
            // Developer/Publisher now handled via pivot tables
            $t->unsignedBigInteger('category_id')->nullable();
            $t->unsignedBigInteger('original_category_id')->nullable();
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

            // Moved from 1:1 tables into parent
            // Availability
            $t->boolean('is_available')->default(false);
            $t->boolean('is_available_in_account')->default(false);
            // Works On (listing)
            $t->boolean('works_on_windows')->default(false);
            $t->boolean('works_on_mac')->default(false);
            $t->boolean('works_on_linux')->default(false);
            // Content system compatibility (details)
            $t->boolean('content_windows')->default(false);
            $t->boolean('content_osx')->default(false);
            $t->boolean('content_linux')->default(false);
            // Links (details)
            $t->string('purchase_link')->nullable();
            $t->string('product_card')->nullable();
            $t->string('support')->nullable();
            $t->string('forum')->nullable();
            // In development (details)
            $t->string('in_development_until')->nullable();
            // Description (details)
            $t->longText('lead')->nullable();
            $t->longText('full')->nullable();
            $t->longText('whats_cool_about_it')->nullable();

            $t->timestamps();
            // FKs to categories
            $t->foreign('category_id')->references('id')->on('gog_game_categories')->nullOnDelete();
            $t->foreign('original_category_id')->references('id')->on('gog_game_categories')->nullOnDelete();

        });

        // Pivots for developers and publishers (many-to-many, allow multiple companies per role)
        Schema::create('gog_game_developers', function (Blueprint $t) {
            $t->unsignedBigInteger('game_id');
            $t->unsignedBigInteger('company_id');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->foreign('company_id')->references('id')->on('gog_game_companies')->cascadeOnDelete();
            $t->unique(['game_id','company_id']);
        });

        Schema::create('gog_game_publishers', function (Blueprint $t) {
            $t->unsignedBigInteger('game_id');
            $t->unsignedBigInteger('company_id');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->foreign('company_id')->references('id')->on('gog_game_companies')->cascadeOnDelete();
            $t->unique(['game_id','company_id']);
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

        // Removed 1:1 tables: gog_game_availabilities, gog_game_works_on,
        // gog_game_content_compatibilities, gog_game_links, gog_game_in_developments,
        // gog_game_descriptions — their fields are now on gog_games

        // Dictionary: genres
        Schema::create('gog_game_genres', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
        });

        // Pivot: game <-> genre
        Schema::create('gog_game_genre', function (Blueprint $t) {
            $t->unsignedBigInteger('game_id');
            $t->unsignedBigInteger('genre_id');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->foreign('genre_id')->references('id')->on('gog_game_genres')->cascadeOnDelete();
            $t->unique(['game_id','genre_id']);
        });

        Schema::create('gog_game_galleries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('game_id');
            $t->string('image_url');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
        });

        // Dictionary: supported systems
        Schema::create('gog_game_supported_systems', function (Blueprint $t) {
            $t->id();
            $t->string('system', 32)->unique();
        });

        // Pivot: game <-> supported system
        Schema::create('gog_game_supported_system', function (Blueprint $t) {
            $t->unsignedBigInteger('game_id');
            $t->unsignedBigInteger('supported_system_id');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->foreign('supported_system_id')->references('id')->on('gog_game_supported_systems')->cascadeOnDelete();
            $t->unique(['game_id','supported_system_id']);
        });

        // Dictionary: languages
        Schema::create('gog_game_languages', function (Blueprint $t) {
            $t->id();
            $t->string('code', 16)->unique();
            $t->string('name');
        });

        // Pivot: game <-> language
        Schema::create('gog_game_language', function (Blueprint $t) {
            $t->unsignedBigInteger('game_id');
            $t->unsignedBigInteger('language_id');
            $t->foreign('game_id')->references('id')->on('gog_games')->cascadeOnDelete();
            $t->foreign('language_id')->references('id')->on('gog_game_languages')->cascadeOnDelete();
            $t->unique(['game_id','language_id']);
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
        Schema::dropIfExists('gog_game_language');
        Schema::dropIfExists('gog_game_languages');
        Schema::dropIfExists('gog_game_supported_system');
        Schema::dropIfExists('gog_game_supported_systems');
        Schema::dropIfExists('gog_game_galleries');
        Schema::dropIfExists('gog_game_genre');
        Schema::dropIfExists('gog_game_genres');
        Schema::dropIfExists('gog_game_images');
        Schema::dropIfExists('gog_game_sales_visibilities');
        Schema::dropIfExists('gog_game_availabilities');
        Schema::dropIfExists('gog_game_prices');
        Schema::dropIfExists('gog_game_publishers');
        Schema::dropIfExists('gog_game_developers');
        Schema::dropIfExists('gog_games');
        Schema::dropIfExists('gog_game_categories');
        Schema::dropIfExists('gog_game_companies');
    }
};
