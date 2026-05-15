<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique index on (role_id, model_type) to aauth's
 * role_model_abac_rules table to prevent duplicate rule rows for
 * the same role / model_type pair under concurrent writes.
 *
 * Idempotent and defensive:
 *   - Skips if the table doesn't exist yet (aauth migration not run).
 *   - Skips if the index is already present.
 *   - De-duplicates existing rows (keeping the most recent) before
 *     adding the constraint, so the migration cannot fail on legacy
 *     data.
 */
return new class extends Migration
{
    private string $table = 'role_model_abac_rules';

    private string $indexName = 'role_model_abac_rules_role_id_model_type_unique';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (Schema::hasIndex($this->table, $this->indexName)) {
            return;
        }

        $this->dedupe();

        Schema::table($this->table, function (Blueprint $table): void {
            $table->unique(['role_id', 'model_type'], $this->indexName);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (! Schema::hasIndex($this->table, $this->indexName)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table): void {
            $table->dropUnique($this->indexName);
        });
    }

    /**
     * For each (role_id, model_type) pair with more than one row,
     * keep the row with the highest id and delete the rest. This is
     * cross-DB safe and runs only when duplicates exist.
     */
    private function dedupe(): void
    {
        $duplicates = DB::table($this->table)
            ->select('role_id', 'model_type', DB::raw('MAX(id) AS keep_id'))
            ->groupBy('role_id', 'model_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table($this->table)
                ->where('role_id', $dup->role_id)
                ->where('model_type', $dup->model_type)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }
    }
};
