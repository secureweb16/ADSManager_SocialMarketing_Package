<?php

namespace Secureweb\Socialmarketing;

use Secureweb\Socialmarketing\Classes\Telegram;

// use Secureweb\Socialmarketing\Models\Campaignmessage;

class SocialMarketing{

	protected $valid_actions;
	protected $valid_social_accounts;
	/*
	 * ***********************************************
	 * $request variable is used to save instance of
	 * different social media platforms
	 * ***********************************************
	*/
	protected $request;

	protected $type;
	protected $message;
	protected $action;
	protected $publisher_id;
	protected $advertiser_id;
	protected $telegram_group_id;
	protected $campaigns_id;
	protected $unique_id='';
	protected $consumer_key = '';
	protected $consumer_secret = '';
	protected $access_token = '';
	protected $token_secret = '';
	protected $group_channel_id = '';
	protected $image_url = '';
	protected $message_id = '';
	protected $campaignmessage_id = '';

	public function __construct(
		$type,
		$message,
		$action,
		$publisher_id,
		$advertiser_id,
		$telegram_group_id = '',
		$campaigns_id,
		$unique_id='',
		$consumer_key = '',
		$consumer_secret = '',
		$access_token = '',
		$token_secret = '',
		$group_channel_id = '',
		$image_url = '',
		$message_id = '',
		$campaignmessage_id = ''
	){
		$this->type = $type;
		$this->message = $message;
		$this->action = $action;
		$this->publisher_id = $publisher_id;
		$this->advertiser_id = $advertiser_id;
		$this->campaigns_id = $campaigns_id;
		$this->unique_id = $unique_id;
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		$this->access_token = $access_token;
		$this->token_secret = $token_secret;
		$this->group_channel_id = $group_channel_id;
		$this->telegram_group_id = $telegram_group_id;
		$this->image_url = $image_url;
		$this->message_id = $message_id;
		$this->campaignmessage_id = $campaignmessage_id;
		
		if(!$this->valid_actions() || !$this->valid_social_accounts()){
			throw new \Exception('Either action or social platform is not correct.');
		}

		switch($this->type){
			case 'telegram':
				$method = 'telegram';
			break;

			case 'facebook':
				$method = 'facebook';
			break;

			case 'instagram':
				$method = 'instagram';
			break;

			case 'twitter':
				$method = 'twitter';
			break;
		}

		$this->$method();
	}

	/**
	 * **********
	 * TELEGRAM *
	 * **********
	 * */
	public function telegram(){		
		$this->request = new Telegram(
			$this->group_channel_id,
			$this->message,
			$this->action,
			$this->publisher_id,
            $this->advertiser_id,
            $this->campaigns_id,
            $this->unique_id,
            $this->telegram_group_id,
            $this->message_id,
            $this->campaignmessage_id,
		);
	}
	/*
	 ******************************************
	 * Send request to social media platforms *
	 * ****************************************
	 * ***/
	public function sendRequest(){
		return $this->request->sendRequest();
	}
	/*
	 *****************
	 * Valid Actions *
	 * ***************
	*/
	protected function valid_actions(){
		$this->valid_actions = collect([
			'send',
			'delete',
			'checkMemberAccess'
		]);
		if($this->valid_actions->contains($this->action))
			return true;
		return false;
	}
	/*
	 *************************
	 * Valid Social Accounts *
	 * ***********************
	*/
	protected function valid_social_accounts(){
		$this->valid_social_accounts = collect([
			'telegram'
		]);
		if($this->valid_social_accounts->contains($this->type))
			return true;
		return false;
	}

}