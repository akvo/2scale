<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RsrTitle extends Model
{
    protected $hidden = ['created_at','updated_at'];
    protected $fillable = ['id', 'title', 'description'];

    public function rsr_results()
    {
        return $this->morphedByMany('App\RsrResult', 'rsr_titleable');
    }

    public function rsr_indicators()
    {
        return $this->morphedByMany('App\RsrIndicator', 'rsr_titleable');
    }

    public function rsr_dimensions()
    {
        return $this->morphedByMany('App\RsrDimension', 'rsr_titleable', 'rsr_titleables', 'rsr_titleable_id', 'rsr_title_id', 'rsr_dimension_id', 'id');
    }

    public function rsr_dimension_values()
    {
        return $this->morphedByMany('App\RsrDimensionValue', 'rsr_titleable', 'rsr_titleables', 'rsr_titleable_id', 'rsr_title_id', 'rsr_dimension_value_id', 'id');
    }
}
