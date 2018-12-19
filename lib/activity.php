<?php
  function send_messages($post_id) {
		global $post;
		$post = get_post($post_id);
		setup_postdata($post);
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
			
			update_post_meta($post_id, 'sensitive', $_POST['sensitive'] ? true : false);
			update_post_meta($post_id, 'content_warning', $_POST['content_warning']);
			
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
			
			// if there are domain blocks, filter out any users that are in those domains
			if (count($domains) > 0) {
				array_push($subscribers['meta_query'], array(
					'key' => 'domain',
					'value' => $domain_blocks,
					'compare' => 'NOT IN'
				));
			}
			
			// get the author info
			$user = get_user_by('ID', $post->post_author);
			
			// replace newlines with \n because json doesn't like them
			$filtered_content = preg_replace('/\n/', '\n', get_the_content($post_id));
			
			// format the post date like AP expects
			$post_date = new DateTime(get_the_date('c', $post_id));
			$post_date->setTimezone(new \DateTimeZone('GMT'));
			$date = $post_date->format('D, d M Y H:i:s T');
			
			$media = get_attached_media('image', $post_id);
			$attachments = array();
			foreach ($media as $m) {
				array_push($attachments, array(
					'type' => 'Image',
					'content' => wp_get_attachment_caption($m->ID),
					'url' => wp_get_attachment_url($m->ID)
				));
			}
			$taglist = get_the_terms($post_id, 'post_tag');
			$tags = array();
			foreach ($taglist as $t) {
				array_push($tags, array(
					'type' => 'Hashtag',
					'href' => get_bloginfo('url').'/tag/'.$t->slug,
					'name' => '#'.$t->slug
				));
			}
			
			// create message data to send to our followers
			$message = array(
				'@context' => array(
					'https://www.w3.org/ns/activitystreams'
				),
				'id' => get_permalink($post_id),
				'type' => 'Create',
				'actor' => get_bloginfo('url').'/u/@'.$user->user_login,
				'published' => $date,
				'to' => array(
					'https://www.w3.org/ns/activitystreams#Public'
				),
				'cc' => array(
					get_bloginfo('url').'/u/@'.$user->user_login.'/followers'
				),
				'object' => array(
					'id' => get_permalink($post_id),
					'type' => 'Note',
					'published' => $date,
					'attributedTo' => get_bloginfo('url').'/u/@'.$user->user_login,
					'content' => apply_filters('the_content',get_the_content()),
					'likes' => get_permalink($post_id).'likes',
					'shares' => get_permalink($post_id).'shares',
					'to' => array(
						'https://www.w3.org/ns/activitystreams#Public'
					),
					'cc' => array(
						get_bloginfo('url').'/u/@'.$user->user_login.'/followers'
					),
					'attachment' => $attachments,
					'tag' => $tags
				)
			);
			
			if (get_post_meta($post_id, 'content_warning')) {
				$message['object']['summary'] = get_post_meta($post_id, 'content_warning', true);
			} 
			if (get_post_meta($post_id, 'sensitive', true) === true) {
				$message['object']['sensitive'] = true;
			}
			
			// hold onto object data in case we need to do an update or delete or w/e later
			update_post_meta($post_id, 'object', $message['object']);
			
			foreach ($subscribers as $subscriber) {
				// post message to subscriber domains
				$str = "(request-target): post /inbox\nhost: ".$domain."\ndate: ".$date;
				$key = trim(get_user_meta($user->ID, 'privkey', true));
				$keyval = <<< EOT
$key
EOT;
				$pkey = openssl_get_privatekey($keyval);
				openssl_sign($str, $signature, $pkey, OPENSSL_ALGO_SHA256);
				$sig_encode = base64_encode($signature);
				$sig_str = "keyId=\"".get_bloginfo('url')."/u/@".$user->user_login."#main-key\",headers=\"(request-target) host date\",signature=\"" .$sig_encode. "\"";
				$domain = get_user_meta($subscriber->ID, 'domain', true);
				$ch = curl_init();
				curl_setopt_array($ch, array(
					CURLOPT_URL => get_user_meta($subscriber->ID, 'inbox', true),
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => 1,
					CURLOPT_POSTFIELDS => json_encode($message),
					CURLOPT_HTTPHEADER => array(
						'Signature: '.$sig_str,
				    'Date: '.$date,
				    'Host: '.$domain,
						'Content-Type: application/activity+json',
					)
				));
			
				wp_insert_post(array(
					'post_content' => json_encode($message)."\n\n".$sig_str."\n\n".$domain,
					'post_status' => 'publish',
					'post_type' => 'outboxitem'
				));
				$result = curl_exec($ch);
				curl_close($ch);
			}
			
			// if it's in any tags or categories, get subscribers for those
			// send to any subscribers w/ tag or category key
			
			// send to people who follow the 'all' actor with the all key?
			// i guess????
			
			// wait i can use Announce for this.
			wp_reset_postdata();
		}
		
		return;
	}
	add_action('save_post', 'send_messages');
