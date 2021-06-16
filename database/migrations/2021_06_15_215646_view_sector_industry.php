<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewSectorIndustry extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement(
            "
            CREATE OR REPLACE VIEW sector_industry AS
                SELECT
                    q.form_id,
                    d.datapoint_id,
                    d.partnership_id,
                    d.country_id,
                    SUBSTRING_INDEX(a.text, '|', 1) as industry,
                    SUBSTRING_INDEX(a.text, '|', -1) as product
                FROM answers a
                LEFT JOIN datapoints d ON a.datapoint_id = d.id
                LEFT JOIN questions q ON q.question_id = a.question_id
                WHERE a.question_id IN (26170015,16040001,16040001,20160002)
                ORDER BY d.country_id;
            "
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("DROP VIEW IF EXISTS sector_industry;");
    }
}
