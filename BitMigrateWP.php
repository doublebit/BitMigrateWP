<?php

/**
 * Plugin Name: Bit Migrate WP
 * Plugin URI: https://github.com/doublebit/BitMigrateWP
 * Description: Automatic migration for WordPress websites
 * Author: DoubleBit
 * Version: 0.9
 * Author URI: http://www.doublebit.net/
 * License: GPL2
*/

function BMWP_check() {
  $old_url = site_url();
	$new_url = BMWP_guess_url();
	if ($old_url != $new_url) {
		BMWP_update_url($old_url, $new_url);
	}
}

function BMWP_update_url($old, $new) {
	global $wpdb;
	$queries = array (
		'content' => "UPDATE $wpdb->posts SET post_content = replace(post_content, %s, %s)",
		'excerpts' => "UPDATE $wpdb->posts SET post_excerpt = replace(post_excerpt, %s, %s)",
		'attachments' => "UPDATE $wpdb->posts SET guid = replace(guid, %s, %s) WHERE post_type = 'attachment'",
		'custom' => "UPDATE $wpdb->postmeta SET meta_value = replace(meta_value, %s, %s)",
		'guids' => "UPDATE $wpdb->posts SET guid = replace(guid, %s, %s)"
	);
	foreach ($queries as $query) {
		$result = $wpdb->query($wpdb->prepare($query, $old, $new));
	}
	$all = wp_load_alloptions();
	foreach ($all as $name => $value) {
		if (stristr($value, $old)) {
			$x = get_option($name);
			$x = BMWP_replace($old, $new, $x);
			update_option($name, $x);
		}
	}
}

function BMWP_guess_url() {
	if (defined('WP_SITEURL') && '' != WP_SITEURL) {
        $url = WP_SITEURL;
    } else {
    	$current_slug = '';
    	global $post;
    	$current_slug = get_post( $post )->post_name;
        $schema = is_ssl() ? 'https://' : 'http://';
        $remove_what = array (
        	'|/wp-admin/.*|i',
        	'|/wp-content/.*|i',
        	'|/wp-include/.*|i',
        	'|/wp-login.php.*|i',
        	'|\?.*|i'
        );
        if ($current_slug != '') {
        	$remove_what[] = '|/' . $current_slug . '.*|i';
        }
        $url = preg_replace($remove_what, '', $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }
    return rtrim($url, '/');
}

function BMWP_replace($find, $replace, $data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = BMWP_replace($find, $replace, $data[$key]);
            } else if (is_string($value)) {
				$data[$key] = str_ireplace( $find, $replace, $value);
            }
        }
    } else if (is_string($data)) {
		$data = str_ireplace($find, $replace, $data);
    }
    return $data;
}

add_action('wp', 'BMWP_check');
