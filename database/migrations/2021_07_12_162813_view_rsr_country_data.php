<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRsrCountryData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        \DB::statement("
            CREATE OR REPLACE VIEW rsr_country_data AS
            SELECT
                data.country,
                data.result_title,
                data.dimension_title,
                data.indicator_target target_value,
                data.actual_value,
                data.dimension_value_title,
                data.target_value dimension_target_value,
                data.dimension_actual_value FROM (
                SELECT ct.country, ct.result_title, ct.dimension_title, ct.indicator_target, ct.dimension_value_title, ct.target_value,
                    COALESCE(av.actual_value, 0) actual_value,
                    COALESCE(av.dimension_actual_value, 0) dimension_actual_value
                    FROM rsr_country_targets ct
                LEFT JOIN rsr_country_actual_values av
                    ON av.country = ct.country
                    AND av.result_title = ct.result_title
                    AND av.dimension_title = ct.dimension_title
                    AND av.dimension_value_title = ct.dimension_value_title
                WHERE ct.dimension_title IS NOT NULL
                UNION ALL
                SELECT ct.country, ct.result_title, ct.dimension_title, ct.indicator_target, ct.dimension_value_title, ct.target_value,
                    COALESCE(av.actual_value, 0) actual_value,
                    av.dimension_actual_value
                FROM rsr_country_targets ct
                LEFT JOIN (
                    SELECT * FROM rsr_country_actual_values
                    WHERE dimension_title IS NULL) av
                    ON av.country = ct.country
                    AND av.result_title = ct.result_title
                WHERE ct.dimension_title IS NULL
                ) data
                ORDER by data.country, data.result_title, data.dimension_title;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("DROP VIEW IF EXISTS `rsr_country_data`;");
    }
}
