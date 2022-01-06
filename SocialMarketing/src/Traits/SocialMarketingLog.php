<?php

namespace Secureweb\Socialmarketing\Traits;

trait SocialMarketingLog{
	/*Save Logs*/
	protected function save_logs($directory, $filename, $logdata){
		$logpath = dirname(__DIR__).'/logs/'. $directory .'/'. $filename . '.txt';
  		$file = fopen ($logpath,'w');
  		fclose ($file);
  		file_put_contents($logpath, $logdata);
	}
}