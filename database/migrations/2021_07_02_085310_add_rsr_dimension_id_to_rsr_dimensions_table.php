<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRsrDimensionIdToRsrDimensionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rsr_dimensions', function (Blueprint $table) {
            $table->unsignedBigInteger('rsr_dimension_id')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rsr_dimensions', function (Blueprint $table) {
            $table->dropColumn('rsr_dimension_id');
        });
    }
}
