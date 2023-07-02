<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('hashtag_override');
            $table->string('hashtag_type')->nullable()->after('hashtag_set_id');
            $table->string('raw_hashtags')->nullable()->after('hashtag_type');
            $table->string('hashtag_position')->nullable()->after('raw_hashtags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('hashtag_override')->nullable()->after('hashtag_set_id');
            $table->dropColumn('hashtag_type');
            $table->dropColumn('hashtag_position');
            $table->dropColumn('raw_hashtags');
        });
    }
};
