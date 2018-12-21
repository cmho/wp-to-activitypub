<?php
	function wp_activitypub_options_html($args) {
		$options = get_option('wp_activitypub_settings');
		if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
	        <?php
		        settings_fields('wp_activitypub');
		        do_settings_sections('wp_activitypub');
		        submit_button('Save Settings');
		      ?>
        </form>
    </div>
    <?php
	}
	
	function wp_activitypub_global_cb($args) {
		$options = get_option('wp_activitypub_global');
		?>
		<label for="wp_activitypub_global"><input type="checkbox" name="wp_activitypub_global" value="true"<?= $options ? ' checked="checked"' : ''; ?> /> Enable @all user?</label>
		<?php
	}
	
	function wp_activitypub_global_name_cb($args) {
		$options = get_option('wp_activitypub_global_name');
		?>
		<input type="text" name="wp_activitypub_global_name" value="<?= $options; ?>" />
		<?php
	}
	
	function wp_activitypub_tags_cb($args) {
		$options = get_option('wp_activitypub_tags');
		$prefix = get_option('wp_activitypub_tags_prefix');
		?>
		<label for="wp_activitypub_tags"><input type="checkbox" name="wp_activitypub_tags" value="true"<?= $options ? ' checked="checked"' : ''; ?> /> Enable tag users?</label>
		<p class="description">This will enable federation users to follow <?= $prefix; ?>[tagname]@yourdomain.com.</p>
		<?php
	}
	
	function wp_activitypub_tags_prefix_cb($args) {
		$options = get_option('wp_activitypub_tags_prefix');
		?>
		<input type="text" name="wp_activitypub_tags_prefix" value="<?= $options; ?>" />
		<?php
	}
	
	function wp_activitypub_cats_cb($args) {
		$options = get_option('wp_activitypub_cats');
		$prefix = get_option('wp_activitypub_cats_prefix');
		?>
		<label for="wp_activitypub_cats"><input type="checkbox" name="wp_activitypub_cats" value="true"<?= $options ? ' checked="checked"' : ''; ?> /> Enable category users?</label>
		<p class="description">This will enable federation users to follow <?= $prefix; ?>[categoryname]@yourdomain.com.</p>
		<?php
	}
	
	function wp_activitypub_cats_prefix_cb($args) {
		$options = get_option('wp_activitypub_cats_prefix');
		?>
		<input type="text" name="wp_activitypub_cats_prefix" value="<?= $options; ?>" />
		<?php
	}
	
	function wp_activitypub_posters_cb($args) {
		return;
	}
	
	function ap_settings_init() {
		register_setting('wp_activitypub', 'wp_activitypub');
		add_settings_section(
			'wp_activitypub_posters',
			__('Global, Tag and Category Users', 'wp_activitypub'),
			'wp_activitypub_posters_cb',
			'wp_activitypub'
		);
		
		register_setting('wp_activitypub', 'wp_activitypub_global', array(
			'type' => 'boolean',
			'default' => false
		));
		register_setting('wp_activitypub', 'wp_activitypub_global_name', array(
			'type' => 'string',
			'default' => 'all'
		));
		/*
		register_setting('wp_activitypub', 'wp_activitypub_global_pubkey', array(
			'type' => 'string'
		));
		register_setting('wp_activitypub', 'wp_activitypub_global_privkey', array(
			'type' => 'string'
		));*/
		add_settings_field(
			'wp_activitypub_global',
			__('Global User?', 'wp_activitypub'),
			'wp_activitypub_global_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_global',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
		add_settings_field(
			'wp_activitypub_global_name',
			__('Global User Name', 'wp_activitypub'),
			'wp_activitypub_global_name_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_global_name',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
		
		register_setting('wp_activitypub', 'wp_activitypub_tags', array(
			'type' => 'boolean',
			'default' => false
		));
		register_setting('wp_activitypub', 'wp_activitypub_tags_prefix', array(
			'type' => 'string',
			'default' => 'tag_'
		));
		add_settings_field(
			'wp_activitypub_tags',
			__('Tag Users?', 'wp_activitypub'),
			'wp_activitypub_tags_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_tags',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
		add_settings_field(
			'wp_activitypub_tags_prefix',
			__('Tag User Prefix', 'wp_activitypub'),
			'wp_activitypub_tags_prefix_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_tags_prefix',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
		
		register_setting('wp_activitypub', 'wp_activitypub_cats', array(
			'type' => 'boolean',
			'default' => false
		));
		register_setting('wp_activitypub', 'wp_activitypub_cats_prefix', array(
			'type' => 'string',
			'default' => 'cat_'
		));
		add_settings_field(
			'wp_activitypub_cats',
			__('Category Users?', 'wp_activitypub'),
			'wp_activitypub_cats_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_cats',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
		add_settings_field(
			'wp_activitypub_cats_prefix',
			__('Cat User Prefix', 'wp_activitypub'),
			'wp_activitypub_cats_prefix_cb',
			'wp_activitypub',
			'wp_activitypub_posters',
			[
				'label_for' => 'wp_activitypub_cats_prefix',
				'class' => 'wp_activitypub_row',
				'wp_activitypub_custom_data' => 'custom'
			]
		);
	}
	add_action('admin_init', 'ap_settings_init');
	
	function ap_options_page() {
		add_submenu_page(
			'options-general.php',
			'WP ActivityPub Settings',
			'WP ActivityPub',
			'administrator',
			'wp_activitypub',
			'wp_activitypub_options_html'
		);
	}
  add_action('admin_menu', 'ap_options_page');


	
	function add_term_keys($term_id, $tt_id, $taxonomy) {
		$privKey;
		$res = openssl_pkey_new(array(
			'digest_alg' => 'sha256WithRsaEncryption',
			'private_key_bits' => 4096,
			'private_key_type' => OPENSSL_KEYTYPE_RSA
		));
		openssl_pkey_export($res, $privKey);
		$pubKey = openssl_pkey_get_details($res);
		add_term_meta($term_id, 'pubkey', $pubKey['key']);
		add_term_meta($term_id, 'privkey', $privKey);
	}
	add_action('create_term', 'add_term_keys');
	
	function add_global_pkeys() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		if (get_option('wp_activitypub_global') && empty(get_option('wp_activitypub_global_pubkey'))) {
			$privKey;
			$res = openssl_pkey_new(array(
				'digest_alg' => 'sha256WithRsaEncryption',
				'private_key_bits' => 4096,
				'private_key_type' => OPENSSL_KEYTYPE_RSA
			));
			openssl_pkey_export($res, $privKey);
			$pubKey = openssl_pkey_get_details($res);
			$pubKey['key'] = str_replace('\r', '\\n', $pubKey['key']);
			$privKey = str_replace('\r', '\\n', $privKey);
			update_option('wp_activitypub_global_pubkey', $pubKey['key']);
			update_option('wp_activitypub_global_privkey', $privKey);
		}
	}
	add_action('update_option_wp_activitypub_global', 'add_global_pkeys');
	
	function add_tag_pkeys() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		if (get_option('wp_activitypub_tags')) {
			$tags = get_terms(array(
				'taxonomy' => 'post_tag',
				'hide_empty' => false,
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => 'pubkey',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'pubkey',
						'value' => ''
	 				)
				)
			));
			foreach ($tags as $tag) {
				add_term_keys($tag->term_id, '', 'tag');
			}
		}
	}
	add_action('update_option_wp_activitypub_tags', 'add_tag_pkeys');
	
	function add_cat_pkeys() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		if (get_option('wp_activitypub_cats')) {
			$cats = get_terms(array(
				'taxonomy' => 'category',
				'hide_empty' => false,
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => 'pubkey',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'pubkey',
						'value' => ''
	 				)
				)
			));
			
			foreach ($cats as $cat) {
				add_term_keys($cat->term_id, '', 'category');
			}
		}
	}
	add_action('update_option_wp_activitypub_cats', 'add_cat_pkeys');
  