<?php

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
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_outbox'
		));
		
		register_rest_route('ap/v1', '/followers', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_followers'
		));
		
		register_rest_route('ap/v1', '/likes', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_likes'
		));
		
		register_rest_route('ap/v1', '/shares', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => 'get_shares'
		));
	}
	add_action('rest_api_init', 'rest_api_stuff');