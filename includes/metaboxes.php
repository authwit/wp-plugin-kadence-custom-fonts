<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'cmb2_render_kcf_abobe_details', 'kadence_custom_fonts_render_adobe_details', 10, 5 );
function kadence_custom_fonts_render_adobe_details( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
	//print_r($escaped_value);
	// $escaped_value = wp_parse_args(
	// 	$escaped_value,
	// 	array(
	// 		'id'       => '',
	// 		'families' => array(),
	// 	)
	// );
	echo $field_type_object->input(
		array(
			'class' => 'cmb2-text-small',
			'type' => 'text',
			'name'  => $field_type_object->_name( '[id]' ),
			'id'    => $field_type_object->_id( '_id' ),
			'value' => ( is_array( $escaped_value ) && isset( $escaped_value['id'] ) ? $escaped_value['id'] : '' ),
		)
	);

	if ( is_array( $escaped_value ) && isset( $escaped_value['families'] ) && is_array( $escaped_value['families'] ) && ! empty( $escaped_value['families'] ) ) {
		echo '<div class="font-details-container" style="padding:20px 0">';
		echo '<div style="padding:0px 0px 5px"><strong>' . esc_html__( 'Project Details', 'kadence-custom-fonts' ) . '</strong></div>';
		echo '<table class="font-details-table" style="border: 1px solid #ddd;border-spacing: 0;"><thead style="background: #eee;"><tr>';
		echo '<th style="padding:15px 10px">' . esc_html__( 'Fonts', 'kadence-custom-fonts' ) . '</th>';
		echo '<th style="padding:15px 10px">' . esc_html__( 'Variations', 'kadence-custom-fonts' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $escaped_value['families'] as $key => $value ) {
			echo '<tr>';
			echo '<td>' . esc_html( $value['title'] ) . '</td>';
			echo '<td>';
			echo esc_html( implode( ', ', $value['variations'] ) );
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}
}
/**
 * Get the font details from adobe.
 *
 * @param string $id the project id.
 */
function kadence_custom_fonts_get_adobe_font_details( $id ) {
	$abobe_url  = 'https://typekit.com/api/v1/json/kits/' . $id . '/published';
	$abode_json = wp_remote_get(
		$abobe_url,
		array(
			'timeout' => '30',
		)
	);
	if ( is_wp_error( $abode_json ) || wp_remote_retrieve_response_code( $abode_json ) !== 200 ) {
		return array();
	}

	$font_data = json_decode( wp_remote_retrieve_body( $abode_json ), true );
	$font_info = array();

	if ( isset( $font_data['kit'] ) && isset( $font_data['kit']['families'] ) && is_array( $font_data['kit']['families'] ) ) {
		foreach ( $font_data['kit']['families'] as $font_family ) {
			$font_css_name  = isset( $font_family['css_names'][0] ) ? $font_family['css_names'][0] : $font_family['slug'];
			$font_css_fallback  = isset( $font_family['css_names'] ) && is_array( $font_family['css_names'] ) ? end( $font_family['css_names'] ) : 'sans-serif';
			$font_info[ $font_family['slug'] ] = array(
				'name'   => $font_css_name,
				'title'    => $font_family['name'],
				'fallback' => $font_css_fallback,
				'weights'  => array(),
				'styles' => array(),
				'variations' => array(),
			);
			foreach ( $font_family['variations'] as $variation ) {
				$variations = str_split( $variation );
				switch ( $variations[0] ) {
					case 'n':
						$style = 'normal';
						break;
					case 'i':
						$style = 'italic';
						break;
					default:
						$style = 'normal';
						break;
				}
				if ( ! in_array( $style, $font_info[ $font_family['slug'] ]['styles'] ) ) {
					$font_info[ $font_family['slug'] ]['styles'][] = $style;
				}

				$weight = $variations[1] . '00';

				if ( ! in_array( $weight, $font_info[ $font_family['slug'] ]['weights'] ) ) {
					$font_info[ $font_family['slug'] ]['weights'][] = $weight;
				}
				if ( ! empty( $style ) && 'normal' !== $style ) {
					$variations_setting = $weight . $style;
				} else {
					$variations_setting = $weight;
				}
				if ( ! in_array( $variations_setting, $font_info[ $font_family['slug'] ]['variations'] ) ) {
					$font_info[ $font_family['slug'] ]['variations'][] = $variations_setting;
				}
			}
		}
	}
	return $font_info;
}
add_filter( 'cmb2_sanitize_kcf_abobe_details', 'kadence_custom_fonts_sanitize_adobe_details', 10, 2 );
function kadence_custom_fonts_sanitize_adobe_details( $null, $new ) {
	if ( is_array( $new ) && isset( $new['id'] ) && ! empty( $new['id'] ) ) {
		$new['id'] = sanitize_text_field( trim( $new['id'] ) );
		$new['families'] = kadence_custom_fonts_get_adobe_font_details( $new['id'] );
	} else {
		$new = '';
	}
	return $new;
}
add_filter( 'cmb2_types_esc_kcf_abobe_details', 'kadence_custom_fonts_escape_adobe_details', 10, 4 );
function kadence_custom_fonts_escape_adobe_details( $check, $meta_value, $field_args, $field_object ) {
	// if not repeatable, bail out.
	if ( ! is_array( $meta_value ) ) {
		return $check;
	}
	$new_meta_value = array();
	if ( isset( $meta_value['id'] ) ) {
		$new_meta_value['id'] = esc_attr( $meta_value['id'] );
	}
	if ( isset( $meta_value['families'] ) ) {
		$new_meta_value['families'] = $meta_value['families'];
	}

	return $new_meta_value;
}
function kadence_custom_fonts_metaboxes() {
	$prefix = '_kad_';
	$kt_font_post = new_cmb2_box(
		array(
			'id'            => 'custom_fonts_metabox',
			'title'         => __( 'Font Details', 'kadence-custom-fonts' ),
			'object_types'  => array( 'kt_font' ),
			'priority'      => 'high',
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( 'Font Type', 'kadence-custom-fonts' ),
			'id'      => $prefix . 'font_type',
			'type'    => 'select',
			'default' => 'custom',
			'options' => array(
				'custom' => __( 'Upload File', 'kadence-custom-fonts' ),
				'adobe'  => __( 'Adobe Type Kit', 'kadence-custom-fonts' ),
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'      => __( 'Font Family Name', 'kadence-custom-fonts' ),
			'desc'      => __( 'Make sure it matches exactly with the font e.g. Raleway', 'kadence-custom-fonts' ),
			'id'        => $prefix . 'font_name',
			'type'      => 'text',
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'      => __( 'Font Specific Style Name - Optional', 'kadence-custom-fonts' ),
			'desc'      => __( 'Make sure it matches exactly with the font e.g. Raleway Bold Italic', 'kadence-custom-fonts' ),
			'id'        => $prefix . 'font_style_name',
			'type'      => 'text',
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( '.woff Font file', 'kadence-custom-fonts' ),
			'desc'    => __( 'Upload .woff font file.', 'kadence-custom-fonts' ),
			'id'      => $prefix . 'font_woff',
			'type'    => 'file',
			// Optional:
			'options' => array(
				'url' => false, // Hide the text input for the url
			),
			'text'    => array(
				'add_upload_file_text' => 'Add File', // Change upload button text. Default: "Add or Upload File"
			),
			'query_args' => array(
				'type' => array(
					'font/woff',
					'application/font-woff',
					'application/x-font-woff',
					'application/octet-stream',
				),
			),
			'preview_size' => 'none', // Image size to use when previewing in the admin.
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( '.woff2 Font file', 'kadence-custom-fonts' ),
			'desc'    => __( 'Upload .woff2 font file.', 'kadence-custom-fonts' ),
			'id'      => $prefix . 'font_woff2',
			'type'    => 'file',
			// Optional:
			'options' => array(
				'url' => false, // Hide the text input for the url
			),
			'text'    => array(
				'add_upload_file_text' => 'Add Font',
			),
			// 'query_args' => array(
			// 	'type' => array(
			// 		'font/woff2|application/octet-stream|font/x-woff2',
			// 		'application/octet-stream',
			// 		'font/woff2',
			// 		'font/woff',
			// 		'application/font-woff',
			// 		'application/x-font-woff',
			// 	),
			// ),
			'preview_size' => 'none', // Image size to use when previewing in the admin.
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'      => __( 'Fallback Font Stack', 'kadence-custom-fonts' ),
			'desc'      => __( 'e.g. Helvetica, Arial', 'kadence-custom-fonts' ),
			'id'        => $prefix . 'font_fallback',
			'type'      => 'text',
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( 'Font Weight', 'kadence-custom-fonts' ),
			'desc'    => '',
			'id'      => $prefix . 'font_weight',
			'type'    => 'select',
			'default' => '400',
			'options' => array(
				'100' => __( '100', 'kadence-custom-fonts' ),
				'200' => __( '200', 'kadence-custom-fonts' ),
				'300' => __( '300', 'kadence-custom-fonts' ),
				'400' => __( '400', 'kadence-custom-fonts' ),
				'500' => __( '500', 'kadence-custom-fonts' ),
				'600' => __( '600', 'kadence-custom-fonts' ),
				'700' => __( '700', 'kadence-custom-fonts' ),
				'800' => __( '800', 'kadence-custom-fonts' ),
				'900' => __( '900', 'kadence-custom-fonts' ),
			),
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( 'Font Style', 'kadence-custom-fonts' ),
			'id'      => $prefix . 'font_style',
			'type'    => 'select',
			'default' => 'normal',
			'options' => array(
				'normal' => __( 'Normal', 'kadence-custom-fonts' ),
				'italic' => __( 'Italic', 'kadence-custom-fonts' ),
			),
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( 'Load Display Swap?', 'kadence-custom-fonts' ),
			'id'      => $prefix . 'font_swap',
			'type'    => 'select',
			'default' => 'true',
			'options' => array(
				'true' => __( 'Enable', 'kadence-custom-fonts' ),
				'false' => __( 'Disable', 'kadence-custom-fonts' ),
			),
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'      => __( 'Adobe TypeKit Project ID', 'kadence-custom-fonts' ),
			'desc'      => __( 'You can get the Project ID from your Adobe Typekit Account.', 'kadence-custom-fonts' ),
			'id'        => $prefix . 'typekit',
			'type'      => 'kcf_abobe_details',
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'adobe',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'     => __( '(For old browsers) .eot Font file', 'kadence-custom-fonts' ),
			'desc'     => __( 'Upload .eot font file. No longer needed, deprecated option.', 'kadence-custom-fonts' ),
			'id'       => $prefix . 'font_eot',
			'type'     => 'file',
			// Optional:
			'options' => array(
				'url' => false, // Hide the text input for the url
			),
			'text'    => array(
				'add_upload_file_text' => 'Add Font', // Change upload button text. Default: "Add or Upload File"
			),
			'query_args' => array(
				'type' => 'application/vnd.ms-fontobject', // Make library only display eots.
			),
			'preview_size' => 'none', // Image size to use when previewing in the admin.
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( '(For old browsers) .ttf Font file', 'kadence-custom-fonts' ),
			'desc'    => __( 'Upload .ttf font file. No longer needed, deprecated option.', 'kadence-custom-fonts' ),
			'id'      => $prefix . 'font_tiff',
			'type'    => 'file',
			'options' => array(
				'url' => false,
			),
			'text'    => array(
				'add_upload_file_text' => __( 'Add Font', 'kadence-custom-fonts' ),
			),
			'query_args' => array(
				'type' => array(
					'application/font-sfnt',
					'font/opentype',
				),
			),
			'preview_size' => 'none',
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
	$kt_font_post->add_field(
		array(
			'name'    => __( '(For old browsers) .svg Font file', 'kadence-custom-fonts' ),
			'desc'    => __( 'Upload .svg font file. No longer needed, deprecated option.', 'kadence-custom-fonts' ),
			'id'      => $prefix . 'font_svg',
			'type'    => 'file',
			'options' => array(
				'url' => false,
			),
			'text'    => array(
				'add_upload_file_text' => __( 'Add Font', 'kadence-custom-fonts' ),
			),
			'query_args' => array(
				'type' => 'image/svg+xml',
			),
			'preview_size' => 'none',
			'attributes' => array(
				'data-kadence-condition-id'    => $prefix . 'font_type',
				'data-kadence-condition-value' => 'custom',
			),
		)
	);
}
add_filter( 'cmb2_admin_init', 'kadence_custom_fonts_metaboxes' );
