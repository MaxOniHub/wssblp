<?php

function wssblp_register_post_type() {

	global $wp_rewrite;
	$wp_rewrite->flush_rules();

    $singular = 'Book';
    $plural = 'Books';
    $slug = str_replace( ' ', '_', strtolower( $plural ) );
	
	$labels = [
        'name'                       => $plural,
        'singular_name'              => $singular,
        'search_items'               => 'Search ' . $plural,
        'popular_items'              => 'Popular ' . $plural,
        'all_items'                  => 'All ' . $plural,
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => 'Edit ' . $singular,
        'update_item'                => 'Update ' . $singular,
        'add_new_item'               => 'Add New ' . $singular,
        'new_item_name'              => 'New ' . $singular . ' Name',
        'separate_items_with_commas' => 'Separate ' . $plural . ' with commas',
        'add_or_remove_items'        => 'Add or remove ' . $plural,
        'choose_from_most_used'      => 'Choose from the most used ' . $plural,
        'not_found'                  => 'No ' . $plural . ' found.',
        'menu_name'                  => $plural,
    ];
    $args = [
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'show_in_nav_menus'   => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 10,
        'menu_icon'           => 'dashicons-book-alt',
        'can_export'          => true,
        'delete_with_user'    => false,
        'hierarchical'        => false,
        'has_archive'         => true,
        'query_var'           => true,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
	    'rewrite'             =>
		[
            'slug' 			  => $slug,
            'with_front'      => true,
            'pages'           => true,
            'feeds'           => true,
        ],
        'supports'            => [
            'title',
            'thumbnail',
            'comments',
        /*  'author',
            'editor',

        */
        ]
    ];

    register_post_type('book', $args);
}
add_action('init', 'wssblp_register_post_type');


function wssblp_load_templates( $original_template ) {
    if (get_query_var('post_type') == 'book') {


        if (is_archive() || is_search()) {
            if (file_exists(get_stylesheet_directory() . '/archive-book.php')) {
                return get_stylesheet_directory() . '/archive-book.php';
            } else {
                return plugin_dir_path(__FILE__) . 'templates/archive-book.php';
            }
        } else {
            if (file_exists(get_stylesheet_directory() . '/single-book.php')) {
                return get_stylesheet_directory() . '/single-book.php';
            } else {
                return plugin_dir_path(__FILE__) . 'templates/single-book.php';
            }
        }
    }
    return $original_template;
}
add_action( 'template_include', 'wssblp_load_templates' );




### Function Show Custom Columns in WP-Admin
add_action('manage_posts_custom_column', 'add_wssbpl_column_content');
add_filter('manage_posts_columns', 'add_wssbpl_column');
add_action('manage_pages_custom_column', 'add_wssbpl_column_content');
add_filter('manage_pages_columns', 'add_wssbpl_column');


function add_wssbpl_column($defaults) {

    global $post_type;
    if ($post_type == 'book') {
        $custom_fields['cb'] = 'cb';
        $custom_fields['title'] = 'Book Name';
        $custom_fields['writer'] = 'Author';
        $custom_fields['isbn_10'] = 'ISBN 10';
        $custom_fields['isbn_13'] = 'ISBN 13';
        $custom_fields['date_added'] = 'Date Added';
        $custom_fields['status'] = 'Status';
        $custom_fields['user'] = 'User';
        $custom_fields['date'] = 'Date Published';

        unset($defaults['comments']);

        $defaults = $custom_fields + $defaults;
    }
    return $defaults;
}
function add_wssbpl_column_content($column_name) {
    global $post;

    switch ($column_name) {
        case 'writer':
            echo $post->author;
            break;
        case 'isbn_10':
            echo $post->isbn_10;
            break;
        case 'isbn_13':
            echo $post->isbn_13;
            break;
        case 'date_added':
            echo $post->date_added;
            break;
        case 'user':
            $user = get_user_by('id', $post->user);
            echo $user->user_login;
            break;
        case 'status':
            echo $post->status;
            break;
    }
}


function custom_comment_template($comment_template)
{
	global $post;
	if (!(is_singular() && (have_comments() || 'open' == $post->comment_status))) {
		return;
	}
	if ($post->post_type == 'book') {
		return plugin_dir_path(__FILE__) . 'templates/_comments.php';
	}
}

add_filter("comments_template", "custom_comment_template");



