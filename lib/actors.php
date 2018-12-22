<?php
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
			preg_match('/'.get_option('wp_activitypub_tags_prefix', 'tag_').'([a-zA-Z0-9\-]+)$/', $acct, $tagmatches);
			preg_match('/'.get_option('wp_activitypub_cats_prefix', 'cat_').'([a-zA-Z0-9\-]+)$/', $acct, $catmatches);
			preg_match('/'.get_option('wp_activitypub_global_name', 'all').'$/', $acct, $globalmatches);
			if (get_option('wp_activitypub_global') && count($globalmatches) > 0) {
				$safe_key = trim(get_option('wp_activitypub_global_pubkey'));
				$icon_meta = wp_get_attachment_metadata(get_option('site_icon'));
				$ret = array(
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1'
					],
					'id' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_global_name', 'all'),
					'name' => get_bloginfo('name'),
					'type' => 'Person',
					'preferredUsername' => get_option('wp_activitypub_global_name', 'all'),
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_global_name', 'all').'/outbox',
					'manuallyApprovesFollowers' => false,
					'icon' => array(
						'type' => 'Image',
						'mediaType' => $icon_meta['sizes']['site_icon-180']['mime-type'],
						'url' => get_site_icon_url()
					),
					'summary' => get_bloginfo('description'),
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_global_name', 'all').'#main-key',
						'owner' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_global_name', 'all'),
						'publicKeyPem' => $safe_key
					)
				);
				echo json_encode($ret);
				die(1);
			} elseif (get_option('wp_activitypub_tags') && count($tagmatches) > 0) {
				$tag = get_term_by('slug', $tagmatches[1], 'post_tag');
				//$safe_key = preg_replace('/\n/', '\n', trim(get_term_meta($tag->term_id, 'pubkey', true)));
				$safe_key = trim(get_term_meta($tag->term_id, 'pubkey', true));
				$icon_meta = wp_get_attachment_metadata(get_option('site_icon'));
				$ret = array(
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
						array(
							"manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
						)
					],
					'id' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_tags_prefix', 
					'tag_').$tagmatches[1],
					'type' => 'Person',
					'name' => get_bloginfo('name').": ".$tag->name,
					'preferredUsername' => get_option('wp_activitypub_tags_prefix', 'tag_').$tagmatches[1],
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_tags_prefix', 'tag_').$tagmatches[1].'/outbox',
					'manuallyApprovesFollowers' => false,
					'icon' => array(
						'type' => 'Image',
						'mediaType' => $icon_meta['sizes']['site_icon-180']['mime-type'],
						'url' => get_site_icon_url()
					),
					'summary' => $tag->description,
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_tags_prefix', 'tag_').$tagmatches[1].'#main-key',
						'owner' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_tags_prefix', 'tag_').$tagmatches[1],
						'publicKeyPem' => $safe_key
					)
				);
				echo json_encode($ret);
				die(1);
			} elseif (get_option('wp_activitypub_cats') && count($catmatches) > 0) {
				$cat = get_term_by('slug', $catmatches[1], 'category');
				//$safe_key = preg_replace('/\n/', '\n', trim(get_term_meta($cat->term_id, 'pubkey', true)));
				$safe_key = trim(get_term_meta($cat->term_id, 'pubkey', true));
				$icon_meta = wp_get_attachment_metadata(get_option('site_icon'));
				$ret = array(
					'@context' => [
						'https://www.w3.org/ns/activitystreams',
						'https://w3id.org/security/v1',
						array(
							"manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
						)
					],
					'id' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_cats_prefix', 'cat_').$catmatches[1],
					'type' => 'Person',
					'name' => get_bloginfo('name').": ".$cat->name,
					'preferredUsername' => get_option('wp_activitypub_cats_prefix', 'cat_').$catmatches[1],
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_cats_prefix', 'cat_').$catmatches[1].'/outbox',
					'manuallyApprovesFollowers' => false,
					'icon' => array(
						'type' => 'Image',
						'mediaType' => $icon_meta['sizes']['site_icon-180']['mime-type'],
						'url' => get_site_icon_url()
					),
					'summary' => $cat->description,
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_cats_prefix', 'cat_').$catmatches[1].'#main-key',
						'owner' => get_bloginfo('url').'/u/@'.get_option('wp_activitypub_cats_prefix', 'cat_').$catmatches[1],
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
						'https://w3id.org/security/v1'
					],
					'id' => get_bloginfo('url').'/u/@'.$user->user_login,
					'type' => 'Person',
					'name' => $user->display_name,
					'preferredUsername' => $user->user_login,
					'inbox' => get_bloginfo('url').'/inbox',
					'outbox' => get_bloginfo('url').'/u/@'.$user->user_login.'/outbox',
					'manuallyApprovesFollowers' => false,
					'summary' => get_user_meta($user->ID, 'description', true),
					'publicKey' => array(
						'id' => get_bloginfo('url').'/u/@'.$user->user_login.'#main-key',
						'owner' => get_bloginfo('url').'/u/@'.$user->user_login,
						'publicKeyPem' => $safe_key
					)
				);
				if (get_avatar_url($user->ID)) {
					preg_match('/\.([a-zA-Z]+)$/', get_avatar_url($user->ID), $ext);
					$ret['icon'] = array(
						'type' => 'Image',
						'mediaType' => wp_get_mime_types()[$ext[1]],
						'url' => get_avatar_url($user->ID)
					);
				} else {
					$ret['icon'] = array(
						'type' => 'Image',
						'mediaType' => 'image/jpeg',
						'url' => get_site_icon_url()
					);
				}
				echo json_encode($ret);
				die(1);
			}
		}
		// close request
	}
	add_action('template_redirect', 'redirect_to_actor');
	
	function get_followers() {
		global $user;
		header('Content-type: application/activity+json');
		$req = $_SERVER['REQUEST_URI'];
		$q = explode('&', $_SERVER['QUERY_STRING']);
		$query = array();
		foreach ($q as $z) {
			$x = explode('=', $z);
			$query[$x[0]] = $x[1];
		}
		preg_match('/^\/u\/@([a-zA-Z0-9\-\_]+)\/?/', $req, $matches);
		// parse it and see if it's an author url by our schema
		if (count($matches) > 0) {
			// get the user by slug
			$user_query = array(
				'post_type' => 'follow',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'following',
						'value' => $matches[1]
					)
				)
			);
			
			if (array_key_exists('page', $query)) {
				$user_query['number'] = 10;
				$user_query['paged'] = intval($query['page']);
			}
			
			$users = array_map(function($x) {
				return get_user($x->post_author);
			}, get_posts($user_query));
			
			$content = array(
				'@context' => 'https://www.w3.org/ns/activitystreams',
				'type' => 'OrderedCollection',
				'totalItems' => count($users),
				'id' => get_bloginfo('url').'/u/@'.$matches[1].'/followers'
			);
			if (array_key_exists('page', $query)) {
				$content['next'] = get_bloginfo('url').'/u/@'.$matches[1].'/followers?page='.(intval($query['page'])+1);
				$content['partOf'] = get_bloginfo('url').'/u/@'.$matches[1].'/followers';
				$content['type'] = 'OrderedCollectionPage';
				$content['orderedItems'] = array_map(function($u) {
					return get_user_meta($u->ID, 'ap_id', true);
				}, $users);
				if (intval($query['page']) != 1) {
					$content['prev'] = get_bloginfo('url').'/u/@'.$matches[1].'/followers?page='.(intval($query['page'])-1);
				}
			} else {
				$content['first'] = get_bloginfo('url').'/u/@'.$matches[1].'/followers?page=1';
			}
			echo json_encode($content);
			die(1);
		}
	}
  