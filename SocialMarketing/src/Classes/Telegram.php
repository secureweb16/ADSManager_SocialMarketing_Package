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
	private $tg_bot_username;
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
		$this->tg_bot_username =  config('socialmarketing.telegram.tg_bot_username');
	}

	private function validInputParameters(){
		return [
			'title',
			'image',
			'link',
			'description'
		];
	}

	public function sendRequest(){

		switch($this->action){
			case 'send':
				$max_size_description = config('socialmarketing.telegram.api.description_limit');
				$input_array_keys = array_keys($this->message);

				$array_size = array_diff($this->validInputParameters(),$input_array_keys);

				if(count($array_size) != 0){
					throw new \Exception('Make sure input parameters are correct.');
				}


				$this->message['description'] = html_entity_decode($this->message['description']);

				if(strlen($this->message['description']>$max_size_description)){
					throw new \Exception('Character Limit is 1-'.$max_size_description);
				}
				$this->response = $this->sendPhoto();
			break;

			case 'delete':
				$url = $this->deleteMessage();
				$this->response = Http::get($url);
			break;

			case 'checkMemberAccess':
				$this->response = $this->getChatAccessWithWelcomeMessage();
			break;
		}
	
		// $this->response = Http::get($url);


		#save logs
		if(config('socialmarketing.telegram.logs')){
			$file_name = date('Ymd') ."-telegram-{$this->advertiser_id}-{$this->telegram_group_id}-" . time();
			$this->save_logs('telegram', $file_name , $this->response);
		}
		
		if($this->response->ok()){
			if($this->action === 'send'){ 
				return $this->parse_success_response(); 
			} else if($this->action === 'delete'){
			 return $this->parse_delete_response(); 
			} else if($this->action === 'checkMemberAccess') {
				$result = $this->response->json();
				$bot_username = $result["result"]["from"]["username"];
				if($bot_username == $this->tg_bot_username){
					$publisher_group_name = $result["result"]["chat"]["title"];
					$confirm_result = $this->confirmWelcomeMessage($publisher_group_name);
					$confirm_result = $confirm_result->json();
					return $confirm_result["ok"];
				} else {
					return false;
				}
			}
		}
		
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
<b>{$this->message['title']}</b>
{$this->message['description']}
<a href="https://moonlaunch.media/">Ad By Moon Launch Media</a>
TEXT;
        
        return Http::post($url, [
            'chat_id' => '@'.$this->group_channel_id,
            'photo' => $this->message['image'],
            'caption' => $api_message,
            'parse_mode' => 'html',
            'reply_markup' => [
		    	'inline_keyboard' => [[[
		    			'text' => 'Open Link for '.$this->message['title'],
		    			'url' => $this->message['link']
		    	]]]
		    ]
        ]);
	}

	private function sendMessage(){

		$campaign_name = get_campaign_name($this->campaigns_id);

		$url = $this->base_url 
			. "/" . $this->access_token
			. "/sendMessage";

$api_message = <<<TEXT
<b>{$this->message['title']}</b>
<a href="{$this->message['image']}">&#8205;</a>
{$this->message['description']}
<a href="https://moonlaunch.media">Ad By Moon Launch Media</a>
TEXT;
		
		return Http::post($url, [
		    'chat_id' => '@'.$this->group_channel_id,
		    'text' => $api_message,
		    'parse_mode' => 'html',
		    'reply_markup' => [
		    	'inline_keyboard' => [[[
		    			'text' => 'Open Link for '.$this->message['title'],
		    			'url' => $this->message['link']
		    	]]]
		    ],
		    'disable_web_page_preview' => false
		]);
	}

	private function deleteMessage(){
		return $this->base_url 
			. "/" . $this->access_token
			. "/deleteMessage"
			. "?chat_id=@" . $this->group_channel_id
			. "&message_id=" . $this->message_id;
	}

	private function getChatMemberAccess(){
		$url = $this->base_url 
			. "/" . $this->access_token
			. "/getChatMember";

		return Http::post($url, [
		    'chat_id' => '@'.$this->group_channel_id,
		    'user_id' => $this->tg_user_id
		]);
	}

	private function getChatAccessWithWelcomeMessage(){
		$url = $this->base_url 
			. "/" . $this->access_token
			. "/sendMessage";

		$api_message = <<<TEXT
You have successfully initiated the request to add <b>Moon Launch Bot</b> to your group. We will get back to you after confirmation.
TEXT;

		return Http::post($url, [
		    'chat_id' => '@'.$this->group_channel_id,
		    'text' => $api_message,
		    'parse_mode' => 'html'
		]);
	}

	private function confirmWelcomeMessage($publisher_group_name){
		$url = $this->base_url 
			. "/" . $this->access_token
			. "/sendMessage";

		$api_message = <<<TEXT
<b>Congratulations!!</b>
You have successfully added the <b>Moon Launch Bot</b> bot to your group {$publisher_group_name}.
TEXT;

		return Http::post($url, [
		    'chat_id' => '@'.$this->group_channel_id,
		    'text' => $api_message,
		    'parse_mode' => 'html'
		]);
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

send photo response

Array
(
    [ok] => 1
    [result] => Array
        (
            [message_id] => 130
            [from] => Array
                (
                    [id] => 5002415190
                    [is_bot] => 1
                    [first_name] => Moon Launch Bot
                    [username] => MoonLaunch_TGBot
                )

            [chat] => Array
                (
                    [id] => -1001628475693
                    [title] => banner_test4
                    [username] => banner_test4
                    [type] => supergroup
                )

            [date] => 1641540530
            [photo] => Array
                (
                    [0] => Array
                        (
                            [file_id] => AgACAgQAAx0EYRCRLQADgmHX67JbwGjfgpGsSzq7EDzz8qy-AAJSrTEbVvbEUo_NhQQYE4YGAQADAgADcwADIwQ
                            [file_unique_id] => AQADUq0xG1b2xFJ4
                            [file_size] => 337
                            [width] => 90
                            [height] => 90
                        )

                    [1] => Array
                        (
                            [file_id] => AgACAgQAAx0EYRCRLQADgmHX67JbwGjfgpGsSzq7EDzz8qy-AAJSrTEbVvbEUo_NhQQYE4YGAQADAgADbQADIwQ
                            [file_unique_id] => AQADUq0xG1b2xFJy
                            [file_size] => 825
                            [width] => 300
                            [height] => 300
                        )

                )

            [caption] => Camapign
This is a test post
Open Link for Camapign
            [caption_entities] => Array
                (
                    [0] => Array
                        (
                            [offset] => 29
                            [length] => 22
                            [type] => text_link
                            [url] => https://adsmanager.moonlaunch.media/telegram/drsm3p3dAEu5LYO1FMNz7XnzrVgAR0LwWRqqIfEvgviue6DL18/1640256162/2/1/16415405280
                        )

                )

        )

)

*/

