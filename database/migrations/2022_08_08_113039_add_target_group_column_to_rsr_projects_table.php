<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetGroupColumnToRsrProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rsr_projects', function (Blueprint $table) {
            $table->longText('target_group')->nullable()->after('goals_overview');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rsr_projects', function (Blueprint $table) {
            $table->dropColumn('target_group');
        });
    }
}
