<?php

namespace Secureweb\Socialmarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;

class Socialmarketing extends Model
{
    use HasFactory;

    protected $table = 'socialmarketings';

    public function user(){
        return $this->belongsTo(User::class,'id','user_id');
    }
}
