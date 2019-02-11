<?php

namespace App;

use App\Traits\CreatorDetails;

class Report extends \Jenssegers\Mongodb\Eloquent\Model
{
    use CreatorDetails;
    
    protected $fillable = ['name', 'description', 'url', 'category','active'];

    public function category()
    {
        return $this->belongsTo('App\Category');
    }
}
