<?php
  	function get_inbox() {
		global $h;
		// we pretty much only use the inbox to get follow requests and accept them
		$h = getallheaders();
		// get request signature parts 4 security reasons
		$sig = explode(",", $h['Signature']);
		$keyId = explode("=", $sig[0])[1];
		$headers = explode(" ", explode("=", $sig[1])[1]);

		// get actor contents
		$entityBody = json_decode(file_get_contents('php://input'));

		$type = $entityBody->type;
		$a = $entityBody->actor;
		/*
		$zip = array_map(function($x) {
			return explode("=", $x);
		}, $sig);

		$headerpairs = array_combine(array_map(function($y) {
			return $y[0];
		}, $zip), array_map(function($z) {
			return str_replace('"', '', $z[1]);
		}, $zip));

		// create signature comparison string
		$strcontent = array_map(function ($c) {
			global $h;
			if ($c == "(request-target)") {
				return "(request-target): post /inbox";
			} elseif ($c == "content-type") {
				return "content-type: ".$h['Content-Type'];
			}
			return $c.": ".$h[ucfirst($c)];
		}, explode(" ", $headerpairs['headers']));
		$data = join("\n", $strcontent);

		// grab the actor data from the webfinger sent to us
		$act = get_actor($a);
		$k = $act->publicKey->publicKeyPem;
		$keyval = <<< EOT
$k
EOT;
		$pk = openssl_get_publickey($keyval);
		*/
		$p = wp_insert_post(array(
			'post_type' => 'inboxitem',
			'post_content' => json_encode($entityBody)."\n\n".$data."\n\n"."\n\n".base64_decode($headerpairs['signature'])."\n\n".$keyval
		));
		/*
		// verify http signature to make sure it's a real request from a real place; if not, send a 401 and kill the process
		$v = openssl_verify($data, base64_decode($entityBody->signature->signatureValue), $pk, OPENSSL_ALGO_SHA256);
		if ($v != 1) {
			if ($v == -1) {
				wp_update_post($p, array(
					'post_content' => "error state"
				));
			}
			header('HTTP/1.1 401 Unauthorized');
			die(1);
		}

		wp_update_post($p, array(
			'post_content' => "passed verification"
		));
		*/

		// signature good! let's go!!!
		if ($a) {
			$inbox  = $act->endpoints->sharedInbox;
			// cobble together the webfinger url from the preferred username and the host name
			$username = $act->preferredUsername.'@'.parse_url($entityBody->id)['host'];
			// get the username we're trying to follow on this site
			$followobj = $entityBody->object;
			
			$domain = parse_url($entityBody->id)['host'];
			
			if (is_string($followobj)) {
				// check if there's an account by that name
				$following = str_replace('https://'.parse_url($followobj)['host']."/u/@", "", $followobj);
				$follow_user = get_user_by('slug', $following);
				header('Content-type: application/activity+json');
				$key;
				if ($follow_user) {
					echo "1";
					$key = trim(get_user_meta($follow_user->ID, 'privkey', true));
				} elseif (count($tagmatches) > 0) {
					$key = trim(get_term_meta(get_term_by('slug', $tagmatches[1], 'post_tag')->term_id, 'privkey', true));
				} elseif (count($catmatches) > 0) {
					$key = trim(get_term_meta(get_term_by('slug', $catmatches[1], 'category')->term_id, 'privkey', true));
				} elseif (count($globalmatches) > 0) {
					$key = get_option('wp_activitypub_global_privkey');
				} else {
				}
				$key = trim(get_user_meta($follow_user->ID, 'privkey', true));
				$keyval = <<< EOT
$key
EOT;
				$pkey = openssl_get_privatekey($keyval);
				$permalink = get_the_permalink($p);
				$baseurl = get_bloginfo('url');
				$body = json_encode($entityBody);

				if ($type == 'Follow') {
					// if it's a follow request, process it
					// check if it comes from a blocked domain; if so, deny it
					$bd = get_posts(array(
						'post_type' => 'domain_block',
						'posts_per_page' => -1,
						'post_name' => str_replace('.', '-', $domain)
					));
					if (count($bd) > 0) {
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
						$ch = curl_init();
						$signature = "";
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
							CURLOPT_POSTFIELDS => $json_reject,
							CURLOPT_HTTPHEADER => array(
								'Signature: '.$sig_str,
								'Date: '.$date,
								'Host: '.$domain,
								'Content-Type: application/activity+json',
							),
						));
						$result = curl_exec($ch);
						curl_close($ch);
						die(1);
						return;
					}
					// otherwise, add user account and return accept notice
					$follow_user = get_user_by('slug', $following);
					
					// check if we've already created a local account for this user
					$user_check = get_user_by('login', $username);
					
					if (!$user_check) {
						// create new user account if it doesn't exist
						$u = wp_create_user($username, serialize(bin2hex(random_bytes(16))));
						$user_check = get_user_by('id', $u);
						// initialize subscription list w/ requested account
						$f = wp_insert_post(array(
							'post_type' => 'follow',
							'post_author' => $u,
							'post_status' => 'publish',
							'meta_input' => array(
								'following' => $following
							)
						));
						add_user_meta($user_check->ID, 'ap_id', $act->id);
					} else {
						// if the account already exists, add the account to the subscription list
						$f = wp_insert_post(array(
							'post_type' => 'follow',
							'post_author' => $user_check->ID,
							'post_status' => 'publish',
							'meta_input' => array(
								'following' => $following
							)
						));
					}
					
					// store inbox url, preferred username, domain, public key, actor for reference purposes
					update_user_meta($user_check->ID, 'inbox', $inbox);
					update_user_meta($user_check->ID, 'preferred_username', $act->preferredUsername);
					update_user_meta($user_check->ID, 'domain', $domain);
					update_user_meta($user_check->ID, 'pubkey', $act->publicKey->publicKeyPem);
					update_user_meta($user_check->ID, 'actor_info', json_encode($act));
					
					// create acceptance object
					$ch = curl_init();
					$signature = "";
					preg_match('/'.get_option('wp_activitypub_tags_prefix').'([a-zA-Z0-9\-]+)$/', $following, $tagmatches);
					preg_match('/'.get_option('wp_activitypub_cats_prefix').'([a-zA-Z0-9\-]+)$/', $following, $catmatches);
					preg_match('/'.get_option('wp_activitypub_global_name').'$/', $following, $globalmatches);

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
						CURLOPT_POSTFIELDS => $json_accept,
						CURLOPT_HTTPHEADER => array(
							'Signature: '.$sig_str,
							'Date: '.$date,
							'Host: '.$domain,
							'Content-Type: application/activity+json'
						),
					));
					$result = curl_exec($ch);
					curl_close($ch);
				} elseif ($type == 'Create') {
					// handle replies here
					// check if it has an inReplyTo
					// if so, find that post... somehow
					// if the sender doesn't have a remote account here, create it
					// create comment
				} elseif ($type == 'Undo') {
					$user = get_user_by('login', $username."@".$domain);
					if ($entityBody->object->type == 'Follow') {
						// if it's an unfollow request, process it
						preg_match('/\/u\/@([a-zA-Z0-9_]+)/', $entityBody->object->actor, $output_array);
						$followuser = $output_array[1];
						$follow = get_posts(array(
							'post_author' => $user->ID,
							'meta_query' => array(
								array(
									'key' => 'following',
									'value' => $followuser
								)
							)
						));
						foreach($follow as $f) {
							// delete follow records from database
							wp_delete_post($f->ID);
						}
					} elseif ($entityBody->object->type == 'Like') {
						$like = get_posts(array(
							'post_author' => $user->ID,
							'post_type' => 'like',
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
									'key' => 'activity_id',
									'value' => $entityBody->object->id
								)
							)
						));
						foreach ($like as $l) {
							// delete like records from database
							wp_delete_post($l->ID);
						}
					} elseif ($entityBody->object->type == 'Announce') {
						$share = get_posts(array(
							'post_author' => $user->ID,
							'post_type' => 'share',
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
									'key' => 'activity_id',
									'value' => $entityBody->object->id
								)
							)
						));
						foreach ($share as $s) {
							// delete share records from database
							wp_delete_post($s->ID);
						}
					}
				} elseif ($type == 'Like') {
					$user = get_user_by('login', $username."@".$domain);
					$params = array(
						'post_type' => 'like',
						'post_author' => $user->ID,
						'post_content' => json_encode($entityBody),
						'post_status' => 'publish',
						'meta_input' => array(
							'object' => $entityBody->object,
							'activity_id' => $entityBody->id
						)
					);
					wp_insert_post($params);
				}
			}
		} else {
			echo "No user by that name.";
		}
		
		die(1);
  }
  