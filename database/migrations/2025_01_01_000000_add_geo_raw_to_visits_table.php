<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shetabit\Visitor\Models\Visit;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The column is only added when it is missing: a table that was created by
     * the migration this package published before this one existed already has
     * it.
     */
    public function up(): void
    {
        if (Schema::hasColumn($this->table(), 'geo_raw')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->json('geo_raw')
                ->nullable()
                ->after('ip')
                ->comment('Optional GeoIP payload (country_code, region, city, lat/lng, …)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn($this->table(), 'geo_raw')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->dropColumn('geo_raw');
        });
    }

    /**
     * The database connection of the table, as the configuration names it.
     */
    public function getConnection(): string|null
    {
        return Visit::configuredConnection();
    }

    /**
     * The name of the table of the visits.
     */
    private function table(): string
    {
        return (string) config('visitor.table_name');
    }
};
