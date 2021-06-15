<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RnrGender extends Model
{
    protected $table = 'rnr_gender';
    protected $appends = ['year'];

    public function getYearAttribute($value)
    {
        $date = new Carbon($this->event_date);
        return $date->year;
    }
}
