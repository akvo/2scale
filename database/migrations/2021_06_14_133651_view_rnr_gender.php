<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRnrGender extends Migration
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
                CREATE OR REPLACE VIEW rnr_gender AS
                    SELECT
                        d.datapoint_id,
                        pc.name as `country`,
                        ps.name as `partnership`,
                        q.text,
                        SUM(a.value) as `total`
                    FROM answers a
                    LEFT JOIN questions q ON a.question_id = q.question_id
                    LEFT JOIN datapoints d ON a.datapoint_id = d.id
                    LEFT JOIN partnerships pc ON d.country_id = pc.id
                    LEFT JOIN partnerships ps ON d.partnership_id = ps.id
                    WHERE a.question_id IN (36030007, 24030004, 20030002, 24030005)
                    GROUP BY d.datapoint_id, d.country_id, d.partnership_id, q.question_id;
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
        \DB::statement("DROP VIEW IF EXISTS `rnr_gender`;");
    }
}
