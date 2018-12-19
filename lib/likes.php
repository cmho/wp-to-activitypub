<?php

	function get_likes() {
		header('Content-type: application/activity+json');
		$req = $_SERVER['REQUEST_URI'];
		preg_match('/([a-zA-Z0-9\/\:\.]+)likes$/', $req, $id_match);
		$id = 'http'.($_SERVER['HTTPS'] ? 's' : '').'://'.$_SERVER['SERVER_NAME'].$req;
		
		$likes = get_posts(array(
			'post_type' => 'like',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'object',
					'value' => 'https?:\/\/'.$_SERVER['SERVER_NAME'].$id_match[1],
					'compare' => 'REGEXP'
				)
			)
		));
		
		$output = array(
			'@context' => "https://www.w3.org/ns/activitystreams",
			'id' => $id,
			'totalItems' => count($likes),
			'type' => 'OrderedCollection',
			'orderedItems' => array()
		);
		
		foreach ($likes as $like) {
			array_push($output['orderedItems'], json_decode($like->post_content));
		}
		echo json_encode($output);
		die(1);
  }
  