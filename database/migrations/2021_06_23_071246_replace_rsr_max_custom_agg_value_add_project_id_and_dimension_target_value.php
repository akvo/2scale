<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReplaceRsrMaxCustomAggValueAddProjectIdAndDimensionTargetValue extends Migration
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
            CREATE OR REPLACE VIEW rsr_max_custom_values AS
                SELECT
                    md.project_id,
                    md.country,
                    md.partnership_code,
                    md.result_title,
                    md.indicator_id,
                    md.indicator_title,
                    md.has_dimension,
                    rsrp.id period_id,
                    rtd.title AS dimension_title,
                    dv.id AS dimension_value_id,
                    rtdv.title AS dimension_value_title,
                    md.max_period_value,
                    pdv.value AS max_actual_value,
                    dv.value AS dimension_target_value
                FROM
                    (
                    SELECT
                        rp.id as project_id,
                        p.code partnership_code,
                        c.name country,
                        rtr.title AS result_title,
                        rpr.rsr_indicator_id AS indicator_id,
                        ri.has_dimension,
                        rtpr.title AS indicator_title,
                        MAX(rpr.actual_value) AS max_period_value
                    FROM rsr_periods rpr
                    LEFT JOIN rsr_indicators ri ON rpr.rsr_indicator_id = ri.id
                    LEFT JOIN rsr_results rr ON ri.rsr_result_id = rr.id
                    LEFT JOIN rsr_projects rp ON rr.rsr_project_id = rp.id
                    LEFT JOIN partnerships p ON rp.partnership_id = p.id
                    LEFT JOIN partnerships c ON c.id = p.parent_id
                    LEFT JOIN rsr_titleables tpr ON tpr.rsr_titleable_id = rpr.rsr_indicator_id
                    LEFT JOIN rsr_titles rtpr ON tpr.rsr_title_id = rtpr.id
                    LEFT JOIN rsr_titleables tr ON tr.rsr_titleable_id = rr.id
                    LEFT JOIN rsr_titles rtr ON tr.rsr_title_id = rtr.id
                    WHERE rp.partnership_id IS NOT NULL
                    AND p.level = 'partnership'
                    AND rpr.actual_value > 0
                    GROUP BY
                        p.id,
                        ri.has_dimension,
                        rtr.title,
                        rtpr.title,
                        rpr.rsr_indicator_id
                ) AS md
                JOIN rsr_periods rsrp
                    ON rsrp.rsr_indicator_id = md.indicator_id
                    AND rsrp.actual_value = md.max_period_value
                LEFT JOIN rsr_period_dimension_values pdv ON rsrp.id = pdv.rsr_period_id
                LEFT JOIN rsr_dimension_values dv ON dv.id = pdv.rsr_dimension_value_id
                LEFT JOIN rsr_titleables tdv ON tdv.rsr_titleable_id = dv.id
                LEFT JOIN rsr_titles rtdv ON tdv.rsr_title_id = rtdv.id
                LEFT JOIN rsr_titleables td ON td.rsr_titleable_id = dv.rsr_dimension_id
                LEFT JOIN rsr_titles rtd ON td.rsr_title_id = rtd.id
                WHERE md.result_title LIKE '%UII-1%'
                OR md.result_title LIKE '%UII-2%'
                OR md.result_title LIKE '%UII-3%';
        ");
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
            CREATE OR REPLACE VIEW rsr_max_custom_values AS
                SELECT
                    md.country,
                    md.partnership_code,
                    md.result_title,
                    md.indicator_id,
                    md.indicator_title,
                    md.has_dimension,
                    rsrp.id period_id,
                    rtd.title AS dimension_title,
                    dv.id AS dimension_value_id,
                    rtdv.title AS dimension_value_title,
                    md.max_period_value,
                    pdv.value AS max_actual_value
                FROM
                    (
                    SELECT
                        p.code partnership_code,
                        c.name country,
                        rtr.title AS result_title,
                        rpr.rsr_indicator_id AS indicator_id,
                        ri.has_dimension,
                        rtpr.title AS indicator_title,
                        MAX(rpr.actual_value) AS max_period_value
                    FROM rsr_periods rpr
                    LEFT JOIN rsr_indicators ri ON rpr.rsr_indicator_id = ri.id
                    LEFT JOIN rsr_results rr ON ri.rsr_result_id = rr.id
                    LEFT JOIN rsr_projects rp ON rr.rsr_project_id = rp.id
                    LEFT JOIN partnerships p ON rp.partnership_id = p.id
                    LEFT JOIN partnerships c ON c.id = p.parent_id
                    LEFT JOIN rsr_titleables tpr ON tpr.rsr_titleable_id = rpr.rsr_indicator_id
                    LEFT JOIN rsr_titles rtpr ON tpr.rsr_title_id = rtpr.id
                    LEFT JOIN rsr_titleables tr ON tr.rsr_titleable_id = rr.id
                    LEFT JOIN rsr_titles rtr ON tr.rsr_title_id = rtr.id
                    WHERE rp.partnership_id IS NOT NULL
                    AND p.level = 'partnership'
                    AND rpr.actual_value > 0
                    GROUP BY
                        p.id,
                        ri.has_dimension,
                        rtr.title,
                        rtpr.title,
                        rpr.rsr_indicator_id
                ) AS md
                JOIN rsr_periods rsrp
                    ON rsrp.rsr_indicator_id = md.indicator_id
                    AND rsrp.actual_value = md.max_period_value
                LEFT JOIN rsr_period_dimension_values pdv ON rsrp.id = pdv.rsr_period_id
                LEFT JOIN rsr_dimension_values dv ON dv.id = pdv.rsr_dimension_value_id
                LEFT JOIN rsr_titleables tdv ON tdv.rsr_titleable_id = dv.id
                LEFT JOIN rsr_titles rtdv ON tdv.rsr_title_id = rtdv.id
                LEFT JOIN rsr_titleables td ON td.rsr_titleable_id = dv.rsr_dimension_id
                LEFT JOIN rsr_titles rtd ON td.rsr_title_id = rtd.id
                WHERE md.result_title LIKE '%UII-1%'
                OR md.result_title LIKE '%UII-2%'
                OR md.result_title LIKE '%UII-3%';
            "
        );
    }
}
