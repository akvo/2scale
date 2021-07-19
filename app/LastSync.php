<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LastSync extends Model
{
    protected $fillable = ['date'];
    public $timestamps = false;
}
