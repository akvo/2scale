<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RsrDimensionValue extends Model
{
    protected $hidden = ['created_at','updated_at'];
    // protected $fillable = ['id', 'rsr_dimension_id', 'parent_dimension_value', 'name', 'value'];
    protected $fillable = ['rsr_dimension_id', 'rsr_dimension_value_id', 'rsr_dimension_value_target_id', 'parent_dimension_value', 'value'];
    protected $appends = ['name'];

    public function rsr_dimension()
    {
        return $this->belongsTo('\App\RsrDimension');
    }

    public function rsr_period_dimension_values()
    {
        return $this->hasMany('\App\RsrPeriodDimensionValue');
    }

    public function childrens()
    {
        return $this->hasMany('\App\RsrDimensionValue','parent_dimension_value','rsr_dimension_value_id');
    }

    public function parents()
    {
        return $this->belongsTo('\App\RsrDimensionValue','parent_dimension_value','rsr_dimension_value_id');
    }

    public function rsr_titles()
    {
        return $this->morphToMany('App\RsrTitle', 'rsr_titleable', 'rsr_titleables', 'rsr_titleable_id', 'rsr_title_id', 'rsr_dimension_value_id', 'id');
    }

    public function getNameAttribute($value)
    {
        return $this->rsr_titles()->pluck('title')[0];
    }
}
