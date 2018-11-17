<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSha1ToMediaTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('sha1', 40)->nullable()->default(null)->after('mime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('sha1');
        });
    }
}
