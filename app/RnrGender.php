<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RnrGender extends Model
{
    protected $table = 'rnr_gender';
    protected $appends = ['year', 'month'];

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
}
