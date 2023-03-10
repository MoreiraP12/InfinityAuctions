<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    public $timestamps = false;

    public function user(){
        return $this->belongsTo(User::class, 'user_id')->get();
    }

    public function auction(){
        return $this->belongsTo(Auction::class, 'auction_id')->get();
    }

    public function report(){
        return $this->belongsTo(Report::class, 'report_id')->get();
    }
}
