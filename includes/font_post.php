<?php
/**
 * Font Post Type
 *
 * @package Kadence Custom Fonts
 */
function kt_font_post_type() {
	$font_labels = array(
		'name'               => __( 'Custom Fonts', 'kadence-custom-fonts' ),
		'singular_name'      => __( 'Custom Font', 'kadence-custom-fonts' ),
		'add_new'            => __( 'Add New Custom Font', 'kadence-custom-fonts' ),
		'add_new_item'       => __( 'Add New Custom Font', 'kadence-custom-fonts' ),
		'edit_item'          => __( 'Edit Custom Font', 'kadence-custom-fonts' ),
		'new_item'           => __( 'New Custom Font', 'kadence-custom-fonts' ),
		'all_items'          => __( 'All Custom Fonts', 'kadence-custom-fonts' ),
		'view_item'          => __( 'View Custom Font', 'kadence-custom-fonts' ),
		'search_items'       => __( 'Search Custom Fonts', 'kadence-custom-fonts' ),
		'not_found'          => __( 'No Custom Fonts found', 'kadence-custom-fonts' ),
		'not_found_in_trash' => __( 'No Custom Fonts found in Trash', 'kadence-custom-fonts' ),
		'parent_item_colon'  => '',
		'menu_name'          => __( 'Custom Fonts', 'kadence-custom-fonts' ),
	);

	$font_args = array(
		'labels'              => $font_labels,
		'public'              => false,
		'publicly_queryable'  => false,
		'map_meta_cap'        => true,
		'show_ui'             => true,
		'exclude_from_search' => true,
		'show_in_menu'        => false,
		'query_var'           => true,
		'rewrite'             => false,
		'has_archive'         => false,
		'show_in_rest'        => true,
		'hierarchical'        => false,
		'menu_position'       => null,
		'menu_icon'           => 'dashicons-editor-textcolor',
		'supports'            => array( 'title' ),
	);

	register_post_type( 'kt_font', $font_args );
}
add_action( 'init', 'kt_font_post_type' );
