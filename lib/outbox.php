<?php
  	function get_outbox() {
		header('Content-type: application/activity+json');
		$req = $_SERVER['REQUEST_URI'];
		$q = explode('&', $_SERVER['QUERY_STRING']);
		$query = array();
		foreach ($q as $z) {
			$x = explode('=', $z);
			$query[$x[0]] = $x[1];
		}
		// parse it and see if it's an author url by our schema
		$matches;
		preg_match('/^\/u\/@([a-zA-Z0-9\-\_]+)\/?/', $req, $matches);
		if (array_key_exists('page', $query) && $query['page']) {
			$params = array(
				'posts_per_page' => 25
			);
			if ($query['offset']) {
				$params['offset'] = $query['offset'];
			}
			if (count($matches) > 0 && $matches[1]) {
				$user = $matches[1];
				preg_match('/^'.get_option('wp_activitypub_tags_prefix').'([a-zA-Z_\-0-9]+)/', $user, $tagmatch);
				preg_match('/^'.get_option('wp_activitypub_cats_prefix').'([a-zA-Z_\-0-9]+)/', $user, $catmatch);
				preg_match('/^'.get_option('wp_activitypub_global_name').'/', $user, $allmatch);
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
					$params['author'] = get_user_by('slug', $matches[1])->ID;
				}
				
				$posts = get_posts($params);
				$outbox = array(
					'@context' => array(
						"https://www.w3.org/ns/activitystreams",
		        "https://w3id.org/security/v1"
					),
					'id' => get_bloginfo('url').'/u/@'.$matches[1].'/outbox?page=true'.($query['offset'] ? '&offset='.$query['offset'] : ''),
					'type' => 'OrderedCollectionPage',
					'next' => get_bloginfo('url').'/u/@'.$matches[1].'/outbox?page=true&offset='.($query['offset'] ? intval($query['offset'])+25 : 25),
					'partOf' => get_bloginfo('url').'/u/@'.$matches[1].'/outbox'
				);
				
				if ($query['offset']) {
					$amt = intval($query['offset']) - 25;
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
					$post_date = new DateTime(get_the_date('c'), new \DateTimeZone('GMT'));
					$date = $post_date->format('Y-m-d\TH:i:s\Z');
					$taglist = get_the_terms($post->ID, 'post_tag');
					$tags = array();
					foreach ($taglist as $t) {
						array_push($tags, array(
							'type' => 'Hashtag',
							'href' => get_bloginfo('url').'/tag/'.$t->slug,
							'name' => '#'.$t->slug
						));
					}
					$new = array(
						'id' => get_the_permalink($post),
						'type' => 'Create',
						'actor' => get_bloginfo('url').'/u/@'.$user,
						'published' => $date,
						'to' => array(
							"https://www.w3.org/ns/activitystreams#Public"
						),
						'cc' => array(
							get_bloginfo('url').'/u/@'.$user.'/followers'
						),
						'object' => array(
							'id' => get_the_permalink($post),
							'type' => 'Note',
							'inReplyTo' => null,
							'published' => $date,
							'url' => get_the_permalink($post),
							'attributedTo' => get_bloginfo('url').'/u/@'.$user,
							'likes' => get_permalink($post_id).'likes',
							'shares' => get_permalink($post_id).'shares',
							'to' => array(
								"https://www.w3.org/ns/activitystreams#Public"
							),
							'cc' => array(
								get_bloginfo('url').'/u/@'.$user.'/followers'
							),
							'sensitive' => get_post_meta($post->ID, 'sensitive', true),
							'content' => get_the_content(),
							'attachment' => $attachments,
							'tag' => $tags
						)
					);
					if (get_post_meta($post->ID, 'content_warning')) {
						$new['object']['summary'] = get_post_meta($post_id, 'content_warning', true);
					} 
					if (get_post_meta($post->ID, 'sensitive', true) === true) {
						$new['object']['sensitive'] = true;
					}
					array_push($orderedItems, $new);
				}
				wp_reset_postdata();
				$outbox['orderedItems'] = $orderedItems;
				echo json_encode($outbox);
			}
		} else {
			$params = array(
				'posts_per_page' => -1
			);
			if (count($matches) > 0 && $matches[1]) {
				$user = $matches[1];
				preg_match('/^tag_([a-zA-Z_\-0-9]+)/', $user, $tagmatch);
				preg_match('/^cat_([a-zA-Z_\-0-9]+)/', $user, $catmatch);
				preg_match('/^all/', $user, $allmatch);
				if (count($tagmatch) > 0) {
					$params['tax_query'] = array(
						array(
							'taxonomy' => 'post_tag',
							'terms' => $tagmatch[1],
							'field' => 'slug'
						)
					);
				} elseif (count($catmatch) > 0) {
					$params['tax_query'] = array(
						array(
							'taxonomy' => 'category',
							'terms' => $catmatch[1],
							'field' => 'slug'
						)
					);
				} elseif (count($allmatch) == 0) {
					$params['author'] = $matches[1];
				}
			}
			$posts = get_posts($params);
			$outbox = array(
				'@context' => 'https://www.w3.org/ns/activitystreams',
				'id' => get_bloginfo('url').'/u/@'.$user.'/outbox',
				'type' => 'OrderedCollection',
				'totalItems' => count($posts),
				'first' => get_bloginfo('url').'/u/@'.$user.'/outbox?page=true'
			);
			echo json_encode($outbox);
		}
		die(1);
	}