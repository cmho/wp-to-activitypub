<?php
  function get_webfinger() {
		// returns the 'webfinger' for an account, aka the hook for federation
		header('Content-type: application/json');
		$wfacct = $_GET['resource'];
		$matches;
		// make sure it's looking for a webfinger on this domain and that it's a valid account format
		preg_match('/acct:([a-zA-Z0-9\_]+)\@([a-z]+\.[a-z]+)/', $wfacct, $matches);
		if (count($matches) > 0 && parse_url(get_bloginfo('url'))['host'] == $matches[2]) {
			$user = get_user_by('slug', $matches[1]);
			// check if user exists
			if ($user) {
				// if so, return a webfinger with the user info for federation + following
				$ret = array(
					'subject' => 'acct:'.$user->user_login.'@'.parse_url(get_bloginfo('url'))['host'],
					'links' => array(
						array(
							'rel' => 'self',
							'type' => 'application/activity+json',
							'href' => get_bloginfo('url').'/u/@'.$user->user_login
						)
					)
				);
				echo json_encode($ret);
				die(1);
			}
			
			preg_match('/^cat_([a-zA-Z\-\_0-9]+)$/', $matches[1], $catmatch);
			preg_match('/^tag_([a-zA-Z\-\_0-9]+)$/', $matches[1], $tagmatch);
			if (count($catmatch) > 0 || $tagmatch > 0 || $matches[1] == 'all') {
				$ret = array(
					'subject' => 'acct:'.$matches[1].'@'.parse_url(get_bloginfo('url'))['host'],
					'links' => array(
						array(
							'rel' => 'self',
							'type' => 'application/activity+json',
							'href' => get_bloginfo('url').'/u/@'.$matches[1]
						)
					)
				);
				echo json_encode($ret);
			}
		}
		die(1);
	}