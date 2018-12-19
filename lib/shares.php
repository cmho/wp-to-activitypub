<?php

	function get_shares() {
		header('Content-type: application/activity+json');
		$req = $_SERVER['REQUEST_URI'];
		preg_match('/([a-zA-Z0-9\/\:\.]+)shares$/', $req, $id_match);
		$id = 'http'.($_SERVER['HTTPS'] ? 's' : '').'://'.$_SERVER['SERVER_NAME'].$req;
		
		$shares = get_posts(array(
			'post_type' => 'share',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'object',
					'value' => 'https?:\/\/'.$_SERVER['SERVER_NAME'].$id_match[1]
				)
			)
		));
		
		$output = array(
			'@context' => "https://www.w3.org/ns/activitystreams",
			'id' => $id,
			'type' => 'OrderedCollection',
			'totalItems' => count($shares),
			'orderedItems' => array()
		);
		
		foreach ($shares as $share) {
			array_push($output['orderedItems'], json_decode($share->post_content));
		}
		echo json_encode($output);
		die(1);
  }
  