<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRsrDimensionValueIdAndRsrDimensionValueTargetIdToRsrDimensionValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rsr_dimension_values', function (Blueprint $table) {
            $table->unsignedBigInteger('rsr_dimension_value_id')->after('rsr_dimension_id');
            $table->unsignedBigInteger('rsr_dimension_value_target_id')->nullable()->after('rsr_dimension_value_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rsr_dimension_values', function (Blueprint $table) {
            $table->dropColumn('rsr_dimension_value_id');
            $table->dropColumn('rsr_dimension_value_target_id');
        });
    }
}
