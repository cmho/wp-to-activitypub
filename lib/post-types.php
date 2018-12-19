<?php
  function post_types_init() {
		// add the domain_block model so we can block instances who don't deserve our beautiful beautiful content
		register_post_type('domain_block', array(
			'label' => 'Domain Block',
			'public' => true,
			'supports' => array('title')
		));

		register_post_type('user_block', array(
			'label' => 'User Block',
			'public' => true,
			'supports' => array('title')
		));
		
		register_post_type('inboxitem', array(
			'label' => 'Inbox',
			'public' => true,
			'supports' => array('title', 'editor', 'page-attributes')
		));
		
		register_post_type('outboxitem', array(
			'label' => 'Outbox',
			'public' => true,
			'supports' => array('title', 'editor', 'page-attributes')
		));

		register_post_type('like', array(
			'label' => 'Like',
			'public' => false,
			'supports' => array('title', 'editor')
		));
		
		register_post_type('share', array(
			'label' => 'Share',
			'public' => false,
			'supports' => array('title', 'editor')
		));
		
	}
  add_action('init', 'post_types_init');
  