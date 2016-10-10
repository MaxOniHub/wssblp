<?php
/**
 * Plugin Name: Wordpress Self-Serve Book Library Plugin
 * Description: The purpose of managing the checking and checking out of physical books in a self-serve manner from a corporate book library
 * Author: Yanpix
 * Version: 1.2.8
 */


//Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once ( plugin_dir_path(__FILE__) . 'wssblp-settings.php' );
require_once ( plugin_dir_path(__FILE__) . 'wssblp-fields.php' );


register_activation_hook( __FILE__, 'library_history' );

function library_history() {
    global $wpdb;

    $table_name = $wpdb->prefix . "library_history";

    $create_sql = "CREATE TABLE $table_name (".
        "library_history_id INT(11) NOT NULL auto_increment,".
        "library_history_postid INT(11) NOT NULL ,".
        "library_history_userid INT(11) NOT NULL ,".
        "check_out_timestamp VARCHAR(15) NOT NULL ,".
        "check_in_timestamp VARCHAR(15) NOT NULL ,".
        "PRIMARY KEY (library_history_id));";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_sql );
}

register_activation_hook( __FILE__, 'ratings_history' );

function ratings_history() {
	global $wpdb;

	$table_name = $wpdb->prefix . "ratings_history";

	$create_sql = "CREATE TABLE $table_name (".
		"ratings_history_id INT(11) NOT NULL auto_increment,".
		"ratings_history_comment_id INT(11) NOT NULL ,".
		"ratings_history_user_id INT(11) NOT NULL ,".
		"ratings_history_post_id INT(11) NOT NULL ,".
		"ratings_history_rating INT(2) NOT NULL ,".
		"date DATETIME NOT NULL ,".
		"PRIMARY KEY (ratings_history_id));";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $create_sql );
}


function wssblp_admin_enqueue_scripts() {

    global $pagenow, $typenow;

    if (($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == 'book') {
        wp_enqueue_style('wssblp-admin-css', plugins_url('css/wssbpl-admin.css', __FILE__));
        wp_enqueue_style('jquery-style', plugins_url('css/jquery-ui.css', __FILE__));
        wp_enqueue_script('wssblp-admin-js', plugins_url('js/wssbpl-admin.js', __FILE__), ['jquery', 'jquery-ui-datepicker'], '20151012', true);
        wp_enqueue_script('wssblp-searcher',plugins_url('js/jquery.sieve.js', __FILE__) , [], '20151012', true);
	}
    wp_enqueue_style('wssbpl-custom-comments', plugins_url('css/wssbpl-custom-comments.css', __FILE__));

}
add_action('admin_enqueue_scripts', 'wssblp_admin_enqueue_scripts');

function wssblp_enqueue_scripts() {
    global $pagenow;
    if ($pagenow == 'index.php') {
        wp_enqueue_style('wssblp-style', plugins_url('css/wssbpl-style.css', __FILE__));
        wp_enqueue_style('wssblp-bootstrap', plugins_url('css/bootstrap.min.css', __FILE__));
        wp_enqueue_style('wssblp-bootstrap-toggle', plugins_url('css/bootstrap-toggle.min.css', __FILE__));

        wp_enqueue_script('wssblp-bootstrap', plugins_url('js/bootstrap.min.js', __FILE__), ['jquery'], '20151013', true);
        wp_enqueue_script('wssblp-bootstrap-toggle', plugins_url('js/bootstrap-toggle.min.js', __FILE__), [], '20151013', true);
        wp_enqueue_script('wssblp-jquery-form', plugins_url('js/jquery.form.js', __FILE__), [], '20151013', true);

        /*WP Front-edn Ajax Request*/
        wp_enqueue_script( 'custom-ajax-request', plugins_url('js/ajax-request.js', __FILE__),['jquery']);
        wp_localize_script( 'custom-ajax-request', 'AjaxRequestVar', [ 'ajaxurl' => admin_url( 'admin-ajax.php' ) ]);

    }
}

add_action( 'wp_enqueue_scripts', 'wssblp_enqueue_scripts' );


function render_post_modal() {
    global $current_user;
    if (isset($_POST['post_id'])) {
        $post_id = $_POST['post_id'];

        $data = [
            'post' => get_post($post_id),
            'meta' => get_post_meta($post_id),
        ];
        if ($data['meta']['user'][0] != $current_user->ID) {
            $user = get_user_by('id', $data['meta']['user'][0]);
            echo "This book has been already checked by " . $user->user_login;
            wp_die();
        }

        /*short info about of the book*/
		$ratings_custom =  function_exists('the_ratings') ? expand_ratings_template('<div style="float: right">%RATINGS_IMAGES%</div>', $post_id) : '';
		$output = '<div class="books-wrapper popup">
                        <div class="entry">
                            <div class="thumb">
                                '.get_the_post_thumbnail($post_id).'
                            </div>
                            <div class="info">
                                <div class="header">
                                    <div class="title">
                                        <h3><a href="'.get_permalink($post_id).'">'.$data['post']->post_title.'</a></h3>
                                </div>

                                '.$ratings_custom.'
                            </div>
                             <div class="author">
                                        <span>Author: '.$data['meta']['author'][0].'</span>
                                </div>
                        </div>
                   </div>
                <hr />
                <span>Review this book now: </span>
            </div>';

        echo $output;

        /*just put ratings...*/
        $query = new WP_Query(['p' => $post_id, 'post_type' => 'book',]);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo function_exists('the_ratings') ? the_ratings() : 'WP-PostRatings has not been activated';
            }
        }
        /*render a review form*/
        echo '<form method="post" name="review-form" id="review-form">
                    <input id="post_id" type="hidden" name="post_id" value="'.$post_id.'"/>
                    <div class="row">
                        <label for="review"> Book review:</label>
                    </div>
                    <div class="row">
                        <textarea id="review" type="text" name="review" />
                    </div>
                    <div class="row">
                        <input type="button" id="review-form-btn" value="Complete Check-in" />
                    </div>
              </form>';

        wp_die();
    }

}

add_action('wp_ajax_nopriv_render_post_modal', 'render_post_modal');
add_action('wp_ajax_render_post_modal', 'render_post_modal');


function render_post_modal_check_out() {
    if (isset($_POST['post_id'])) {
        $post_id = $_POST['post_id'];

        $data = [
            'post' => get_post($post_id),
            'meta' => get_post_meta($post_id),
        ];

        /*render a review form*/
        echo '<form action="#" method="post" name="check-out-form" id="check-out-form">
                    <input id="post_id" type="hidden" name="post_id" value="'.$post_id.'"/>
                    <input type="button" id="check-out-form-btn" value="Confirm" />
              </form>';

        wp_die();
    }

}
add_action('wp_ajax_nopriv_render_post_modal_check_out', 'render_post_modal_check_out');
add_action('wp_ajax_render_post_modal_check_out', 'render_post_modal_check_out');

function check_in() {
    global $current_user, $wpdb;
    $params = [];
    parse_str($_POST['dataForm'], $params);
    $meta = get_post_meta($params['post_id']);
	$user_id = $current_user->ID;
	$rating = 0;

    if (isset($params['post_id']) && $meta['user'][0] == $user_id) {

		// update history
       $wpdb->update($wpdb->prefix . 'library_history', ['check_in_timestamp' => current_time("timestamp")],
            [
                'library_history_postid' => $params['post_id'],
                'library_history_userid' => $user_id,
                'check_in_timestamp' => "",
            ]
        );
		// clear rating data for the user
		$rating = get_rating_by_user_id($user_id);
		$wpdb->query( "DELETE FROM {$wpdb->ratings} WHERE rating_userid = " . $user_id );

        update_post_meta($params['post_id'], 'user', "");
        update_post_meta($params['post_id'], 'status', 'available');
    }
    if (!empty($params['review'])) {
        $data = array(
            'comment_post_ID' => $params['post_id'],
			'user_id' => $user_id,
            'comment_author' => $current_user->user_login,
            'comment_author_email' => $current_user->user_email,
            'comment_content' => $params['review'],
            'comment_date' => date('Y-m-d H:i:s'),
            'comment_date_gmt' => date('Y-m-d H:i:s'),
            'comment_approved' => 1,

        );
        $comment_id = wp_insert_comment($data);
		save_ratings_history($comment_id, $user_id, $params['post_id'],$rating);
    }
    wp_die();
}

add_action('wp_ajax_nopriv_check_in', 'check_in');
add_action('wp_ajax_check_in', 'check_in');

function check_out()
{
    global $current_user, $wpdb;

    $params = [];
    parse_str($_POST['dataForm'], $params);
	$user_id = $current_user->ID;

    if (isset($params['post_id'])) {
        $meta = get_post_meta($params['post_id']);

        if (empty($meta['user'][0])) {
            update_post_meta($params['post_id'], 'user', $user_id);
            update_post_meta($params['post_id'], 'status', 'borrowed');

			update_library_history($params['post_id'], $user_id);

        } else {
            $user = get_user_by('id', $meta['meta']['user'][0]);
            echo "This book has been already checked by " . $user->user_login;
        }

        wp_die();
    }
}

add_action('wp_ajax_nopriv_check_out', 'check_out');
add_action('wp_ajax_check_out', 'check_out');

function delete_ratings($params) {
	global $current_user, $wpdb;

	if (function_exists('the_ratings')) {
		update_post_meta($params['post_id'], 'ratings_users', '');
		update_post_meta($params['post_id'], 'ratings_score', '');
		update_post_meta($params['post_id'], 'ratings_average', '');

		$wpdb->query( "DELETE FROM {$wpdb->ratings} WHERE rating_userid = " . $current_user->ID );
	}
}
function revert_ratings() {
	global $current_user;
	$user_id = $current_user->ID;
	$params['post_id'] = $_POST['post_id'] ? $_POST['post_id'] : 0;

	$meta = get_post_meta($params['post_id']);
	if ($meta['user'][0] == $user_id)
		delete_ratings($params);
}

add_action('wp_ajax_nopriv_revert_ratings', 'revert_ratings');
add_action('wp_ajax_revert_ratings', 'revert_ratings');


function show_users_list() {
	if (isset($_POST['status']) && $_POST['status'] == 'borrowed') {

		$user_query = new WP_User_Query(['orderby' => 'post_count', 'order' => 'DESC']);

		if (!empty($user_query->results)) {
			$output = "";
			foreach ($user_query->results as $user) {
				$output .= "
				<p>
					<input type='radio' name='users' value='" . $user->data->ID . "' id='user" . $user->data->ID . "'>
					<label for='user" . $user->data->ID . "'>" . $user->data->user_login . "</label>
				</p>
				";

			}
			$output .= " <div class='row'>
                        	<input type='button' id='borrow-by-btn' value='Confirm' style='display: none;'/>
                    	</div>";
			echo $output;

		} else {
			echo 'No users found.';
		}
	} else {
		echo '';
	}
	wp_die();
}

add_action('wp_ajax_nopriv_show_users_list', 'show_users_list');
add_action('wp_ajax_show_users_list', 'show_users_list');


function save_borrow_by() {

	if ( isset($_POST['user_id']) && isset($_POST['post_id'])) {
		$post_id = intval($_POST['post_id']);
		$user_id = intval($_POST['user_id']);
		update_post_meta($post_id, 'user', $user_id);
		update_post_meta($post_id, 'status', 'borrowed');

		update_library_history($post_id, $user_id);

	}
	echo get_user_by('id', $user_id)->user_login;
	wp_die();
}

add_action('wp_ajax_nopriv_save_borrow_by', 'save_borrow_by');
add_action('wp_ajax_save_borrow_by', 'save_borrow_by');


//retrieve by ISBN 10
function retrieve_by_isbn() {
    if (isset($_POST['isbn']) && isset($_POST['post_id'])) {
		$post_id = $_POST['post_id'];
        $isbn_number = $_POST['isbn'];
        $url = 'http://www.isbnsearch.org/isbn/' . $isbn_number;
        $data = file_get_contents($url);
        if ($data) {
            echo parse_content($data, $post_id);
        }
    }

	wp_die();
}

add_action('wp_ajax_nopriv_retrieve_by_isbn', 'retrieve_by_isbn');
add_action('wp_ajax_retrieve_by_isbn', 'retrieve_by_isbn');

function parse_content($data, $post_id) {
	$book = [];

	// Get thumbnail
	preg_match("/<div[^>]*class=\"thumbnail\">(.*?)<\\/div>/si", $data, $match);
	preg_match("/<img src=\"(.*?)\"/s", $data, $match[1]);
	$book['thumbnail'] = $match[1];
	$book['thumbnail']['attach_data'] = set_featured_image($book['thumbnail'][1], $post_id);
	// Get title
	preg_match('#<h2>(.*?)</h2>#is', $data, $match);
	$book['title'] = $match[1];
	// Get author
	preg_match('#<p><strong>Author:</strong>(.*?)<\\/p>#is', $data, $match);
	$book['author'] = trim($match[1]);


	// Get ISBN 13

	return json_encode($book);
}
function set_featured_image($image_url, $post_id) {
	$upload_dir = wp_upload_dir();
	$image_data = file_get_contents($image_url);
	$filename = basename($image_url);

	if(wp_mkdir_p($upload_dir['path']))
		$file = $upload_dir['path'] . '/' . $filename;
	else
		$file = $upload_dir['basedir'] . '/' . $filename;
	file_put_contents($file, $image_data);

	$wp_filetype = wp_check_filetype($filename, null );
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => sanitize_file_name($filename),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	set_post_thumbnail( $post_id, $attach_id );
	$attach_data['file'] = $upload_dir['baseurl'].'/'.$attach_data['file'];
	return $attach_data;
}

function get_check_out_date($user_id, $post_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . "library_history";
    $check_out_date = $wpdb->get_var( $wpdb->prepare( "SELECT check_out_timestamp FROM {$table_name} WHERE library_history_postid = %d AND library_history_userid = %d", $post_id, $user_id ) );

    return $check_out_date ? date('m/d/Y H:i', $check_out_date) : '';
}
function get_rating_by_user_id($user_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . "ratings";
	$rating = $wpdb->get_var( $wpdb->prepare( "SELECT rating_rating FROM {$table_name} WHERE rating_userid = %d",$user_id ) );
	return $rating;
}

function save_ratings_history($comment_id, $user_id, $post_id, $rating) {
	global $wpdb;
	$table_name = $wpdb->prefix . "ratings_history";

	$wpdb->insert($table_name, [
			'ratings_history_comment_id' => $comment_id,
			'ratings_history_user_id' => $user_id,
			'ratings_history_post_id' => $post_id,
			'ratings_history_rating' => $rating,
			'date' => date('Y-m-d H:i:s')
		],
		['%d', '%d', '%d', '%d', '%s']
	);
}
function get_user_rating_from_history($comment_id, $user_id, $post_id, $time) {
	global $wpdb;
	$table_name = $wpdb->prefix . "ratings_history";
	$rating = $wpdb->get_var( $wpdb->prepare( "SELECT ratings_history_rating FROM {$table_name}
			WHERE ratings_history_comment_id = %d AND ratings_history_user_id = %d AND ratings_history_post_id = %d AND date = %s",
		$comment_id, $user_id, $post_id, $time));
	return $rating;

}

function update_library_history($post_id, $user_id) {
	global $wpdb;
	$table_name = $wpdb->prefix . "library_history";

	$if_exist_row = get_check_out_date($user_id, $post_id);

	if ($if_exist_row) {
		$wpdb->update($table_name, [
				'check_out_timestamp' => current_time("timestamp"),
				'check_in_timestamp' => ""],
			[
				'library_history_postid' => $post_id,
				'library_history_userid' => $user_id
			]
		);
	} else {
		$wpdb->insert($table_name, [
				'library_history_postid' => $post_id,
				'library_history_userid' => $user_id,
				'check_out_timestamp' => current_time('timestamp')
			],
			['%d', '%d','%d']
		);
	}
}

function add_rating_to_comment($author_name) {
    global $comment;

    $comment_id = $comment->comment_ID;
    $user_id = $comment->user_id;
    $post_id = $comment->comment_post_ID;
    $comment_time = $comment->comment_date;
    $rating = get_user_rating_from_history($comment_id, $user_id, $post_id, $comment_time);

    // If appearing on the dashboard, then don't need to break out of
    // pre-existing <strong> tags.
    $screen = get_current_screen();
    $is_dashboard = $screen && 'dashboard' == $screen->id;

    $html = $is_dashboard ? '' : '</strong>';

    $html .= "
				<span class='column-response'>
				    <span class='post-and-rating-wrapper'>
				        <span class='rating'>
                            ".function_exists('get_user_vote') ? get_user_vote($rating) : ''."
				        </span>
				    </span>
				</span>";


    $html .= $is_dashboard ? '' : '<strong>';
    $html .= $author_name;
    return $html;
}


add_filter( 'comment_author', 'add_rating_to_comment');

function delete_comment_and_ratings() {

    $comment_id = isset($_POST['id']) ? $_POST['id'] : null;
    $post_id = isset($_GET['post']) ? $_GET['post'] : null;

    clear_all_history($comment_id, $post_id);
}

add_filter( 'delete_comment', 'delete_comment_and_ratings');


function clear_all_history($comment_id=null, $post_id=null) {
    global $wpdb;
    $table_name = $wpdb->prefix . "ratings_history";
    $ratings_history = $wpdb->get_results("SELECT * FROM {$table_name} WHERE ratings_history_comment_id = ".$comment_id);


    $post_meta = get_post_meta($ratings_history[0]->ratings_history_post_id);

    $ratings_users = $post_meta['ratings_users'][0] - 1;
    $ratings_score = $post_meta['ratings_score'][0] - $ratings_history[0]->ratings_history_rating;
    $ratings_average = $ratings_score != 0 ? round($ratings_score/$ratings_users,2) : 0;

    update_post_meta($ratings_history[0]->ratings_history_post_id, 'ratings_users', $ratings_users);
    update_post_meta($ratings_history[0]->ratings_history_post_id, 'ratings_score', $ratings_score);
    update_post_meta($ratings_history[0]->ratings_history_post_id, 'ratings_average', $ratings_average);

    $table_name = $wpdb->prefix . "ratings_history";
    if ($comment_id) {
        $wpdb->query( "DELETE FROM {$table_name} WHERE ratings_history_comment_id = ".intval($comment_id));
    } else {
        $wpdb->query("DELETE FROM {$table_name} WHERE ratings_history_post_id = ".intval($post_id));
        $table_name = $wpdb->prefix . "library_history";
        $wpdb->query("DELETE FROM {$table_name} WHERE library_history_postid = ".intval($post_id));
    }
}

function remove_rating() {
    global $wpdb;
    if (isset($_POST['rating_id'])) {
        $rating_id = $_POST['rating_id'];
        $table_name = $wpdb->prefix . "ratings_history";
        $ratings_history = $wpdb->get_results("SELECT * FROM {$table_name} WHERE ratings_history_id = ".$rating_id);
        if ($ratings_history[0]->ratings_history_comment_id) {
            $comment_id = intval($ratings_history[0]->ratings_history_comment_id);
            wp_delete_comment($comment_id);
            clear_all_history($comment_id);
        }
    }
    wp_die();
}

add_action('wp_ajax_nopriv_remove_rating', 'remove_rating');
add_action('wp_ajax_remove_rating', 'remove_rating');