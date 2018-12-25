<?php

  function add_user_keys($user_id) {
		$user = get_user_by('ID', $user_id);
		if (!in_array('subscriber', $user->roles)) {
			if (!get_user_meta($user->ID, 'pubkey')) {
				// if no keys set up for a profile on save, generate new ones
				$res = openssl_pkey_new(array(
					'digest_alg' => 'sha256WithRsaEncryption',
					'private_key_bits' => 4096,
					'private_key_type' => OPENSSL_KEYTYPE_RSA
				));
				openssl_pkey_export($res, $privKey);
				$pubKey = openssl_pkey_get_details($res);
				// save keys to user meta fields
				update_user_meta($user->ID, 'pubkey', $pubKey['key']);
				update_user_meta($user->ID, 'privkey', $privKey);
			}
		}
	}
	
	function check_suspension($user_id) {
		// on profile save, update the suspended field
		$user = get_user_by('ID', $user_id);
		update_user_meta($user_id, 'suspended', $_POST['suspend']);
	}
	// run this on profile save
	add_action('profile_update', 'add_user_keys');
	add_action('profile_update', 'check_suspension');
	
	function add_suspend_checkbox($profileuser) {
		// add account suspension checkbox on user profile page to prevent federating to a specific user
		?>
			<h3>Account Suspension</h3>
			<table class="form-table">
				<tbody>
					<tr>
						<th>Suspend User?</th>
						<td>
							<input type="checkbox" name="suspend" id="suspend"<?= get_user_meta($profileuser->ID, 'suspended', true) ? ' checked="checked"' : ''; ?> value="true" />
						</td>
					</tr>
				</tbody>
			</table>
		<?php
	}
	add_action( 'show_user_profile', 'add_suspend_checkbox', 10, 1 );
	add_action( 'edit_user_profile', 'add_suspend_checkbox', 10, 1 );
	
	function add_info_fields($profileuser) {
		?>
		<h3>Instance Information</h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th>Domain</th>
					<td>
						<input type="text" name="userdomain" id="userdomain" value="<?= get_user_meta($profileuser->ID, 'domain', true); ?>" />
					</td>
				</tr>
				<tr>
					<th>Preferred Username</th>
					<td>
						<input type="text" name="prefuser" id="prefuser" value="<?= get_user_meta($profileuser->ID, 'preferred_username', true); ?>" />
					</td>
				</tr>
				<tr>
					<th>Inbox</th>
					<td>
						<input type="text" name="inboxurl" id="inboxurl" value="<?= get_user_meta($profileuser->ID, 'inbox', true); ?>" />
					</td>
				</tr>
				<tr>
					<th>Public Key</th>
					<td>
						<textarea name="publickey" id="publickey"><?= get_user_meta($profileuser->ID, 'pubkey', true); ?></textarea>
					</td>
				</tr>
				<tr>
					<th>Actor Info</th>
					<td>
						<textarea name="actor" id="actor"><?= get_user_meta($profileuser->ID, 'actor_info', true); ?></textarea>
					</td>
				</tr>
				<tr>
					<th>Follow Result</th>
					<td>
						<textarea name="follow" id="follow"><?= get_user_meta($profileuser->ID, 'follow_result', true); ?></textarea>
					</td>
				</tr>
				<tr>
					<th>Following:</th>
					<td>
						<?php
							$follows = get_posts(array(
								'post_type' => 'follow',
								'posts_per_page' => -1
							));
						?>
						<ul>
							<?php foreach ($follows as $follow) : ?>
								<li><?= get_post_meta($follow->ID, 'following', true); ?></li>
							<?php endforeach; ?>
						</ul>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	add_action( 'show_user_profile', 'add_info_fields', 10, 1 );
  add_action( 'edit_user_profile', 'add_info_fields', 10, 1 );
  