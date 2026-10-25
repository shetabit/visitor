<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        if (Schema::hasColumn($this->table(), 'route')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->string('route')
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
        if (!Schema::hasColumn($this->table(), 'route')) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table): void {
            $table->dropIndex(['route']);
            $table->dropColumn('route');
        });
    }

    /**
     * The name of the table of the visits.
     */
    private function table(): string
    {
        return (string) config('visitor.table_name');
    }
};
