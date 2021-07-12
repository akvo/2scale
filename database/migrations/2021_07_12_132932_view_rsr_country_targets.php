<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRsrCountryTargets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("
            CREATE OR REPLACE VIEW rsr_country_targets AS
            SELECT
                r_p.id project_id,
                p.name country,
                r_r.id result_id,
                vt_r.title result_title,
                r_i.id indicator_id,
                r_i.target_value indicator_target,
                r_d.rsr_dimension_id dimension_id,
                vt_d.title dimension_title,
                r_dv.rsr_dimension_value_id dimension_value_id,
                vt_dv.title dimension_value_title,
                r_dv.value target_value
            FROM rsr_projects r_p
                LEFT JOIN partnerships p ON p.id = r_p.partnership_id
                LEFT JOIN rsr_results r_r ON r_r.rsr_project_id = r_p.id
                LEFT JOIN rsr_indicators r_i ON r_i.rsr_result_id = r_r.id
                LEFT JOIN rsr_dimensions r_d ON r_d.rsr_indicator_id = r_i.id AND r_d.rsr_project_id = r_p.id
                LEFT JOIN (SELECT * FROM rsr_dimension_values WHERE rsr_dimension_value_target_id IS NOT NULL) r_dv ON r_dv.rsr_dimension_id = r_d.id
                LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'result') vt_r ON vt_r.type_id = r_r.id
                LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'dimension') vt_d ON vt_d.type_id = r_d.rsr_dimension_id
                LEFT JOIN (SELECT * FROM rsr_view_titles WHERE type = 'dimensionvalue') vt_dv ON vt_dv.type_id = r_dv.rsr_dimension_value_id
            WHERE p.level = 'country'
            ORDER BY country, result_id, dimension_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("DROP VIEW IF EXISTS `rsr_country_targets`;");
    }
}
