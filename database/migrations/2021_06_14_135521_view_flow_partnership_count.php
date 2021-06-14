<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewFlowPartnershipCount extends Migration
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
                CREATE OR REPLACE VIEW flow_partnership_count AS
                    SELECT
                        d.datapoint_id,
                        pc.name as `country`,
                        ps.name as `partnership`,
                        COUNT(d.partnership_id) as `total`
                    FROM answers a
                    LEFT JOIN datapoints d ON a.datapoint_id = d.id
                    LEFT JOIN partnerships pc ON d.country_id = pc.id
                    LEFT JOIN partnerships ps ON d.partnership_id = ps.id
                    GROUP BY d.datapoint_id, d.country_id, d.partnership_id;
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
        \DB::statement("DROP VIEW IF EXISTS `flow_partnership_count`;");
    }
}
