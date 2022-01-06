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

	private function validParametersForMessage(){
		return [
			'title',
			'image',
			'link',
			'description'
		];
	}

	public function sendRequest(){
		$input_array_keys = array_keys($this->message);

		$array_size = array_diff($this->validParametersForMessage(),$input_array_keys);

		if(count($array_size) != 0){
			throw new \Exception('Make sure message parameters have correct values.');
		}

		switch($this->action){
			case 'send':
				$this->response = $this->sendPhoto();
			break;

			case 'delete':
				$url = $this->deleteMessage();
				$this->response = Http::get($url);
			break;
		}
	
		// $this->response = Http::get($url);


		#save logs
		if(config('socialmarketing.telegram.logs')){
			$file_name = date('Ymd') ."-telegram-{$this->advertiser_id}-{$this->telegram_group_id}-" . time();
			$this->save_logs('telegram', $file_name , $this->response);
		}
		
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

	private function sendPhoto(){
		$campaign_name = get_campaign_name($this->campaigns_id);

		$url = $this->base_url 
			. "/" . $this->access_token
			. "/sendPhoto";

			$api_message = <<<TEXT
{$this->message['title']}
{$this->message['description']}
<a href="{$this->message['link']}">Open Link for {$this->message['title']}</a>
TEXT;
        
        return Http::post($url, [
            'chat_id' => '@banner_test2',
            'photo' => $this->message['image'],
            'caption' => $api_message,
            'parse_mode' => 'html',
        ]);
	}

	private function sendMessage(){

		$campaign_name = get_campaign_name($this->campaigns_id);

		$url = $this->base_url 
			. "/" . $this->access_token
			. "/sendMessage";

		$api_message = <<<TEXT
ADS Manager Moon Launch Media
This is the customised CPC link, For now this is just for testing.
<a href="{$this->message}">Open Link for {$campaign_name}</a>
TEXT;
		
		return Http::post($url, [
		    'chat_id' => '@banner_test2',
		    'text' => $api_message,
		    'parse_mode' => 'html',
		    // 'reply_markup' => [
		    // 	'inline_keyboard' => [[[
		    // 			'text' => 'Open Link for '.$campaign_name,
		    // 			'url' => $this->message
		    // 	]]]
		    // ]
		]);
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

