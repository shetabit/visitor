<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeoRawToVisitsTable extends Migration
{
    public function up()
    {
        Schema::table(config('visitor.table_name'), function (Blueprint $table) {
            $table->json('geo_raw')->nullable()->after('ip');
        });
    }

    public function down()
    {
        Schema::table(config('visitor.table_name'), function (Blueprint $table) {
            $table->dropColumn('geo_raw');
        });
    }
}
