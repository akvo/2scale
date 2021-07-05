<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ViewRsrTitles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::Statement('
            CREATE OR REPLACE VIEW rsr_view_titles AS
                SELECT
                    rt.rsr_titleable_id as type_id,
                    LOWER(REPLACE(rt.rsr_titleable_type, "App\\\\Rsr", "")) as type,
                    t.title
                FROM rsr_titleables rt
                LEFT JOIN rsr_titles t ON rt.rsr_title_id = t.id;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("DROP VIEW IF EXISTS `rsr_view_titles`;");
    }
}
