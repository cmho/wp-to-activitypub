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

	function add_head_links() {
		if (is_author()) {
			// if this is an author page, display the activity+json version as an alt link
			$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
			?>
			<link rel="alternate" type="application/activity+json" href="<?= get_bloginfo('url'); ?>/u/@<?= $curauth; ?>" />
			<?php
		}
	}
	add_action('wp_head', 'add_head_links');