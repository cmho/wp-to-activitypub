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
	
	function rewrite_init() {
		// set up some nicer rewrite urls
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)/act/?$', 'index.php?rest_route=/ap/v1/actor&acct=$matches[1]', 'top');
		add_rewrite_rule('^inbox$', 'index.php?rest_route=/ap/v1/actor/inbox', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)$', 'index.php?author_name=$matches[1]', 'top');
		add_rewrite_rule('^\.well-known/([a-zA-Z0-9\-\?\=\@\%\.]+)$', 'index.php?rest_route=/ap/v1/$matches[1]', 'top');
	}
	add_action('init', 'rewrite_init');
	
	function post_types_init() {
		// add the domain_block model so we can block instances who don't deserve our content
		register_post_type('domain_block', array(
			'label' => 'Domain Block',
			'public' => true,
			'supports' => array('title')
		));
	}
	add_action('init', 'post_types_init');
	
	function get_webfinger() {
		// returns the 'webfinger' for an account, aka the hook for federation
		header('Content-type: application/json');
		$wfacct = $_GET['resource'];
		$matches;
		// make sure it's looking for a webfinger on this domain and that it's a valid account format
		preg_match('/acct:([a-zA-Z0-9]+)\@([a-z]+\.[a-z]+)/', $wfacct, $matches);
		if (count($matches) > 0 && parse_url(get_bloginfo('url'))['host'] == $matches[2]) {
			$user = get_user_by('slug', $matches[1]);
			// check if user exists
			if ($user) {
				// if so, return a webfinger with the user info for federation + following
				echo '{"subject": "acct:'.$user->user_login.'@'.parse_url(get_bloginfo('url'))['host'].'", "aliases": ["'.get_bloginfo('url').'/u/@'.$user->user_login.'", "'.get_bloginfo('url').'/wp-json/ap/v1/actor?acct='.$user->user_login.'"], "links": [{"rel": "https://webfinger.net/rel/profile-page", "type": "text/html", "href": "'.get_bloginfo('url').'/u/@'.$user->user_login.'"}, {"rel": "https://webfinger.net/rel/avatar/", "type": "image/jpeg", "href": "'.get_avatar_url($user->ID).'"}, {"rel": "self", "type": "application/activity+json", "href": "'.get_bloginfo('url').'/wp-json/ap/v1/actor?acct='.$user->user_login.'"}]}';
			}
		}
		die(1);
	}

	function get_webfinger_actor() {
		// returns the activitypub actor data
		header('Content-type: application/activity+json');
		$acct = $_GET['acct'];
		if ($acct) {
			$user = get_user_by('slug', $acct);
			// remove all the newlines characters from the public key and replace with \n for json
			$safe_key = preg_replace('/\n/', '\n', trim(get_user_meta($user->ID, 'pubkey', true)));
			// echo the composited actor json object
			echo '{"@context": ["https://www.w3.org/ns/activitystreams", "https://w3id.org/security/v1"], "id": "'.get_bloginfo('url').'/u/@'.$user->user_login.'", "type": "Person", "preferredUsername": "'.$user->user_login.'", "inbox": "'.get_bloginfo('url').'/inbox", "publicKey": { "id": "'.get_bloginfo('url').'/u/@'.$user->user_login.'#main-key", "owner": "'.get_bloginfo('url').'/u/@'.$user->user_login.'", "publicKeyPem": "'.$safe_key.'"}}';
		}

		die(1);
	}
	
	function get_inbox() {
		// we pretty much only use the inbox to get follow requests and accept them
		$h = getallheaders();
		// get request signature parts 4 security reasons
		$sig = explode(",", $h['Signature']);
		$keyId = $sig[0];
		$headers = $sig[1];
		$signature = base64_decode($sig[2]);
		
		// if it's a follow request, process it
		
		// check if it comes from a blocked domain; if so, deny it
		
		// otherwise, add user account and accept notice
		
		// if it's an unfollow request, process it
		
		// delete user from database
		
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
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_inbox'
		));
	}
	add_action('rest_api_init', 'rest_api_stuff');
	
	function add_user_keys($user_id) {
		$user = get_user_by('ID', $user_id);
		if (!get_user_meta($user->ID, 'pubkey')) {
			// if no keys set up for a profile on save, generate new ones
			$res = openssl_pkey_new(array(
				'digest_alg' => 'sha512',
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

	function send_messages($post_id) {
		// get all domain blocks and reduce down to an array of just titles (aka the actual domain)
		$domain_blocks = array_map(get_posts(array(
			'post_type' => 'domain_block',
			'posts_per_page' => -1
		)), function($x) {
			return $x->post_title;
		});
		
		// get a list of all subscribers who are not on a blocked domain and who have a valid activitypub webfinger username and who haven't been suspended
		$subscribers = get_users(array(
			'role' => 'subscriber',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'domain',
					'value' => $domain_blocks,
					'compare' => 'NOT IN'
				),
				array(
					'key' => 'ap_username',
					'compare' => 'EXISTS'
				),
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
		
		// replace newlines with \n because json doesn't like them
		$filtered_content = preg_replace('/\n/', '\n', get_the_content());
		// set up message object
		$message = '{"@context": "https://www.w3.org/ns/activitystreams","id": "'.get_permalink($post_id).'", "type": "Create", "actor": "'.get_bloginfo('url').'/u/@'.get_the_author_meta('user_login').'", "object": {"id": "'.get_permalink($post_id).'", "type": "Note", "published": "'.get_the_date('c').'", "attributedTo": "'.get_bloginfo('url').'/u/@'.get_the_author_meta('user_login').'", "content": "'.filtered_content().'", "to": "https://www.w3.org/ns/activitystreams#Public"}}';
		foreach ($subscribers as $subscriber) {
			// post message to subscriber domains
			$ch = curl_init();
			$fields = array(
				'body' => $message
			);
			curl_setopt($ch, CURLOPT_URL, get_user_meta($subscriber->ID, 'inbox', true));
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'body='.$message);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		
		return;
	}
	add_action('save_post', 'send_messages');
	
