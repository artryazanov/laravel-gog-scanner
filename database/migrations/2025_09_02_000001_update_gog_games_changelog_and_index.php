<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add index for game_type
        Schema::table('gog_games', function (Blueprint $table) {
            if (!self::hasIndex($table->getTable(), 'game_type')) {
                $table->index('game_type');
            }
        });

        // Change changelog to LONGTEXT where applicable
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE `gog_games` MODIFY `changelog` LONGTEXT NULL');
        }
        // On PostgreSQL, TEXT is already unlimited; on SQLite, TEXT maps accordingly.
    }

    public function down(): void
    {
        // Revert changelog type where applicable
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE `gog_games` MODIFY `changelog` TEXT NULL');
        }

        // Drop index for game_type
        try {
            Schema::table('gog_games', function (Blueprint $table) {
                // Use default index name resolution
                $table->dropIndex(['game_type']);
            });
        } catch (\Throwable $e) {
            // Ignore if index doesn't exist or driver doesn't support dropIndex by column array
        }
    }

    private static function hasIndex(string $table, string $column): bool
    {
        // Best-effort detection for MySQL/MariaDB. For other drivers, allow creating the index; duplicates will error but are unlikely here.
        try {
            $driver = DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                $dbName = DB::getDatabaseName();
                $exists = DB::selectOne(
                    'SELECT COUNT(1) as cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND column_name = ?',
                    [$dbName, $table, $column]
                );
                return ($exists?->cnt ?? 0) > 0;
            }
        } catch (\Throwable $e) {
            // If detection fails, assume no index and let migration proceed
        }
        return false;
    }
};
