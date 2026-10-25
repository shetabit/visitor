<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The columns an application keeps beside a visit, which the custom visit model fills.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table((string) config('visitor.table_name'), function (Blueprint $table): void {
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn((string) config('visitor.table_name'), 'name')) {
            return;
        }

        Schema::table((string) config('visitor.table_name'), function (Blueprint $table): void {
            $table->dropColumn(['name', 'mobile']);
        });
    }
};
