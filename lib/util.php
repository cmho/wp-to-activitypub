<?php
	  
	function get_actor($url) {
		// make request for actor object in json format, convert to php array
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Accept: application/activity+json'
		));
		$result = curl_exec($ch);
		curl_close($ch);
		return json_decode($result);
	}

	function sign_and_send($endpoint, $message, $key) {

	}
	