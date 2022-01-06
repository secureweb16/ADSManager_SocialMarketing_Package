<?php

namespace Secureweb\Socialmarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;


class Campaignmessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'campaignmessages';

    protected $fillable = [
        'publisher_id',
        'advertiser_id',
        'telegram_group_id',
        'campaigns_id',
        'unique_id',
        'message_id'
    ];

    public function publisher(){
        return $this->belongsTo(User::class,'id','publisher_id');
    }

    public function advertiser(){
        return $this->belongsTo(User::class,'id','advertiser_id');
    }

}
