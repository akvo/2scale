<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRsrOverviews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("
            CREATE OR REPLACE VIEW rsr_overviews AS
                SELECT
                    data.project_id,
                    data.project_title,
                    data.partnership_code,
                    data.country,
                    data.result_id,
                    tres.title as result_title,
                    data.indicator_id,
                    tind.title as indicator_title,
                    data.indicator_target,
                    data.has_dimension,
                    data.period_id,
                    data.rsr_dimension_id,
                    tdim.title as dimension_title,
                    data.rsr_dimension_value_id,
                    tdimv.title as dimension_value_title,
                    data.dimension_target,
                    data.period_value,
                    pdv.value as period_dimension_actual_value
                FROM(
                    SELECT
                        rp.id as project_id,
                        rp.title as project_title,
                        p.code partnership_code,
                        c.name country,
                        ri.rsr_result_id as result_id,
                        rpr.rsr_indicator_id AS indicator_id,
                        ri.target_value as indicator_target,
                        ri.has_dimension,
                        rpr.id as period_id,
                        rd.id as dimension_id,
                        rd.rsr_dimension_id,
                        rdv.id as dimension_value_id,
                        rdv.rsr_dimension_value_id,
                        rdv.value as dimension_target,
                        MAX(rpr.actual_value) AS period_value
                    FROM rsr_periods rpr
                    LEFT JOIN rsr_indicators ri ON rpr.rsr_indicator_id = ri.id
                    LEFT JOIN rsr_dimensions rd ON ri.id = rd.rsr_indicator_id
                    LEFT JOIN rsr_dimension_values rdv ON rd.id = rdv.rsr_dimension_id
                    LEFT JOIN rsr_results rr ON ri.rsr_result_id = rr.id
                    LEFT JOIN rsr_projects rp ON rr.rsr_project_id = rp.id
                    LEFT JOIN partnerships p ON rp.partnership_id = p.id
                    LEFT JOIN partnerships c ON c.id = p.parent_id
                    WHERE rp.partnership_id IS NOT NULL
                    AND p.level = 'partnership'
                    AND rpr.actual_value > 0
                    GROUP BY
                        p.id,
                        ri.has_dimension,
                        rpr.rsr_indicator_id,
                        rd.id,
                        rdv.id,
                        rpr.id
                ) AS data
                LEFT JOIN rsr_period_dimension_values pdv ON data.period_id = pdv.rsr_period_id
                AND data.rsr_dimension_value_id = pdv.rsr_dimension_value_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'result'
                ) AS tres ON tres.type_id = data.result_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'indicator'
                ) AS tind ON tind.type_id = data.indicator_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'dimension'
                ) AS tdim ON tdim.type_id = data.rsr_dimension_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'dimensionvalue'
                ) AS tdimv ON tdimv.type_id = data.rsr_dimension_value_id
                WHERE tres.title LIKE '%UII-1%'
                OR tres.title LIKE '%UII-2%'
                OR tres.title LIKE '%UII-3%'

                UNION ALL

                SELECT
                    data.project_id,
                    data.project_title,
                    data.partnership_code,
                    data.country,
                    data.result_id,
                    tres.title as result_title,
                    data.indicator_id,
                    tind.title as indicator_title,
                    data.indicator_target,
                    data.has_dimension,
                    data.period_id,
                    data.rsr_dimension_id,
                    tdim.title as dimension_title,
                    data.rsr_dimension_value_id,
                    tdimv.title as dimension_value_title,
                    data.dimension_target,
                    data.period_value,
                    pdv.value as period_dimension_actual_value
                FROM(
                    SELECT
                        rp.id as project_id,
                        rp.title as project_title,
                        p.code partnership_code,
                        c.name country,
                        ri.rsr_result_id as result_id,
                        rpr.rsr_indicator_id AS indicator_id,
                        ri.target_value as indicator_target,
                        ri.has_dimension,
                        rpr.id as period_id,
                        rd.id as dimension_id,
                        rd.rsr_dimension_id,
                        rdv.id as dimension_value_id,
                        rdv.rsr_dimension_value_id,
                        rdv.value as dimension_target,
                        SUM(rpr.actual_value) AS period_value
                    FROM rsr_periods rpr
                    LEFT JOIN rsr_indicators ri ON rpr.rsr_indicator_id = ri.id
                    LEFT JOIN rsr_dimensions rd ON ri.id = rd.rsr_indicator_id
                    LEFT JOIN rsr_dimension_values rdv ON rd.id = rdv.rsr_dimension_id
                    LEFT JOIN rsr_results rr ON ri.rsr_result_id = rr.id
                    LEFT JOIN rsr_projects rp ON rr.rsr_project_id = rp.id
                    LEFT JOIN partnerships p ON rp.partnership_id = p.id
                    LEFT JOIN partnerships c ON c.id = p.parent_id
                    WHERE rp.partnership_id IS NOT NULL
                    AND p.level = 'partnership'
                    AND rpr.actual_value > 0
                    GROUP BY
                        p.id,
                        ri.has_dimension,
                        rpr.rsr_indicator_id,
                        rd.id,
                        rdv.id,
                        rpr.id
                ) AS data
                LEFT JOIN rsr_period_dimension_values pdv ON data.period_id = pdv.rsr_period_id
                AND data.rsr_dimension_value_id = pdv.rsr_dimension_value_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'result'
                ) AS tres ON tres.type_id = data.result_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'indicator'
                ) AS tind ON tind.type_id = data.indicator_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'dimension'
                ) AS tdim ON tdim.type_id = data.rsr_dimension_id
                LEFT JOIN (
                    SELECT * FROM rsr_view_titles WHERE type = 'dimensionvalue'
                ) AS tdimv ON tdimv.type_id = data.rsr_dimension_value_id
                WHERE tres.title NOT LIKE '%UII-1%'
                AND tres.title NOT LIKE '%UII-2%'
                AND tres.title NOT LIKE '%UII-3%';
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("DROP VIEW IF EXISTS `rsr_overviews`;");
    }
}
