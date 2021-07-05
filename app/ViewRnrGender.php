<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ViewRnrGender extends Model
{
    protected $table = 'rnr_gender';
    protected $appends = ['year', 'month','year_month'];

    public function getYearAttribute($value)
    {
        $date = new Carbon($this->event_date);
        return $date->year;
    }

    public function getMonthAttribute($value)
    {
        $date = new Carbon($this->event_date);
        return $date->month;
    }

    public function getYearMonthAttribute()
    {
        $date = new Carbon($this->event_date);
        return $date->year.'-'.$date->month;
    }
}
