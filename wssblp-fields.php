<?php

function wssblp_add_custom_metabox() {
    add_meta_box(
        'wssblp_meta',
        'Book',
        'wssblp_meta_callback',
        'book',
        'normal');
}

add_action('add_meta_boxes', 'wssblp_add_custom_metabox');

function wssblp_meta_callback( $post ) {
    wp_nonce_field(basename(__FILE__), 'wssblp_book_nonce');
    $wssblp_stored_meta = get_post_meta($post->ID)
    ?>
    <!--Author-->
    <div class="meta-row">
        <div class="meta-th">
            <label for="author">Author</label>
        </div>
        <div class="meta-td">
            <input type="text" name="author" id="author"
                   value="<?php echo (!empty($wssblp_stored_meta['author'])) ? esc_attr($wssblp_stored_meta['author'][0]) : '' ?>"/>
        </div>
    </div>

    <!--ISBN 10-->
    <div class="meta-row">
        <div class="meta-th">
            <label for="isbn-10">ISBN 10</label>
        </div>
        <div class="meta-td">
            <input type="text" name="isbn_10" id="isbn-10"
                   value="<?php echo (!empty($wssblp_stored_meta['isbn_10'])) ? esc_attr($wssblp_stored_meta['isbn_10'][0]) : '' ?>"/>
			<input type="button" id="retrieve-isbn-10" class="button button-primary button-large" value="retrieve" />
            <img src="<?= plugins_url("images/ajax-loader.gif", __FILE__)?>" id="isbn-loader-10" style="display: none;">
            <span id="isbn-error-10" class="error"></span>
        </div>
    </div>

    <!--ISBN 13-->
    <div class="meta-row">
        <div class="meta-th">
            <label for="isbn-13">ISBN 13</label>
        </div>
        <div class="meta-td">
            <input type="text" name="isbn_13" id="isbn-13"
                   value="<?php echo (!empty($wssblp_stored_meta['isbn_13'])) ? esc_attr($wssblp_stored_meta['isbn_13'][0]) : '' ?>"/>
			<input type="button" id="retrieve-isbn-13" class="button button-primary button-large" value="retrieve" />
            <img src="<?= plugins_url("images/ajax-loader.gif", __FILE__)?>" id="isbn-loader-13" style="display: none">
            <span id="isbn-error-13" class="error"></span>
		</div>
    </div>

    <!--Status-->
    <div class="meta-row">
        <div class="meta-th">
            <label for="status" class="wssblp-row-title">Status</label>
        </div>
        <div class="meta-td">

            <select name="status" id="status">
                <option value="available" <?php if ( ! empty ( $wssblp_stored_meta['status'] )) selected( $wssblp_stored_meta['status'][0], 'available' ); ?>>Available</option>';
                <option value="borrowed" <?php if ( ! empty ( $wssblp_stored_meta['status'] )) selected( $wssblp_stored_meta['status'][0], 'borrowed' ); ?>>Borrowed</option>';
            </select>
			<?php if ($wssblp_stored_meta['status'][0] == 'borrowed' || !empty($wssblp_stored_meta['user'][0])) :?>
				 <span>(by <span class="borrow-by"><?= get_user_by('id', $post->user)->user_login;?></span>)</span>
			<?php endif; ?>
        </div>
		<div id="users-list"></div>
    </div>

    <!--Date Adding-->
    <div class="meta-row">
        <div class="meta-th">
            <label for="date-added" class="wssblp-row-title">Date Added</label>
        </div>
        <div class="meta-td">
            <input type="text" size=10 class="wssblp-row-content datepicker" name="date_added" id="date-added"
                   value="<?php echo (!empty ($wssblp_stored_meta['date_added'])) ? esc_attr($wssblp_stored_meta['date_added'][0]) : '' ?>"/>

        </div>
    </div>
    <div class="meta-row">
        <div class="meta-td">
            <input type="hidden" name="user" id="user"
                   value="<?php echo (!empty ($wssblp_stored_meta['user'])) ? esc_attr($wssblp_stored_meta['user'][0]) : '' ?>"/>
        </div>
    </div>
    <!--Description-->
    <div class="meta">
        <div class="meta-th">
            <span>Description</span>
        </div>
        <div class="meta-editor">

        </div>
        <?php
        $content = get_post_meta($post->ID, 'description', true);
        $editor = 'description';
        $settings = [
            'textarea_rows' => 8,
            'media_buttons' => false,
        ];
        wp_editor($content, $editor, $settings);
        ?>
    </div>

    <?php
}

function wssblp_meta_save($post_id)
{
	global $wpdb;
    $is_autosave = wp_is_post_autosave($post_id);
    $is_revision = wp_is_post_revision($post_id);
    $is_valid_nonce = isset( $_POST[ 'wssblp_book_nonce' ] ) && wp_verify_nonce( $_POST[ 'wssblp_book_nonce' ], basename( __FILE__ ));

    if ($is_autosave || $is_revision || !$is_valid_nonce) {
        return;
    }

    if (isset($_POST['author'])) {
        update_post_meta($post_id, 'author', sanitize_text_field($_POST['author']));
    }

    if (isset($_POST['isbn_10'])) {
        update_post_meta($post_id, 'isbn_10', sanitize_text_field($_POST['isbn_10']));
    }
    if (isset($_POST['isbn_13'])) {
        update_post_meta($post_id, 'isbn_13', sanitize_text_field($_POST['isbn_13']));
    }
    if (isset($_POST['status'])) {
        update_post_meta($post_id, 'status', $_POST['status']);
    }
    if (isset($_POST['date_added'])) {
        update_post_meta($post_id, 'date_added', sanitize_text_field($_POST['date_added']));
    }
    if (isset($_POST['description'])) {
        update_post_meta($post_id, 'description', $_POST['description']);
    }
    if (isset($_POST['user']) && $_POST['status'] != 'available') {
        update_post_meta($post_id, 'user', $_POST['user']);
    } else {
        update_post_meta($post_id, 'user', '');
		// update library history
		$wpdb->update($wpdb->prefix . 'library_history', ['check_in_timestamp' => current_time("timestamp")],
			[
				'library_history_postid' => intval($post_id),
				'library_history_userid' => intval($_POST['user']),
				'check_in_timestamp' => "",
			]
		);
    }
}

add_action('save_post', 'wssblp_meta_save');


