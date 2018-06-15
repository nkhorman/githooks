<?php

/**
 * @author      Initial coding and development - Neal Horman - http://www.wanlink.com
 *
 * @copyright   Copyright (c) 2018 by Neal Horman. All Rights Reserved.
 *
 * @license	Redistribution and use in source and binary forms, with or without
 * @license	modification, are permitted provided that the following conditions
 * @license	are met:
 * @license	1. Redistributions of source code must retain the above copyright
 * @license	   notice, this list of conditions and the following disclaimer.
 * @license	2. Redistributions in binary form must reproduce the above copyright
 * @license	   notice, this list of conditions and the following disclaimer in the
 * @license	   documentation and/or other materials provided with the distribution.
 * @license	3. Neither the name Neal Horman nor the names of any contributors
 * @license	   may be used to endorse or promote products derived from this software
 * @license	   without specific prior written permission.
 * @license
 * @license THIS SOFTWARE IS PROVIDED BY NEAL HORMAN AND ANY CONTRIBUTORS ``AS IS'' AND
 * @license ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * @license IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * @license ARE DISCLAIMED.  IN NO EVENT SHALL NEAL HORMAN OR ANY CONTRIBUTORS BE LIABLE
 * @license FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * @license DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * @license OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * @license HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * @license LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * @license OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * @license SUCH DAMAGE.
 *
 */

// use curl for post operations - https://stackoverflow.com/questions/5647461/how-do-i-send-a-post-request-with-php
class HTTPRequester
{
	public static function HTTPGet($url, array $params)
	{
		$query = http_build_query($params); 
		$ch = curl_init($url.'?'.$query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	public static function HTTPPost($url, array $params)
	{
		$query = http_build_query($params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		$response = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return array('response'=>$response, 'code'=>$code);
	}
}

class SlackMsg
{
	public function clear()
	{
		unset($this->mMsg);
	}

	public function textEscape($text)
	{
		return str_replace(array('&', '<', '>', '"'), array('&amp;', '&lt;', '&gt;', '\\"'), $text);
	}

	public function attach($info)
	{
		if(isset($info) && is_array($info))
		{
			if(!isset($this->mMsg))
				$this->mMsg = array('attachments' => array());
			$this->mMsg['attachments'][] = $info;
		}
	}

	public function plain($text)
	{
		$this->mMsg = array( 'mrkdwn'=> false, 'text'=> $this->textEscape($text));
	}

	public function integration($info)
	{
		$info['msg'] = $this->textEscape($info['msg']);
		$colors = array('green'=>'good', 'red'=>'danger', 'yellow'=>'warning', 'blue'=>'#439FE0');

		$at = array();

		if(isset($info['msgTitle']))
		{
			$at['title'] = $info['msgTitle'];
			if(isset($info['msgUrl']))
				$at['title_link'] = $info['msgUrl'];
		}

		if(isset($info['msg']))
		{
			$at['fallback'] = $info['msg'].' <'.$info['msgUrl'].'|'.$info['msgTitle'].'>';
			$at['pretext'] = $info['msg'].'.';
		}
		else
			$at['fallback'] = '<'.$info['msgUrl'].'|'.$info['msgTitle'].'>';

		if(isset($info['msgColor']) && !is_array($info['msgColor']))
		{
			if(array_key_exists($info['msgColor'], $colors))
				$at['color'] = $colors[$info['msgColor']];
			else
				$at['color'] = $info['msgColor'];
		}
		$this->attach($at);
	}

	public function commit($info)
	{
		$info['commitLog'] = $this->textEscape($info['commitLog']);
		$at = array(
			'fallback' => $info['commitTitle'].' - '.$info['commitLog'],
			'pretext' => $info['commitTitle'],
			'author_name' => $info['commitAuthor'],
			'text' => $info['commitLog'],
			'ts' => $info['commitDate']
			);
		if(isset($info['commitUrl']) && $info['commitUrl'] != "")
			$at['author_link'] = $info['commitUrl'];
		$this->attach($at);

		if(isset($info['commitHistory']))
		{
			$at = [ 'mrkdwn_in'=> ['text'], 'text'=> '```'.$this->textEscape($info['commitHistory']).'```'];
			$this->attach($at);
		}
	}

	public $mMsg;
}

class Slack
{
	public function __construct($url)
	{
		$this->url($url);
	}

	public function url($url)
	{
		if(isset($url) && $url != "")
			$this->mUrl = $url;
	}

	private function jsonSend($json, $channel)
	{
		$response = "";
		if(isset($json) && is_array($json))
		{
			if(isset($this->mUrl) && $this->mUrl != 'none' && $channel != 'none')
			{
				if(isset($channel) && $channel != "")
					$json['channel'] = $channel;
				$response = HTTPRequester::HTTPPost($this->mUrl, array('payload'=>json_encode($json)));
			}
		}

		return $response;
	}

	public function Send($msg, $channel)
	{
		return $this->jsonSend($msg->mMsg, $channel);
	}

	private $mUrl;
}

?>
