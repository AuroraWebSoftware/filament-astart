<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `avatar_path` column the plugin's avatar feature relies on
 * (`config('filament-astart.avatar.enabled')`).
 *
 * Idempotent and defensive:
 *   - Skips when the `users` table does not exist yet.
 *   - Skips when the column is already present (projects that added
 *     it manually before installing the plugin).
 *
 * The column is `nullable` so existing users continue to load.
 */
return new class extends Migration
{
    private string $table = 'users';

    private string $column = 'avatar_path';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (Schema::hasColumn($this->table, $this->column)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table): void {
            // `after()` is a MySQL-only hint that Postgres / SQLite
            // silently ignore — keeps the column appended in those
            // engines. We deliberately don't use `after('name')` here
            // to avoid relying on a column the host app may have
            // renamed or removed.
            $table->string($this->column)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (! Schema::hasColumn($this->table, $this->column)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table): void {
            $table->dropColumn($this->column);
        });
    }
};
