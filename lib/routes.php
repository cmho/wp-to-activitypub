<?php

  function rewrite_init() {
		// set up some nicer rewrite urls
		$tags_prefix = get_option('wp_activitypub_tags_prefix', true) ? get_option('wp_activitypub_tags_prefix', true) : 'tag_';
		$cats_prefix = get_option('wp_activitypub_cats_prefix', true) ? get_option('wp_activitypub_cats_prefix', true) : 'cat_';
		$all_name = get_option('wp_activitypub_global', true) ? get_option('wp_activitypub_global', true) : 'all';
		add_rewrite_rule('^[a-zA-Z0-9\/\-_]+/likes$', 'index.php?rest_route=/ap/v1/likes', 'top');
		add_rewrite_rule('^[a-zA-Z0-9\/\-_]+/shares$', 'index.php?rest_route=/ap/v1/shares', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-_]+)/outbox$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-_]+)/outbox\??([a-zA-Z0-9_\&\=]+)?$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]&matches[2]', 'top');
		add_rewrite_rule('^inbox/?$', 'index.php?rest_route=/ap/v1/inbox', 'top');
		add_rewrite_rule('^u/@'.$all_name.'/outbox$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]', 'top');
		add_rewrite_rule('^u/@'.$all_name.'/outbox\??([a-zA-Z0-9_\&\=]+)?$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]&matches[2]', 'top');
		add_rewrite_rule('^u/@'.$all_name.'$', 'index.php', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)/followers$', 'index.php?rest_route=/ap/v1/followers&acct=$matches[1]', 'top');
		add_rewrite_rule('^u/@'.$tags_prefix.'([a-zA-Z0-9\-_]+)/outbox$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]', 'top');
		add_rewrite_rule('^u/@'.$cats_prefix.'([a-zA-Z0-9\-_]+)/outbox$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]', 'top');
		add_rewrite_rule('^u/@'.$tags_prefix.'([a-zA-Z0-9\-_]+)/outbox\??([a-zA-Z0-9_\&\=]+)?$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]&matches[2]', 'top');
		add_rewrite_rule('^u/@'.$cats_prefix.'([a-zA-Z0-9\-_]+)/outbox\??([a-zA-Z0-9_\&\=]+)?$', 'index.php?rest_route=/ap/v1/outbox&acct=$matches[1]&matches[2]', 'top');
		add_rewrite_rule('^u/@'.$tags_prefix.'([a-zA-Z0-9\-_]+)$', 'index.php?tag=$matches[1]', 'top');
		add_rewrite_rule('^u/@'.$cats_prefix.'([a-zA-Z0-9\-_]+)$', 'index.php?category_name=$matches[1]', 'top');
		add_rewrite_rule('^u/@([a-zA-Z0-9\-]+)$', 'index.php?author_name=$matches[1]', 'top');
		add_rewrite_rule('^\.well-known/([a-zA-Z0-9\-\?\=\@\%\.]+)$', 'index.php?rest_route=/ap/v1/$matches[1]', 'top');
	}
	add_action('init', 'rewrite_init');