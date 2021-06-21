<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRsrMaxAggValues extends Migration
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
            CREATE OR REPLACE VIEW rsr_max_agg_values AS
                SELECT
                    r.rsr_project_id as project_id,
                    p.title as project_title,
                    p.partnership_id,
                    i.rsr_result_id as result_id,
                    rti.title as result_title,
                    d.rsr_indicator_id as indicator_id,
                    rtd.title as indicator_title,
                    dv.rsr_dimension_id as dimension_id,
                    rtdv.title as dimension_title,
                    pdv.rsr_dimension_value_id as dimension_value_id,
                    rtpdv.title as dimension_value_title,
                    max(pdv.value) as max_dimension_value,
                    max(prd.actual_value) as max_actual_value
                FROM rsr_indicators i
                LEFT JOIN rsr_periods prd ON prd.rsr_indicator_id = i.id
                LEFT JOIN rsr_dimensions d ON d.rsr_indicator_id = i.id
                LEFT JOIN rsr_dimension_values dv ON dv.rsr_dimension_id = d.id
                LEFT JOIN rsr_period_dimension_values pdv ON pdv.rsr_dimension_value_id = dv.id
                LEFT JOIN rsr_results r ON r.id = i.rsr_result_id
                LEFT JOIN rsr_projects p ON r.rsr_project_id = p.id
                LEFT JOIN rsr_titleables tpdv ON pdv.rsr_dimension_value_id = tpdv.rsr_titleable_id
                LEFT JOIN rsr_titles rtpdv ON tpdv.rsr_title_id = rtpdv.id
                LEFT JOIN rsr_titleables tdv ON dv.rsr_dimension_id = tdv.rsr_titleable_id
                LEFT JOIN rsr_titles rtdv ON tdv.rsr_title_id = rtdv.id
                LEFT JOIN rsr_titleables td ON d.rsr_indicator_id = td.rsr_titleable_id
                LEFT JOIN rsr_titles rtd ON td.rsr_title_id = rtd.id
                LEFT JOIN rsr_titleables ti ON i.rsr_result_id = ti.rsr_titleable_id
                LEFT JOIN rsr_titles rti ON ti.rsr_title_id = rti.id
                group by
                p.title,
                p.partnership_id,
                r.rsr_project_id,
                i.rsr_result_id,
                d.rsr_indicator_id,
                dv.rsr_dimension_id,
                pdv.rsr_dimension_value_id,
                rti.title,
                rtd.title,
                rtdv.title,
                rtpdv.title
                ORDER BY p.partnership_id;
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
        \DB::statement("DROP VIEW IF EXISTS rsr_max_agg_values;");
    }
}
