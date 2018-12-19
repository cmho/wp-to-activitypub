<?php
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
							echo $reject;
							wp_update_post(array(
								'ID' => $p,
								'post_content' => $reject
							));
							die(1);
							return;
						}
						// otherwise, add user account and return accept notice
						$follow_user = get_user_by('slug', $following);
						
						// check if we've already created a local account for this user
						$user_check = get_user_by('slug', $username);
						
						if (!$user_check) {
							// create new user account if it doesn't exist
							$user_check = wp_create_user($username, serialize(bin2hex(random_bytes(16))));
							// initialize subscription list w/ requested account
							add_user_meta($user_check, 'following', array($following));
							add_user_meta($user_check, 'ap_id', $act->id);
						} else {
							// if the account alreaddy  exists, add the account to the subscription list
							$follows = get_user_meta($user_check, 'following', true);
							array_push(get_user_meta($follows, 'following'), $following)
							update_user_meta($user_check, 'following', $follows);
						}
						
						// store inbox url, preferred username, domain, public key, actor for reference purposes
						update_user_meta($user_check, 'inbox', $inbox);
						update_user_meta($user_check, 'preferred_username', $act->preferredUsername);
						update_user_meta($user_check, 'domain', $domain);
						update_user_meta($user_check, 'pubkey', $act->publicKey->publicKeyPem);
						update_user_meta($user_check, 'actor_info', json_encode($act));
						
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
					} elseif ($type == 'Like') {
						$params = array(
							'post_type' => 'like',
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
			//} else {
			//	header("HTTP/1.1 401 Request signature could not be verified");
			//}
		} else {
			echo "No user by that name.";
		}
		
		die(1);
  }
  