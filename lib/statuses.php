<?php
  function redirect_to_status() {
		global $post;
		if (is_single() && get_post_type() == 'post' && in_array('application/activity+json', explode(",", getallheaders()['Accept']))) {
			header('Content-type: application/activity+json');
			echo json_encode(get_post_meta($post->ID, 'object', true));
			die(1);
		}
	}
  add_action('template_redirect', 'redirect_to_status');
  