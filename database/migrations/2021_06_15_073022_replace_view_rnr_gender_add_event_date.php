<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReplaceViewRnrGenderAddEventDate extends Migration
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
                    d.country_id,
                    d.partnership_id,
                    q.question_id,
                    d.submission_date,
                    aw.event_date,
                    SUM(a.value) as `total`,
                    CASE
                        WHEN (LOCATE('female',q.text) > 0) THEN 'Female'
                        ELSE 'Male' END as gender,
                    CASE
                        WHEN (LOCATE('above',q.text) > 0) THEN 'Senior'
                        ELSE 'Junior' END as age
                FROM answers a
                LEFT JOIN questions q ON a.question_id = q.question_id
                LEFT JOIN datapoints d ON a.datapoint_id = d.id
                LEFT JOIN ((
                    SELECT
                        a.datapoint_id,
                        a.text as event_date
                    FROM answers a
                    WHERE a.question_id = 90001
                )) as aw on a.datapoint_id = aw.datapoint_id
                WHERE a.question_id IN (36030007, 24030004, 20030002, 24030005)
                GROUP BY
                    d.datapoint_id,
                    d.country_id,
                    d.partnership_id,
                    q.question_id,
                    d.submission_date,
                    aw.event_date
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
        \DB::statement(
            "
            CREATE OR REPLACE VIEW rnr_gender AS
                SELECT
                    d.datapoint_id,
                    d.country_id,
                    d.partnership_id,
                    q.question_id,
                    d.submission_date,
                    SUM(a.value) as `total`,
                    CASE
                        WHEN (LOCATE('female',q.text) > 0) THEN 'Female'
                        ELSE 'Male' END as gender,
                    CASE
                        WHEN (LOCATE('above',q.text) > 0) THEN 'Senior'
                        ELSE 'Junior' END as age
                FROM answers a
                LEFT JOIN questions q ON a.question_id = q.question_id
                LEFT JOIN datapoints d ON a.datapoint_id = d.id
                WHERE a.question_id IN (36030007, 24030004, 20030002, 24030005)
                GROUP BY
                    d.datapoint_id,
                    d.country_id,
                    d.partnership_id,
                    q.question_id,
                    d.submission_date
            "
        );
    }
}
