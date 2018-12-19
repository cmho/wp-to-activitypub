<?php

function activate_tasks() {
		global $wp_rewrite;
		// add options fields for plugin
		
		// set up actors for non-subscriber accounts
		$users = get_users(array(
			'role__not_in' => array('subscriber'),
			'meta_query' => array(
				array(
					'key' => 'domain',
					'compare' => 'NOT EXISTS'
				)
			)
		));
		
		// set up SSL keys for each existing non-subscriber user
		foreach ($users as $user) {
			add_user_keys($user->ID);
		}
		
		// flush permalinks
		$wp_rewrite->flush_rules();
		
		return;
	}
	
	function deactivate_tasks() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		return;
	}
	
	function uninstall_tasks() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		return;
	}
	
	function register_hooks() {
		// register actions to take on plugin activate/deactivate/uninstall
		register_activation_hook(__FILE__, 'activate_tasks');
		register_deactivation_hook(__FILE__, 'deactivate_tasks');
		register_uninstall_hook(__FILE__, 'uninstall_tasks');
	}
  add_action('plugins_loaded', 'register_hooks');
  