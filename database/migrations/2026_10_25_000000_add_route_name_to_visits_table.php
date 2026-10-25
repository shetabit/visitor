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
     * The column is indexed, since it is there to filter the visits by the route they went to. It is only added when
     * it is missing, like the other columns that were added to the table after it was created.
     */
    public function up(): void
    {
        if (Schema::hasColumn($this->table(), 'route_name')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->string('route_name')
                ->nullable()
                ->after('url')
                ->index()
                ->comment('Name of the route the visit matched');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn($this->table(), 'route_name')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->dropIndex(['route_name']);
            $table->dropColumn('route_name');
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
