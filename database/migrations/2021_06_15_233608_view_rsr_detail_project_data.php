<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRsrDetailProjectData extends Migration
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
            CREATE OR REPLACE VIEW rsr_detail_project_data AS
            SELECT
                rr.order,
                rp.id as project_id,
                rp.title as project_title,
                rr.id result_id,
                rrtts.title as result_title,
                ri.id indicator_id,
                ri.baseline_year,
                ri.baseline_value,
                ritts.title as indicator_title,
                ritts.description as indicator_description,
                ri.target_value as indicator_target_value,
                ri.has_dimension as has_dimension,
                rd.id as dimension_id,
                rv.value as dimension_target_value,
                rdtts.title as dimension_title,
                rv.id as dimension_value_id,
                rvtts.title as dimension_value_title,
                rps.id as period_id,
                rps.target_value as period_target_value,
                rps.actual_value as period_actual_value
                FROM rsr_results rr
                LEFT JOIN rsr_projects rp ON rp.id = rr.rsr_project_id
                LEFT JOIN rsr_indicators ri ON ri.rsr_result_id = rr.id
                LEFT JOIN rsr_dimensions rd ON rd.rsr_indicator_id =  ri.id
                LEFT JOIN rsr_dimension_values rv ON rv.rsr_dimension_id = rd.id
                LEFT JOIN rsr_periods rps ON rps.rsr_indicator_id = ri.id
                LEFT JOIN rsr_titleables rrtt ON rrtt.rsr_titleable_id = rr.id
                LEFT JOIN rsr_titles rrtts ON rrtts.id = rrtt.rsr_title_id
                LEFT JOIN rsr_titleables rvtt ON rvtt.rsr_titleable_id = rv.id
                LEFT JOIN rsr_titles rvtts ON rvtts.id = rvtt.rsr_title_id
                LEFT JOIN rsr_titleables rdtt ON rdtt.rsr_titleable_id = rd.id
                LEFT JOIN rsr_titles rdtts ON rdtts.id = rdtt.rsr_title_id
                LEFT JOIN rsr_titleables ritt ON ritt.rsr_titleable_id = ri.id
                LEFT JOIN rsr_titles ritts ON ritts.id = ritt.rsr_title_id
            ORDER BY rr.order
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
        \DB::statement("DROP VIEW IF EXISTS rsr_detail_project_data;");
    }
}
