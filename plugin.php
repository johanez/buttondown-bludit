<?php

class buttondown_newsletter extends Plugin {
	

	public function init()
	{
		global $site;
		// Fields and default values for the database of this plugin
		$this->dbFields = array(
			'apiKey'=>'', // APIKey form buttondown
			'paused'=> false,
			'startDate' => date('Y-m-d H:i:s'), // page creation date which newsletter will be send YYYY-MM-DD Hours:Minutes:Seconds
			'sentEmails'=> json_encode(array()),
			'includeCover' => false,
			'subjectPrefix' => 'New post on '.$site->title().': ',
		);
	}

	  // Method/function to process the post settings.
	public function post()
	{

	// Process the POST variables like in the parent function.
	// Code from file: bludit/bl-kernel/abstract/plugin.class.php
	$args = $_POST;
	foreach ($this->dbFields as $key=>$value) {
		if (isset($args[$key])) {
			$value = Sanitize::html($args[$key]);
			if ($value === 'false') {
			$value = false;
			} elseif ($value === 'true') {
			$value = true;
			}
			settype($value, gettype($this->dbFields[$key]));
			$this->db[$key] = $value;
		}
	}

	// Save the plugin settings to the database.
	return $this->save();

	}
  

	// Method called on the settings of the plugin on the admin area
	public function form()
	{
		global $L;
		$sentEmails = $this->getSentEmailsArray();

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		$html .= '<div>';
		// API key
		$html .= '<label><strong>Buttondown API Key</strong></label>';
		$html .= '<input id="apiKey" name="apiKey" type="text" value="'.$this->getValue('apiKey').'">';
		$html .= '<span class="tip">Copy your API key on https://buttondown.email/settings/programming </span>';
		// Sending paused
		$html .= '<label>'.$L->get('paused').'</label>';
		$html .= '<select id="paused" name="paused" type="text">';
		$html .= '<option value="true" '.($this->getValue('paused')===true?'selected':'').'>'.$L->get('is-paused').'</option>';
		$html .= '<option value="false" '.($this->getValue('paused')===false?'selected':'').'>'.$L->get('is-active').'</option>';
		$html .= '</select>';
		$html .= '<span class="tip">'.$L->get('paused-tip').'</span>';

		// Start date
		$html .= '<label>'.$L->get('send-after').'</label>';
		$html .= '<input id="startDate" name="startDate" type="text" value="'.$this->getValue('startDate').'">';
		$html .= '<span class="tip">'.$L->get('send-after-tip').'</span>';
		// Subject Prefix
		$html .= '<label>'.$L->get('subject-prefix').'</label>';
		$html .= '<input id="subjectPrefix" name="subjectPrefix" type="text" value="'.$this->getValue('subjectPrefix').'">';
		$html .= '<span class="tip">'.$L->get('subject-prefix-tip').'</span>';
				// Sending paused
		$html .= '<label>'.$L->get('include-cover').'</label>';
		$html .= '<select id="includeCover" name="includeCover" type="text">';
		$html .= '<option value="true" '.($this->getValue('includeCover')===true?'selected':'').'>'.$L->get('yes').'</option>';
		$html .= '<option value="false" '.($this->getValue('includeCover')===false?'selected':'').'>'.$L->get('no').'</option>';
		$html .= '</select>';
		$html .= '<span class="tip">'.$L->get('include-cover-tip').'</span>';
		$html .= '</div><hr>';
		// List of page keys for which mail was sent 
		$html .= '<h4>'.$L->get('sent-list').'</h4>';
		$html .= '<span class="tip">'.$L->get('sent-list-tip').'</span>';
		$html .= '<div style="overflow-y: scroll; height:400px;"><ul>';
		foreach ($sentEmails as $sentKey):
			$html .= '<li>'.$sentKey.'</li>';
		endforeach;
		$html .= '</ul></div>';
		$html .= '<div><a target="_blank" rel="noopener" href="https://buttondown.email/archive">Buttondown Archive</a></div>';
		return $html;
	}

	// Get array of sent email keys
	public function getSentEmailsArray() 
	{
		$sentEmailsJSON = $this->getValue('sentEmails',false);
		$sentEmails = json_decode($sentEmailsJSON, true);
		return $sentEmails;
	}


	public function sendEmail($key)
	{

		if (!$this->getValue('paused')){
			$apiKey = $this->getValue('apiKey'); 
			$sentEmails = $this->getSentEmailsArray();
			$url  = 'https://api.buttondown.email/v1/emails';
			$page = new Page($key);
			$startDateTS = strtotime($this->getValue('startDate'));
			$pageDateTS = strtotime($page->dateRaw()); 
			global $syslog;

			// Only if no newslettetr was sent and page is NOT static, ausosave, nonindex
			if ($page->published() and 
					!$page->isStatic() and 
					!$page->autosave() and
					!$page->noindex() and
					$startDateTS <= $pageDateTS and
					!in_array($key, $sentEmails)){

				$body = '<h1>'.$page->title().'</h1>'.$page->content();
				if($page->coverImage() and $this->getValue('includeCover')){
					$body = '<img class="teaser" src="'.$page->coverImage().'">'.$body;
				}
			
				$data = array(
					'body' => $body,
					'secondary_id' => $page->position(),
					'subject' => $this->getValue('subjectPrefix').$page->title(),
					'external_url' => $page->permalink(),
					'slug' => $page->slug(),
				);
				// use key 'http' even if you send the request to https://...
				$options = array(
					'http' => array(
						'header'  => "Authorization: Token {$apiKey}\r\n".
						"Content-Type: application/json\r\n",
						'method'  => 'POST',
						'content' => json_encode($data)
					)
				);
				$context  = stream_context_create($options);
				$result = file_get_contents($url, false, $context);
				if ($result === FALSE) { /* Handle error */ } else {
					// Add key to lsit of sent emails
					array_push($sentEmails, $key);
					$syslog->add(array(
						'dictionaryKey'=>'buttondown-sent',
						'notes'=>$key
					));
					$this->db['sentEmails'] = json_encode($sentEmails);
					var_dump($result);
					return $this->save();
				}
				
				var_dump($result);
			}
		}
		return false;
	}

	public function afterPageCreate($key)
	{
		$this -> sendEmail($key);
	}

	public function afterPageModify($key)
	{
		$this -> sendEmail($key);
	}

}

?>