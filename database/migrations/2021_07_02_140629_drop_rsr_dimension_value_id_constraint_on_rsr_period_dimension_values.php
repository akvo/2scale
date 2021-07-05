<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropRsrDimensionValueIdConstraintOnRsrPeriodDimensionValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::Statement('TRUNCATE TABLE rsr_period_dimension_values;');
        Schema::table('rsr_period_dimension_values', function (Blueprint $table) {
            $table->dropForeign('rsr_period_dimension_values_rsr_dimension_value_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rsr_period_dimension_values', function (Blueprint $table) {
            $table->foreign('rsr_dimension_value_id')->references('id')
                ->on('rsr_dimension_values')->onDelete('cascade');
        });
    }
}
