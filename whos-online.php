<?php

/*
Plugin Name: Who's Online
Plugin URI: http://www.plymouth.edu/
Description: Sidebar widget to log when a user was last online
Version: 0.1
Author: Adam Backstrom
Author URI: http://blogs.bwerp.net/
*/

function whosonline_init() {
	add_action( 'wp_head', 'whosonline_pageoptions_js' );
	add_action( 'wp_head', 'whosonline_css' );

	wp_enqueue_script('whosonline', '/' . PLUGINDIR . '/whos-online/whos-online.js', array('jquery'));

	whosonline_update();
}
add_action('template_redirect', 'whosonline_init');

// our own ajax call
add_action( 'wp_ajax_whosonline_ajax_update', 'whosonline_ajax_update' );

// hook into p2 ajax calls, if they're there
add_action( 'wp_ajax_prologue_latest_posts', 'whosonline_update' );
add_action( 'wp_ajax_prologue_latest_comments', 'whosonline_update' );

/**
 * Update a user's "last online" timestamp.
 */
function whosonline_update() {
	if( !is_user_logged_in() )
		return null;

	global $user_ID;

	update_usermeta( $user_ID, 'whosonline_timestamp', time() );
}//end whosonline_update

/**
 * Echo json listing all authors who have had their "last online" timestamp updated
 * since the client's last update.
 */
function whosonline_ajax_update() {
	global $wpdb;

	// update timestamp of user who is checking
	whosonline_update();

	$load_time = strtotime($_GET['load_time'] . ' GMT');
	$authors = $wpdb->get_results($wpdb->prepare("SELECT user_id, meta_value AS whosonline FROM $wpdb->usermeta
		WHERE meta_key = 'whosonline_timestamp' AND meta_value > %d", $load_time));

	if( count($authors) == 0 ) {
		die( '0' );
	}

	$latest = 0;
	foreach($authors as $author) {
		if( $author->whosonline > $latest )
			$latest = $author->whosonline;

		$author->whosonline_unix = (int)$author->whosonline;
		$author->whosonline = strftime( '%d %b %Y %H:%M:%S %Z', $author->whosonline );
	}

	echo json_encode( array('authors' => $authors, 'latestupdate' => gmdate('Y-m-d H:i:s', $latest)) );
	exit;
}

function whosonline_css() {
	?><style type="text/css">
	.widget_whosonline .active { font-weight: bold; color: green; }
	.widget_whosonline .recent { }
	.widget_whosonline .ancient { font-style: italic; color: red; }
	.widget_whosonline ul li { float: none; width: auto; height: 33px; }
	.widget_whosonline h2 {}
	.widget_whosonline ul li strong {}
	.widget_whosonline ul li img.avatar { float: left; margin-right: 1ex; }
	/* kubrick style follows */
	#sidebar ul li.widget_whosonline ul li:before { content: none; }
	</style><?php
}

function whosonline_pageoptions_js() {
	global $page_options;
?><script type='text/javascript'>
// <![CDATA[
var whosonline = {
	'ajaxUrl': "<?php echo js_escape( get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php' ); ?>",
	'whosonlineLoadTime': "<?php echo gmdate( 'Y-m-d H:i:s' ); ?>",
	'getWhosonlineUpdate': '0',
	'isFirstFrontPage': "<?php echo is_home(); ?>"
};
// ]]>
</script><?php
}

/**
 * Custom version of wp_list_authors() for the whos-online plugin.
 *
 * optioncount (boolean) (false): Show the count in parenthesis next to the
 *		author's name.
 * exclude_admin (boolean) (true): Exclude the 'admin' user that is installed by
 *		default.
 * show_fullname (boolean) (false): Show their full names.
 * hide_empty (boolean) (true): Don't show authors without any posts.
 * feed (string) (''): If isn't empty, show links to author's feeds.
 * feed_image (string) (''): If isn't empty, use this image to link to feeds.
 * echo (boolean) (true): Set to false to return the output, instead of echoing.
 * avatar_size' => 
 *
 * @param array $args The argument array.
 * @return null|string The output, if echo is set to false.
 */
function whosonline_list_authors($args = '') {
	global $wpdb;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'feed_type' => '', 'echo' => true,
		'avatar_size' => 0, 'whosonline' => 0
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);

	$return = '';

	/** @todo Move select to get_authors(). */
	$authors = $wpdb->get_results("SELECT ID, user_nicename from $wpdb->users " . ($exclude_admin ? "WHERE user_login <> 'admin' " : '') . "ORDER BY display_name");

	$author_count = array();
	foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
		$author_count[$row->post_author] = $row->count;
	}

	foreach ( (array) $authors as $author ) {
		$author = get_userdata( $author->ID );
		$posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
		$name = $author->display_name;

		if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
			$name = "$author->first_name $author->last_name";

		if ( $avatar_size > 0 )
			$name = get_avatar( $author->ID, $avatar_size) . " " . $name;

		if ( !($posts == 0 && $hide_empty) ) {
			$return .= '<li>';
		}

		if ( $posts == 0 ) {
			if ( !$hide_empty )
				$link = $name;
		} else {
			$link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . sprintf(__("Posts by %s"), attribute_escape($author->display_name)) . '">' . $name . '</a>';

			if ( (! empty($feed_image)) || (! empty($feed)) ) {
				$link .= ' ';
				if (empty($feed_image))
					$link .= '(';
				$link .= '<a href="' . get_author_feed_link($author->ID) . '"';

				if ( !empty($feed) ) {
					$title = ' title="' . $feed . '"';
					$alt = ' alt="' . $feed . '"';
					$name = $feed;
					$link .= $title;
				}

				$link .= '>';

				if ( !empty($feed_image) )
					$link .= "<img src=\"$feed_image\" style=\"border: none;\"$alt$title" . ' />';
				else
					$link .= $name;

				$link .= '</a>';

				if ( empty($feed_image) )
					$link .= ')';
			}

			if ( $optioncount )
				$link .= ' ('. $posts . ')';
		}

		if ( $whosonline ) {
			$whosonline_time = get_usermeta( $author->ID, 'whosonline_timestamp' );
			if( $whosonline_time ) {
				$whosonline_time = strftime( '%d %b %Y %H:%M:%S %Z', $whosonline_time );
			} else {
				$whosonline_time = '';
			}
			$link .= '<br /><span id="whosonline-' . $author->ID . '" title="Last online timestamp">' . $whosonline_time . '</span>';
		}

		if ( !($posts == 0 && $hide_empty) )
			$return .= $link . '</li>';
	}
	if ( !$echo )
		return $return;
	echo $return;
}

function widget_whosonline_init() {

  // Check for the required plugin functions. This will prevent fatal
  // errors occurring when you deactivate the dynamic-sidebar plugin.
  if ( !function_exists('register_sidebar_widget') )
    return;

  // This is the function that outputs the Authors code.
  function widget_whosonline($args) {
    extract($args);

    echo $before_widget . $before_title . "Users" . $after_title;
?>
<ul>
<?php whosonline_list_authors('optioncount=1&exclude_admin=0&show_fullname=1&hide_empty=0&avatar_size=32&whosonline=1'); ?>
</ul>
<?php
    echo $after_widget;
  }

  // This registers our widget so it appears with the other available
  // widgets and can be dragged and dropped into any active sidebars.
  register_sidebar_widget('Who\'s Online', 'widget_whosonline');
}

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'widget_whosonline_init');
