<?php
	
	/*
	Plugin Name: Carly's ActivityPub
	*/
	
	function activate_tasks() {
		global $wp_rewrite;
		// add options fields for plugin
		
		// set up actors for non-subscriber accounts
		$users = get_users(array(
			'role__not_in' => array('subscriber'),
			'meta_query' => array(
				array(
					'key' => 'domain',
					'compare' => 'NOT EXISTS'
				)
			)
		));
		
		// set up SSL keys for each existing non-subscriber user
		foreach ($users as $user) {
			add_user_keys($user->ID);
		}
		
		// flush permalinks
		$wp_rewrite->flush_rules();
		
		return;
	}
	
	function deactivate_tasks() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		return;
	}
	
	function uninstall_tasks() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		return;
	}
	
	function register_hooks() {
		// register actions to take on plugin activate/deactivate/uninstall
		register_activation_hook(__FILE__, 'activate_tasks');
		register_deactivation_hook(__FILE__, 'deactivate_tasks');
		register_uninstall_hook(__FILE__, 'uninstall_tasks');
	}
	add_action('plugins_loaded', 'register_hooks');
	
	function wp_activitypub_options_html($args) {
		$options = get_option('wp_activitypub_settings');
		if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
	        <?php
		        settings_fields('wp_activitypub');
		        do_settings_sections('wp_activitypub');
		        submit_button('Save Settings');
		      ?>
        </form>
    </div>
    <?php
	}
	
	function wp_activitypub_global_cb($args) {
		$options = get_option('wp_activitypub_global');
		?>
		<label for="wp_activitypub_global"><input type="checkbox" name="wp_activitypub_global" value="true"<?= $options ? ' checked="checked"' : ''; ?> /> Enable @all user?</label>
		<?php
	}
	
	function wp_activitypub_tags_cb($args) {
		$options = get_option('wp_activitypub_tags');
		?>
		<label for="wp_activitypub_tags"><input type="checkbox" name="wp_activitypub_tags" value="true"<?= $options ? ' checked="checked"' : ''; ?> /> Enable tag users?</label>
		<p class="description">This will enable federation users to follow tag-[tagname]@yourdomain.com.</p>
		<?php
	}
	
	function wp_activitypub_cats_cb($args) {
		$options = get_option('wp_activitypub_cats');
		?>
		<label for="wp_activitypub_cats"><input type="checkbox" name="wp_activitypub_cats" value="true"<?= $options ? ' checked="checked"' : ''; ?> /> Enable category users?</label>
		<p class="description">This will enable federation users to follow cat-[categoryname]@yourdomain.com.</p>
		<?php
	}
	
	function wp_activitypub_posters_cb($args) {
		return;
	}
	
	function ap_settings_init() {
		register_setting('wp_activitypub', 'wp_activitypub');
		add_settings_section(
			'wp_activitypub_posters',
			__('Global, Tag and Category Users', 'wp_activitypub'),
			'wp_activitypub_posters_cb',
			'wp_activitypub'
		);
		
		register_setting('wp_activitypub', 'wp_activitypub_global', array(
			'type' => 'boolean',
			'default' => false
		));
		register_setting('wp_activitypub', 'wp_activitypub_global_pubkey', array(
			'type' => 'string'
		));
		register_setting('wp_activitypub', 'wp_activitypub_global_privkey', array(
			'type' => 'string'
		));
		add_settings_field(
			'wp_activitypub_global',
			__('Global User?', 'wp_activitypub'),
			'wp_activitypub_global_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_global',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
		
		register_setting('wp_activitypub', 'wp_activitypub_tags', array(
			'type' => 'boolean',
			'default' => false
		));
		add_settings_field(
			'wp_activitypub_tags',
			__('Tag Users?', 'wp_activitypub'),
			'wp_activitypub_tags_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_tags',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
		
		register_setting('wp_activitypub', 'wp_activitypub_cats', array(
			'type' => 'boolean',
			'default' => false
		));
		add_settings_field(
			'wp_activitypub_cats',
			__('Category Users?', 'wp_activitypub'),
			'wp_activitypub_cats_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_cats',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
	}
	add_action('admin_init', 'ap_settings_init');
	
	function ap_options_page() {
		add_submenu_page(
			'options-general.php',
			'WP ActivityPub Settings',
			'WP ActivityPub',
			'administrator',
			'wp_activitypub',
			'wp_activitypub_options_html'
		);
	}
	add_action('admin_menu', 'ap_options_page');
	
	function rewrite_init() {
		// set up some nicer rewrite urls
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)/outbox$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)/outbox\??([a-zA-Z0-9_\&\=]+)?$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]&matches[2]', 'top');
		add_rewrite_rule('^inbox/?$', 'index.php?rest_route=/ap/v1/inbox', 'top');
		add_rewrite_rule('^u/@all$', 'index.php', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)/followers$', 'index.php?rest_route=/ap/v1/followers&acct=$matches[1]', 'top');
		add_rewrite_rule('^u/@tag_([a-zA-Z0-9\-]+)$', 'index.php?tag=$matches[1]', 'top');
		add_rewrite_rule('^u/@cat_([a-zA-Z0-9\-]+)$', 'index.php?category_name=$matches[1]', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)$', 'index.php?author_name=$matches[1]', 'top');
		add_rewrite_rule('^\.well-known/([a-zA-Z0-9\-\?\=\@\%\.]+)$', 'index.php?rest_route=/ap/v1/$matches[1]', 'top');
	}
	add_action('init', 'rewrite_init');
	
	function post_types_init() {
		// add the domain_block model so we can block instances who don't deserve our beautiful beautiful content
		register_post_type('domain_block', array(
			'label' => 'Domain Block',
			'public' => true,
			'supports' => array('title')
		));

		register_post_type('user_block', array(
			'label' => 'User Block',
			'public' => true,
			'supports' => array('title')
		));
		
		register_post_type('inboxitem', array(
			'label' => 'Inbox',
			'public' => true,
			'supports' => array('title', 'editor', 'page-attributes')
		));
		
		register_post_type('outboxitem', array(
			'label' => 'Outbox',
			'public' => true,
			'supports' => array('title', 'editor', 'page-attributes')
		));
	}
	add_action('init', 'post_types_init');
	
	function redirect_to_actor() {
		// get the url that was requested
		$req = $_SERVER['REQUEST_URI'];
		// parse it and see if it's an author url by our schema
		preg_match('/\/u\/@([a-zA-Z0-9\-\_]+)\/?$/', $req, $matches1);
		preg_match('/\/author\/([a-zA-Z0-9\-]+)\/?$/', $req, $matches2);
		if ((count($matches1) > 0 || count($matches2) > 0) && in_array('application/activity+json', explode(",", getallheaders()['Accept']))) {
			// if it's an author and the request asked for application/activity+json format, return the actor
			header('Content-type: application/activity+json');
			if (count($matches1) > 0) {
				$acct = $matches1[1];
			} elseif (count($matches2) > 0) {
				$acct = $matches2[1];
			}
			// check if this is tag user, cat user, or a global user
			preg_match('/tag_([a-zA-Z0-9\-]+)$/', $acct, $tagmatches);
			preg_match('/cat_([a-zA-Z0-9\-]+)$/', $acct, $catmatches);
			preg_match('/all$/', $acct, $globalmatches);
			if (count($globalmatches) > 0) {
				//$safe_key = preg_replace('/\n/', '\n', trim(get_option('wp_activitypub_global_pubkey')));
				$safe_key = trim(get_option('wp_activitypub_global_pubkey'));
				$ret = array(
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
						array(
							"manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
						)
					],
					'id' => get_bloginfo('url').'/u/@all',
					'type' => 'Person',
					'preferredUsername' => 'all',
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@all/outbox',
					'manuallyApprovesFollowers' => false,
					'icon' => array(
						'type' => 'Image',
						'mediaType' => 'image/jpeg',
						'url' => get_site_icon_url()
					),
					'summary' => get_bloginfo('description'),
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@all#main-key',
						'owner' => get_bloginfo('url').'/u/@all',
						'publicKeyPem' => $safe_key
					)
				);
				echo json_encode($ret);
				die(1);
			} elseif (count($tagmatches) > 0) {
				$tag = get_term_by('slug', $tagmatches[1], 'post_tag');
				//$safe_key = preg_replace('/\n/', '\n', trim(get_term_meta($tag->term_id, 'pubkey', true)));
				$safe_key = trim(get_term_meta($tag->term_id, 'pubkey', true));
				$ret = array(
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
						array(
							"manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
						)
					],
					'id' => get_bloginfo('url').'/u/@tag_'.$tagmatches[1],
					'type' => 'Person',
					'preferredUsername' => 'tag_'.$tagmatches[1],
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@tag_'.$tagmatches[1].'/outbox',
					'manuallyApprovesFollowers' => false,
					'icon' => array(
						'type' => 'Image',
						'mediaType' => 'image/jpeg',
						'url' => get_site_icon_url()
					),
					'summary' => get_bloginfo('description'),
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@tag_'.$tagmatches[1].'#main-key',
						'owner' => get_bloginfo('url').'/u/@tag_'.$tagmatches[1],
						'publicKeyPem' => $safe_key
					)
				);
				echo json_encode($ret);
				die(1);
			} elseif (count($catmatches) > 0) {
				$cat = get_term_by('slug', $catmatches[1], 'category');
				//$safe_key = preg_replace('/\n/', '\n', trim(get_term_meta($cat->term_id, 'pubkey', true)));
				$safe_key = trim(get_term_meta($cat->term_id, 'pubkey', true));
				$ret = array(
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
						array(
							"manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
						)
					],
					'id' => get_bloginfo('url').'/u/@cat_'.$catmatches[1],
					'type' => 'Person',
					'preferredUsername' => 'cat_'.$catmatches[1],
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@cat_'.$catmatches[1].'/outbox',
					'manuallyApprovesFollowers' => false,
					'icon' => array(
						'type' => 'Image',
						'mediaType' => 'image/jpeg',
						'url' => get_site_icon_url()
					),
					'summary' => get_bloginfo('description'),
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@cat_'.$catmatches[1].'#main-key',
						'owner' => get_bloginfo('url').'/u/@cat_'.$catmatches[1],
						'publicKeyPem' => $safe_key
					)
				);
				echo json_encode($ret);
				die(1);
			} else {
				// get the username and then retrieve the correct user from the database
				$user = get_user_by('slug', $acct);
				// remove all the newlines characters from the public key and replace with \n for json
				//$safe_key = preg_replace('/\n/', '\n', trim(get_user_meta($user->ID, 'pubkey', true)));
				$safe_key = trim(get_user_meta($user->ID, 'pubkey', true));
				// echo the composited actor json object
				$ret = array(
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
						array(
							"manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
						)
					],
					'id' => get_bloginfo('url').'/u/@'.$user->user_login,
					'type' => 'Person',
					'preferredUsername' => $user->user_login,
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@'.$user->user_login.'/outbox',
					'manuallyApprovesFollowers' => false,
					'icon' => array(
						'type' => 'Image',
						'mediaType' => 'image/jpeg',
						'url' => get_site_icon_url()
					),
					'summary' => get_bloginfo('description'),
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@'.$user->user_login.'#main-key',
						'owner' => get_bloginfo('url').'/u/@'.$user->user_login,
						'publicKeyPem' => $safe_key
					)
				);
				echo json_encode($ret);
				die(1);
			}
		}
		// close request
	}
	add_action('wp_headers', 'redirect_to_actor');
	
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
	
	function get_followers() {
		$req = $_SERVER['REQUEST_URI'];
		$matches;
		preg_match('/^\/u\/@([a-zA-Z0-9\-\_]+)\/?/', $req, $matches);
		// parse it and see if it's an author url by our schema
		if (count($matches) > 0) {
			// get the user by slug
			$user = get_user_by('slug', $matches[1]);
			$users = get_users(array(
				'role__in' => array('subscriber'),
				'meta_query' => array(
					array(
						'key' => 'domain',
						'compare' => 'EXISTS'
					)
				)
			));
			print_r($user);
			print_r($users);
			$users = array_filter($users, function($x) {
				return in_array($user->user_login, get_user_meta($user->ID, 'following'));
			});
			print_r($users);
			$content = array(
				'@context' => 'https://www.w3.org/ns/activitystreams',
				'type' => 'OrderedCollection',
				'totalItems' => count($users),
				'id' => get_bloginfo('url').'/u/@'.$matches[1].'/followers',
				'first' => get_bloginfo('url').'/u/@'.$matches[1].'/followers?page=1'
			);
			header('Content-type: application/activity+json');
			echo json_encode($content);
			die(1);
		}
	}
	
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
	
	function get_inbox() {
		// we pretty much only use the inbox to get follow requests and accept them
		$h = getallheaders();
		// get request signature parts 4 security reasons
		$sig = explode(",", $h['Signature']);
		$keyId = $sig[0];
		$headers = $sig[1];
		$signature = base64_decode($sig[2]);
		
		// do some security thing
		
		// get actor contents
		$entityBody = json_decode(file_get_contents('php://input'));
		wp_insert_post(array(
			'post_content' => file_get_contents('php://input'),
			'post_status' => 'publish',
			'post_type' => 'inboxitem'
		));
		$type = $entityBody->type;
		$a = $entityBody->actor;
		if ($a) {
			// grab the actor data from the webfinger sent to us
			$act = get_actor($a);
			//if (openssl_verify($headers, $signature, $act->publicKey->publicKeyPem, 'sha256')) {
				$inbox  = $act->endpoints->sharedInbox;
				// cobble together the webfinger url from the preferred username and the host name
				$username = $act->preferredUsername.'@'.parse_url($entityBody->id)['host'];
				// get the username we're trying to follow on this site
				$followobj = $entityBody->object;
				
				$domain = parse_url($entityBody->id)['host'];
				
				if (is_string($followobj)) {
					// check if there's an account by that name
					$following = str_replace('https://'.parse_url($followobj)['host']."/u/@", "", $followobj);
					header('Content-type: application/activity+json');
					header("HTTP/1.1 200 OK");
					
					if ($type == 'Follow') {
						// if it's a follow request, process it
						// check if it comes from a blocked domain; if so, deny it
						$bd = get_posts(array(
							'post_type' => 'domain_block',
							'posts_per_page' => -1,
							'post_name' => str_replace('.', '-', $domain)
						));
						if (count($bd) > 0) {
							$p = wp_insert_post(array(
								'post_type' => 'outboxitem',
								'post_status' => 'publish',
								'post_content' => 'pending'
							));
							$reject = array(
								'@context' => 'https://www.w3.org/ns/activitystreams',
								'id' => get_bloginfo('url').'/u/@'.$following,
								'type' => 'Reject',
								'actor' => get_bloginfo('url').'/u/@'.$following,
								'object' => $entityBody,
								'signature' => array(
									'type' => 'RsaSignature2017',
									'creator' => get_bloginfo('url').'/u/@'.$following.'#main-key',
									'date' => '',
									'signatureValue' => ''
								)
							);
							$permalink = get_the_permalink($p);
							$baseurl = get_bloginfo('url');
							$body = json_encode($entityBody);
							$ch = curl_init();
							$signature = "";
							$key = trim(get_user_meta($follow_user->ID, 'privkey', true));
							$keyval = <<< EOT
$key
EOT;
							$pkey = openssl_get_privatekey($keyval);
							$now = new \DateTime('now', new \DateTimeZone('GMT'));
							$date  = $now->format('D, d M Y H:i:s T');
							$str = "(request-target): post /inbox\nhost: ".$domain."\ndate: ".$date;
							openssl_sign($str, $signature, $pkey, OPENSSL_ALGO_SHA256);
							$sig_encode = base64_encode($signature);
							$sig_str = "keyId=\"".get_bloginfo('url')."/u/@".$following."#main-key\",headers=\"(request-target) host date\",signature=\"" .$sig_encode. "\"";
							$reject['signature']['signatureValue'] = $sig_encode;
							$reject['signature']['date'] = $date;
							$json_reject = json_encode($reject);
							curl_setopt_array($curl, array(
							  CURLOPT_URL => $inbox,
							  CURLOPT_RETURNTRANSFER => true,
							  CURLOPT_CUSTOMREQUEST => "POST",
							  CURLOPT_POST => 1,
							  CURLOPT_POSTFIELDS => 'body='.$json_reject,
							  CURLOPT_HTTPHEADER => array(
									'Signature: '.$sig_str,
							    'Date: '.$date,
							    'Host: '.$domain,
									'Content-type: application/ld+json; profile="https://www.w3.org/ns/activitystreams',
									'Content-Length: '.strlen($json_reject)
								),
							));
							$result = curl_exec($ch);
							curl_close($ch);
							echo $reject;
							wp_update_post(array(
								'ID' => $p,
								'post_content' => $reject
							));
							die(1);
							return;
						}
						$follow_user = get_user_by('slug', $following);
						// otherwise, add user account and return accept notice
						$user_check = get_user_by('slug', $username);
						if (!$user_check) {
							// create new user account if it doesn't exist
							$u = wp_create_user($username, serialize(bin2hex(random_bytes(16))));
							// initialize subscription list w/ requested account
							add_user_meta($u, 'following', array($following));
						} else {
							// if the account alreaddy  exists, add the account to the subscription list
							update_user_meta($u, 'following', array_push(get_user_meta($u, 'following'), $following));
						}
						// store inbox url, preferred username, domain, public key, actor for reference purposes
						update_user_meta($u, 'inbox', $inbox);
						update_user_meta($u, 'preferred_username', $act->preferredUsername);
						update_user_meta($u, 'domain', $domain);
						update_user_meta($u, 'pubkey', $act->publicKey->publicKeyPem);
						update_user_meta($u, 'actor_info', json_encode($act));
						
						// create acceptance object
						$p = wp_insert_post(array(
							'post_type' => 'outboxitem',
							'post_content' => 'pending',
							'post_status' => 'publish'
						));
						$permalink = get_the_permalink($p);
						$baseurl = get_bloginfo('url');
						$body = json_encode($entityBody);
						$ch = curl_init();
						$signature = "";
						$key = trim(get_user_meta($follow_user->ID, 'privkey', true));
						$keyval = <<< EOT
$key
EOT;
						$pkey = openssl_get_privatekey($keyval);
						$accept = array(
							'@context' => 'https://www.w3.org/ns/activitystreams',
							'id' => get_bloginfo('url').'/u/@'.$following,
							'type' => 'Accept',
							'actor' => get_bloginfo('url').'/u/@'.$following,
							'object' => $entityBody,
							'signature' => array(
								'type' => 'RsaSignature2017',
								'creator' => get_bloginfo('url').'/u/@'.$following.'#main-key',
								'date' => '',
								'signatureValue' => ''
							)
						);
						$now = new \DateTime('now', new \DateTimeZone('GMT'));
						$date  = $now->format('D, d M Y H:i:s T');
						$str = "(request-target): post /inbox\nhost: ".$domain."\ndate: ".$date;
						openssl_sign($str, $signature, $pkey, OPENSSL_ALGO_SHA256);
						$sig_encode = base64_encode($signature);
						$sig_str = "keyId=\"".get_bloginfo('url')."/u/@".$following."#main-key\",headers=\"(request-target) host date\",signature=\"" .$sig_encode. "\"";
						$accept['signature']['signatureValue'] = $sig_encode;
						$accept['signature']['date'] = $date;
						$json_accept = json_encode($accept);
						curl_setopt_array($ch, array(
						  CURLOPT_URL => $inbox,
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_CUSTOMREQUEST => "POST",
						  CURLOPT_POST => 1,
						  CURLOPT_POSTFIELDS => 'body='.$json_accept,
						  CURLOPT_HTTPHEADER => array(
								'Signature: '.$sig_str,
						    'Date: '.$date,
						    'Host: '.$domain,
								'Content-Type: application/ld+json; profile="https://www.w3.org/ns/activitystreams',
								'Content-Length: '.strlen($json_accept)
							),
						));
						$result = curl_exec($ch);
						curl_close($ch);
						update_user_meta($u, 'follow_result', var_dump($result));
						echo $accept;
						wp_update_post(array(
							'ID' => $p,
							'post_content' => $json_accept."\n\n".$sig_str
						));
					} elseif ($type == 'Create') {
						// handle replies here
					} elseif ($type == 'Undo') {
						// if it's an unfollow request, process it
						$user = get_user_by('slug', $username."@".$domain);
						wp_delete_user($user->ID);
						// delete user from database
					} 
				}
			//} else {
			//	header("HTTP/1.1 401 Request signature could not be verified");
			//}
		} else {
			echo "No user by that name.";
		}
		
		die(1);
	}
	
	function get_outbox() {
		header('Content-type: application/activity+json');
		header("HTTP/1.1 200 OK");
		$req = $_SERVER['REQUEST_URI'];
		// parse it and see if it's an author url by our schema
		$matches;
		preg_match('/^\/u\/@([a-zA-Z0-9\-\_]+)\/?/', $req, $matches);
		if ($_GET['page'] && $_GET['page'] == true) {
			$params = array(
				'posts_per_page' => 25
			);
			if ($_GET['offset']) {
				$params['offset'] = $_GET['offset'];
			}
			print_r($matches);
			if (count($matches) > 0 && $matches[1]) {
				$user = $matches[1];
				preg_match('/^tag_([a-zA-Z_\-0-9]+)/', $user, $tagmatch);
				preg_match('/^cat_([a-zA-Z_\-0-9]+)/', $user, $catmatch);
				preg_match('/^all/', $user, $allmatch);
				if (count($tagmatch) > 0) {
					$params['tax_query'] = array(
						array(
							'taxonomy' => 'post_tag',
							'term' => $tagmatch[1],
							'field' => 'slug'
						)
					);
				} elseif (count($catmatch) > 0) {
					$params['tax_query'] = array(
						array(
							'taxonomy' => 'category',
							'term' => $catmatch[1],
							'field' => 'slug'
						)
					);
				} elseif (count($allmatch) == 0) {
					$params['author'] = get_user_by('slug', get_query_var('acct'))->ID;
				}
				
				$posts = get_posts($params);
				$outbox = array(
					'@context' => array(
						"https://www.w3.org/ns/activitystreams",
		        "https://w3id.org/security/v1"
					),
					'id' => '',
					'type' => 'OrderedCollectionPage',
					'next' => get_bloginfo('url').'/u/@'.get_query_var('acct').'/outbox?page=true&offset='.(get_query_var('offset') ? intval(get_query_var('offset'))+25 : 25),
					'partOf' => ''
				);
				
				if (get_query_var('offset')) {
					$amt = intval(get_query_var('offset')) - 25;
					if ($amt < 0) {
						$amt = 0;
					}
					$outbox['prev'] = get_bloginfo('url').'/u/@'.$user.'/outbox?page=true&offset='.$amt;
				}
				
				$orderedItems = array();
				foreach ($posts as $post) {
					setup_postdata($post);
					$media = get_attached_media('image', $post->ID);
					$attachments = array();
					foreach ($media as $m) {
						array_push($attachments, array(
							'type' => 'Image',
							'content' => wp_get_attachment_caption($m->ID),
							'url' => wp_get_attachment_url($m->ID)
						));
					}
					array_push($orderedItems, array(
						'id' => get_the_permalink(),
						'type' => 'Create',
						'actor' => get_bloginfo('url').'/u/@'.$user,
						'published' => get_the_date('r', $post),
						'to' => array(
							"https://www.w3.org/ns/activitystreams#Public"
						),
						'cc' => array(
							get_bloginfo('url').'/u/@'.$user.'/followers'
						),
						'object' => array(
							'id' => get_the_permalink($post),
							'type' => 'Note',
							'summary' => get_the_excerpt($post),
							'inReplyTo' => null,
							'published' => get_the_date('r', $post),
							'url' => get_the_permalink($post),
							'attributedTo' => get_bloginfo('url').'/u/@'.$user,
							'to' => array(
								"https://www.w3.org/ns/activitystreams#Public"
							),
							'cc' => array(
								get_bloginfo('url').'/u/@'.$user.'/followers'
							),
							'sensitive' => get_post_meta($post->ID, 'sensitive', false),
							'content' => get_the_content($post),
							'attachment' => $attachments,
							'tag' => array()
						)
					));
				}
				wp_reset_postdata();
				$outbox['orderedItems'] = $orderedItems;
				echo json_encode($outbox);
			}
		}
		die(1);
	}

	function rest_api_stuff() {
		// set up some API routes to return webfinger + actor data
		register_rest_route('ap/v1', '/webfinger', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_webfinger'
		));

		register_rest_route('ap/v1', '/actor', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_webfinger_actor'
		));
		
		register_rest_route('ap/v1', '/inbox', array(
			'methods' => 'POST',
			'callback' => 'get_inbox'
		));
		
		register_rest_route('ap/v1', '/outbox', array(
			'methods' => 'POST',
			'callback' => 'get_outbox'
		));
		
		register_rest_route('ap/v1', '/followers', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_followers'
		));
	}
	add_action('rest_api_init', 'rest_api_stuff');
	
	function add_term_keys($term_id, $tt_id, $taxonomy) {
		$privKey;
		$res = openssl_pkey_new(array(
			'digest_alg' => 'sha256WithRsaEncryption',
			'private_key_bits' => 4096,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
		));
		openssl_pkey_export($res, $privKey);
		$pubKey = openssl_pkey_get_details($res);
		add_term_meta($term_id, 'pubkey', $pubKey['key']);
		add_term_meta($term_id, 'privkey', $privKey);
	}
	add_action('create_term', 'add_term_keys');
	
	function add_global_pkeys() {
		if (get_option('wp_activitypub_global') && get_option('wp_activitypub_global_pubkey') == '') {
			$privKey;
			$res = openssl_pkey_new(array(
				'digest_alg' => 'sha256WithRsaEncryption',
				'private_key_bits' => 4096,
				'private_key_type' => OPENSSL_KEYTYPE_RSA
			));
			openssl_pkey_export($res, $privKey);
			$pubKey = openssl_pkey_get_details($res);
			update_option('wp_activitypub_global_pubkey', $pubKey['key']);
			update_option('wp_activitypub_global_privkey', $privKey);
		}
	}
	add_action('update_option_wp_activitypub_global', 'add_global_pkeys');
	
	function add_tag_pkeys() {
		if (get_option('wp_activitypub_tags')) {
			$tags = get_terms(array(
				'taxonomy' => 'post_tag',
				'hide_empty' => false,
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => 'pubkey',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'pubkey',
						'value' => ''
	 				)
				)
			));
			foreach ($tags as $tag) {
				add_term_keys($tag->term_id, '', 'tag');
			}
		}
	}
	add_action('update_option_wp_activitypub_tags', 'add_tag_pkeys');
	
	function add_cat_pkeys() {
		if (get_option('wp_activitypub_cats')) {
			$cats = get_terms(array(
				'taxonomy' => 'category',
				'hide_empty' => false,
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => 'pubkey',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'pubkey',
						'value' => ''
	 				)
				)
			));
			
			foreach ($cats as $cat) {
				add_term_keys($cat->term_id, '', 'category');
			}
		}
	}
	add_action('update_option_wp_activitypub_cats', 'add_cat_pkeys');
	
	function add_user_keys($user_id) {
		$user = get_user_by('ID', $user_id);
		if (!in_array('subscriber', $user->roles)) {
			if (!get_user_meta($user->ID, 'pubkey')) {
				// if no keys set up for a profile on save, generate new ones
				$res = openssl_pkey_new(array(
					'digest_alg' => 'sha256WithRsaEncryption',
					'private_key_bits' => 4096,
					'private_key_type' => OPENSSL_KEYTYPE_RSA
				));
				openssl_pkey_export($res, $privKey);
				$pubKey = openssl_pkey_get_details($res);
				// save keys to user meta fields
				update_user_meta($user->ID, 'pubkey', $pubKey['key']);
				update_user_meta($user->ID, 'privkey', $privKey);
			}
		}
	}
	
	function check_suspension($user_id) {
		// on profile save, update the suspended field
		$user = get_user_by('ID', $user_id);
		update_user_meta($user_id, 'suspended', $_POST['suspend']);
	}
	// run this on profile save
	add_action('profile_update', 'add_user_keys');
	add_action('profile_update', 'check_suspension');
	
	function add_suspend_checkbox($profileuser) {
		// add account suspension checkbox on user profile page to prevent federating to a specific user
		?>
			<h3>Account Suspension</h3>
			<table class="form-table">
				<tbody>
					<tr>
						<th>Suspend User?</th>
						<td>
							<input type="checkbox" name="suspend" id="suspend"<?= get_user_meta($profileuser->ID, 'suspended', true) ? ' checked="checked"' : ''; ?> value="true" />
						</td>
					</tr>
				</tbody>
			</table>
		<?php
	}
	add_action( 'show_user_profile', 'add_suspend_checkbox', 10, 1 );
	add_action( 'edit_user_profile', 'add_suspend_checkbox', 10, 1 );
	
	function add_info_fields($profileuser) {
		?>
		<h3>Instance Information</h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th>Domain</th>
					<td>
						<input type="text" name="userdomain" id="userdomain" value="<?= get_user_meta($profileuser->ID, 'domain', true); ?>" />
					</td>
				</tr>
				<tr>
					<th>Preferred Username</th>
					<td>
						<input type="text" name="prefuser" id="prefuser" value="<?= get_user_meta($profileuser->ID, 'preferred_username', true); ?>" />
					</td>
				</tr>
				<tr>
					<th>Inbox</th>
					<td>
						<input type="text" name="inboxurl" id="inboxurl" value="<?= get_user_meta($profileuser->ID, 'inbox', true); ?>" />
					</td>
				</tr>
				<tr>
					<th>Public Key</th>
					<td>
						<textarea name="publickey" id="publickey"><?= get_user_meta($profileuser->ID, 'pubkey', true); ?></textarea>
					</td>
				</tr>
				<tr>
					<th>Actor Info</th>
					<td>
						<textarea name="followers" id="followers"><?= get_user_meta($profileuser->ID, 'actor_info', true); ?></textarea>
					</td>
				</tr>
				<tr>
					<th>Follow Result</th>
					<td>
						<textarea name="followers" id="followers"><?= get_user_meta($profileuser->ID, 'follow_result', true); ?></textarea>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	add_action( 'show_user_profile', 'add_info_fields', 10, 1 );
	add_action( 'edit_user_profile', 'add_info_fields', 10, 1 );

	function send_messages($post_id) {
		global $post;
		$post = get_post($post_id);
		// only do the stuff if this is a regular blog post
		if (get_post_type() == 'post') {
			// get all domain blocks and reduce down to an array of just titles (aka the actual domain)
			$domains = get_posts(array(
				'post_type' => 'domain_block',
				'posts_per_page' => -1
			));
			
			if (count($domains) > 0) {
				$domain_blocks = array_map($domains, function($x) {
					return $x->post_title;
				});
			}
			
			// get a list of all subscribers who are not on a blocked domain and who have a valid activitypub webfinger username and who haven't been suspended
			$subscribers = get_users(array(
				'role' => 'subscriber',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => 'domain',
						'compare' => 'EXISTS'
					),
					array(
						'relation' => 'OR',
						array(
							'key' => 'suspended',
							'value' => true,
							'compare' => '!='
						),
						array(
							'key' => 'suspended',
							'compare' => 'NOT EXISTS'
						)
					)
				)
			));
			
			if (count($domains) > 0) {
				array_push($subscribers['meta_query'], array(
					'key' => 'domain',
					'value' => $domain_blocks,
					'compare' => 'NOT IN'
				));
			}
			
			// replace newlines with \n because json doesn't like them
			$filtered_content = preg_replace('/\n/', '\n', get_the_content($post_id));
			// set up message object
			$message = '{"@context": "https://www.w3.org/ns/activitystreams","id": "'.get_permalink($post_id).'", "type": "Create", "actor": "'.get_bloginfo('url').'/u/@'.get_the_author_meta('user_login').'", "object": {"id": "'.get_permalink($post_id).'", "type": "Note", "published": "'.get_the_date('D, d M Y H:i:s \G\M\T').'", "attributedTo": "'.get_bloginfo('url').'/u/@'.get_the_author_meta('user_login').'", "content": "'.filtered_content().'", "to": "https://www.w3.org/ns/activitystreams#Public"}}';
			foreach ($subscribers as $subscriber) {
				// post message to subscriber domains
				$ch = curl_init();
				$fields = array(
					'body' => $message
				);
				curl_setopt($ch, CURLOPT_URL, get_user_meta($subscriber->ID, 'inbox', true));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_POST, count($fields));
				curl_setopt($ch, CURLOPT_POSTFIELDS, 'body='.$message);
				$result = curl_exec($ch);
				curl_close($ch);
			}
		}
		
		return;
	}
	add_action('save_post', 'send_messages');
