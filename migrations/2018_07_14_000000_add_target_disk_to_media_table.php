<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetDiskToMediaTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('target_disk')->nullable()->default(null)->after('disk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('target_disk');
        });
    }
}
