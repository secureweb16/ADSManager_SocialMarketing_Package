<?php

namespace Secureweb\Socialmarketing\Facades;

use Illuminate\Support\Facades\Facade;

class SocialMarketing extends Facade{
	protected static function getFacadeAccessor(){
		return 'socialmarketing';
	}
}

