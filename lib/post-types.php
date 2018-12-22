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
		
		register_post_type('announce', array(
			'label' => 'Announce',
			'public' => false,
			'supports' => array('title', 'editor')
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
		
		register_post_type('follow', array(
			'label' => 'Follow',
			'public' => false,
			'supports' => array()
		));
		
	}
  add_action('init', 'post_types_init');
  