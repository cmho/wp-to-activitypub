<?php

  function wp_activitypub_meta($post)
	{
    ?>
    <table>
	    <tr class="wp_activitypub_row">
				<th scope="row">
					<label for="sensitive">Sensitive Content?</label>
				</th>
				<td>
					<input type="checkbox" name="sensitive" id="sensitive"<?= get_post_meta($post->ID, 'sensitive', true) ? ' checked="checked"' : ''; ?>>
				</td>
	    </tr>
		  <tr class="wp_activitypub_row">
			  <th scope="row">
					<label for="content_warning">Content Warning?</label>
			  </th>
			  <td>
					<input type="text" name="content_warning" id="content_warning" value="<?= get_post_meta($post->ID, 'content_warning', true); ?>">
			  </td>
		  </tr>
    </table>
    <?php
	}
	function adding_custom_meta_boxes( $post ) {
    add_meta_box( 
        'wp-activitypub-meta',
        __( 'ActivityPub Information' ),
        'wp_activitypub_meta',
        'post',
        'normal',
        'default'
    );
	}
  add_action( 'add_meta_boxes_post', 'adding_custom_meta_boxes' );
  