<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRsrCountryOverviews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("
            CREATE OR REPLACE VIEW rsr_country_overviews AS
                SELECT
                    data.*
                FROM
                    (SELECT  dm.*, rpdv.value AS period_dimension_actual_value
                        FROM (
                            SELECT
                                rp.id project_id,
                                rp.title project_title,
                                p.level,
                                p.code partnership_code,
                                c.name country,
                                ri.rsr_result_id result_id,
                                rpr.rsr_indicator_id indicator_id,
                                rt.title result_title,
                                ri.target_value indicator_target,
                                ri.has_dimension,
                                rd.id dimension_id,
                                rd.rsr_dimension_id,
                                rdv.id dimension_value_id,
                                rdv.rsr_dimension_value_id,
                                dt.title dimension_title,
                                'max' agg_type,
                                MAX(rpr.actual_value) period_value,
                                dtv.title dimension_value_title,
                                COALESCE(rdv.value, 0) dimension_target_value
                            FROM (SELECT id, rsr_indicator_id, actual_value FROM rsr_periods WHERE actual_value > 0) rpr
                            LEFT JOIN rsr_indicators ri ON rpr.rsr_indicator_id = ri.id
                            LEFT JOIN rsr_dimensions rd ON ri.id = rd.rsr_indicator_id
                            LEFT JOIN rsr_dimension_values rdv ON rd.id = rdv.rsr_dimension_id
                            LEFT JOIN rsr_results rr ON ri.rsr_result_id = rr.id
                            LEFT JOIN rsr_projects rp ON rr.rsr_project_id = rp.id
                            LEFT JOIN partnerships p ON rp.partnership_id = p.id
                            LEFT JOIN partnerships c ON c.id = p.parent_id
                            LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'result') rt ON rt.type_id = rr.id
                            LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'dimension') dt ON dt.type_id = rd.rsr_dimension_id
                            LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'dimensionvalue') dtv ON dtv.type_id = rdv.rsr_dimension_value_id
                            WHERE rt.title LIKE 'UII-1%' OR rt.title LIKE 'UII-2%' OR rt.title LIKE 'UII-3%'
                            GROUP BY p.code , ri.has_dimension , rpr.rsr_indicator_id , rt.title , rd.id , rdv.id , dt.title , dtv.title) dm
                        LEFT JOIN rsr_periods rp
                            ON rp.rsr_indicator_id = dm.indicator_id
                            AND rp.actual_value = dm.period_value
                        LEFT JOIN rsr_period_dimension_values rpdv
                            ON rpdv.rsr_period_id = rp.id
                            AND rpdv.rsr_dimension_value_id = dm.rsr_dimension_value_id
                        WHERE dm.level = 'partnership'
                    UNION ALL
                        SELECT ds.*, SUM(rpdv.value)
                        FROM (
                            SELECT
                                rp.id project_id,
                                rp.title project_title,
                                p.level,
                                p.code partnership_code,
                                c.name country,
                                ri.rsr_result_id result_id,
                                rpr.rsr_indicator_id indicator_id,
                                rt.title result_title,
                                ri.target_value indicator_target,
                                ri.has_dimension,
                                COALESCE(rd.id, NULL) dimension_id,
                                COALESCE(rd.rsr_dimension_id, NULL) rsr_dimension_id,
                                COALESCE(rdv.id, NULL) dimension_value_id,
                                COALESCE(rdv.rsr_dimension_value_id, NULL) rsr_dimension_value_id,
                                COALESCE(dt.title, NULL) dimension_title,
                                'sum' agg_type,
                                SUM(rpr.actual_value) period_value,
                                COALESCE(dtv.title, NULL) dimension_value_title,
                                COALESCE(rdv.value, 0) dimension_target_value
                            FROM (SELECT id, rsr_indicator_id, actual_value FROM rsr_periods WHERE actual_value > 0) rpr
                            LEFT JOIN rsr_indicators ri ON rpr.rsr_indicator_id = ri.id
                            LEFT JOIN rsr_results rr ON ri.rsr_result_id = rr.id
                            LEFT JOIN rsr_projects rp ON rr.rsr_project_id = rp.id
                            LEFT JOIN rsr_dimensions rd ON ri.id = rd.rsr_indicator_id
                            LEFT JOIN rsr_dimension_values rdv ON rd.id = rdv.rsr_dimension_id
                            LEFT JOIN partnerships p ON rp.partnership_id = p.id
                            LEFT JOIN partnerships c ON c.id = p.id
                            LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'result') rt ON rt.type_id = rr.id
                            LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'dimension') dt ON dt.type_id = rd.rsr_dimension_id
                            LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'dimensionvalue') dtv ON dtv.type_id = rdv.rsr_dimension_value_id
                            WHERE rt.title
                                NOT LIKE 'UII-1%'
                                AND rt.title NOT LIKE 'UII-2%'
                                AND rt.title NOT LIKE 'UII-3%'
                            GROUP BY p.code , ri.has_dimension , rpr.rsr_indicator_id , rd.id , rt.title , dt.title , rdv.id , dtv.title) ds
                            LEFT JOIN rsr_periods rp
                                ON rp.rsr_indicator_id = ds.indicator_id
                                AND rp.actual_value = ds.period_value
                            LEFT JOIN rsr_period_dimension_values rpdv
                                ON rpdv.rsr_period_id = rp.id
                                AND rpdv.rsr_dimension_value_id = ds.rsr_dimension_value_id
                            WHERE ds.level = 'country'
                            GROUP BY
                                ds.partnership_code,
                                ds.has_dimension,
                                ds.indicator_id,
                                ds.dimension_id,
                                ds.rsr_dimension_id,
                                ds.dimension_title,
                                ds.result_title,
                                ds.dimension_value_id,
                                ds.dimension_value_title,
                                ds.rsr_dimension_value_id,
                                ds.period_value,
                                ds.dimension_target_value) data
                ORDER BY
                    data.country,
                    data.partnership_code,
                    data.result_title,
                    data.dimension_title;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("DROP VIEW IF EXISTS `rsr_country_overviews`;");
    }
}
