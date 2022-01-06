<?php

namespace Secureweb\Socialmarketing\Classes;

use Illuminate\Support\Facades\Http;

use Secureweb\Socialmarketing\Models\Campaignmessage;
use Secureweb\Socialmarketing\Traits\SocialMarketingLog;

use App\Model\Campaign;

class Telegram{

	use SocialMarketingLog;

	private $base_url;
	private $response;
	private $access_token;
	protected $group_channel_id;
	protected $message;
	protected $action;
	protected $publisher_id;
	protected $advertiser_id;
	protected $telegram_group_id;
	protected $campaigns_id;
	protected $unique_id='';
	protected $message_id = '';
	protected $image_url = '';

	public function __construct(
		$group_channel_id,
		$message,
		$action,
		$publisher_id,
		$advertiser_id,
		$campaigns_id,
		$unique_id,
		$telegram_group_id,
		$message_id = '',
		$campaignmessage_id = '',
		$image_url = ''
	){
		$this->group_channel_id = $group_channel_id;
		$this->message = $message;
		$this->action = $action;
		$this->publisher_id = $publisher_id;
		$this->advertiser_id = $advertiser_id;
		$this->telegram_group_id = $telegram_group_id;
		$this->campaigns_id = $campaigns_id;
		$this->unique_id = $unique_id;
		$this->message_id = $message_id;
		$this->campaignmessage_id = $campaignmessage_id;
		$this->image_url = $image_url;
		$this->base_url 	=  config('socialmarketing.telegram.base_url');
		$this->access_token =  config('socialmarketing.telegram.access_token');
	}

	public function sendRequest(){
		switch($this->action){
			case 'send':
				$this->response = $this->sendMessage();
				// print "<pre>";print_r($this->response->json());print "</pre>";exit;
				// dd($this->response);
			break;

			case 'delete':
				$url = $this->deleteMessage();
				$this->response = Http::get($url);
			break;
		}
	
		// $this->response = Http::get($url);


		#save logs
		$file_name = date('Ymd') ."-telegram-{$this->advertiser_id}-{$this->telegram_group_id}-" . time();
		$this->save_logs('telegram', $file_name , $this->response);
		
		if($this->response->ok())
			if($this->action === 'send'){ return $this->parse_success_response(); } else { return $this->parse_delete_response(); }
		
		return $this->response->json();
	}

	private function parse_success_response(){ 
		$result = $this->response->json();
		if(isset($result["ok"])){
			$message_id = $result["result"]["message_id"];
		}
	
		Campaignmessage::create([
			'publisher_id' 			=> $this->publisher_id,
			'advertiser_id' 		=> $this->advertiser_id,
			'telegram_group_id' 	=> $this->telegram_group_id,
			'campaigns_id' 			=> $this->campaigns_id,
			'unique_id' 			=> $this->unique_id,
			'message_id' 			=> $message_id,
		]);

		return ['ok' => true, 'message' => 'Message has sent successfully'];
	}

	private function parse_delete_response(){
		$result = $this->response->json();		
		$campaignmessage = Campaignmessage::findOrFail($this->campaignmessage_id);
        $campaignmessage->delete();
        return ['ok' => true, 'message' => 'Message deleted successfully'];
	}

	private function sendMessage(){

		$campaign_name = get_campaign_name($this->campaigns_id);

		$url = $this->base_url 
			. "/" . $this->access_token
			. "/sendMessage";

		$message = <<<TEXT
*** Title Here again***
*** This is the description of the photo. This could be little long with more characters ***
<a href="{$$this->message}">Open Link for {$campaign_name}</a>
<a href="https://moonlaunch.media/wp-content/uploads/2021/12/mobile-4-300x174.png"> ‏ </a>
TEXT;
		
		return Http::post($url, [
		    'chat_id' => '@banner_test2',
		    'text' => $message,
		    'parse_mode' => 'html',
		    'disable_web_page_preview' => false
		    // 'reply_markup' => [
		    // 	'inline_keyboard' => [[[
		    // 			'text' => 'Open Link for '.$campaign_name,
		    // 			'url' => $this->message
		    // 	]]]
		    // ]
		]);

		// return $this->base_url 
		// 	. "/" . $this->access_token
		// 	. "/sendMessage"
		// 	. "?chat_id=@" . $this->group_channel_id
		// 	. "&text='". $this->message."'";
	}

	private function deleteMessage(){
		return $this->base_url 
			. "/" . $this->access_token
			. "/deleteMessage"
			. "?chat_id=@" . $this->group_channel_id
			. "&message_id=" . $this->message_id;
	}
}

/*

{
    "ok": true,
    "result": {
        "message_id": 6,
        "from": {
            "id": 2141555629,
            "is_bot": true,
            "first_name": "BannerNetworkBot",
            "username": "banner_network_bot"
        },
        "chat": {
            "id": -1001723057155,
            "title": "Banner Network Patrick",
            "username": "bannernetworkparick",
            "type": "supergroup"
        },
        "date": 1637753198,
        "text": "This is demo test"
    }
}


*/

