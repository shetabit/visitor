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
     * The column keeps what an application hands over beside a visit, as key and value pairs, so that it needs no
     * column of its own for each of them. It is only added when it is missing, like the other columns that were added
     * to the table after it was created.
     */
    public function up(): void
    {
        if (Schema::hasColumn($this->table(), 'extra_attributes')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->json('extra_attributes')
                ->nullable()
                ->after('geo_raw')
                ->comment('What the application kept beside the visit, as key and value pairs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn($this->table(), 'extra_attributes')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->dropColumn('extra_attributes');
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
