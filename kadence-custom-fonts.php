<?php
/**
 * Plugin Name: Kadence Custom Fonts
 * Description: A simple plugin to add custom fonts for Kadence WP
 * Version: 1.1.5
 * Author: Kadence WP
 * Author URI: http://kadencewp.com/
 * License: GPLv2 or later
 * Text Domain: kadence-custom-fonts
 *
 * @package Kadence Custom Fonts
 */

class Kadence_Custom_Fonts {

	public static $font_query = null;
	public static $font_fallbacks = null;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}
	/**
	 * Load text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'kadence-custom-fonts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	/**
	 * On plugins loaded.
	 */
	public function on_plugins_loaded() {

		define( 'KT_CUSTOM_FONTS_PATH', realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
		define( 'KT_CUSTOM_FONTS_URL', plugin_dir_url( __FILE__ ) );
		define( 'KT_CUSTOM_FONTS_VERSION', '1.1.5' );

		// Admin Options.
		require_once KT_CUSTOM_FONTS_PATH . 'includes/cmb/init.php';
		require_once KT_CUSTOM_FONTS_PATH . 'includes/cmb2-conditionals/cmb2-conditionals.php';
		require_once KT_CUSTOM_FONTS_PATH . 'includes/metaboxes.php';
		require_once KT_CUSTOM_FONTS_PATH . 'includes/font_post.php';

		add_action( 'init', array( $this, 'on_init' ) );

	}
	/**
	 * Action on init.
	 */
	public function on_init() {
		// Add our fonts mime types.
		add_filter( 'upload_mimes', array( $this, 'extra_mime_types' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'files_ext_webp' ), 10, 4 );

		add_filter( 'cmb2__{kad_font_svg}_is_valid_img_ext', array( $this, 'no_preview_image' ) );

		add_filter( 'redux/ascend/field/typography/custom_fonts', array( $this, 'send_fonts_redux_list' ) );

		add_filter( 'redux/kadence_slider/field/typography/custom_fonts', array( $this, 'send_fonts_redux_list' ) );

		add_filter( 'redux/kadence_pricing_table/field/typography/custom_fonts', array( $this, 'send_fonts_redux_list' ) );

		add_filter( 'redux/virtue_premium/field/typography/custom_fonts', array( $this, 'send_fonts_redux_list' ) );

		add_filter( 'redux/virtue/field/typography/custom_fonts', array( $this, 'send_fonts_redux_list' ) );

		add_filter( 'redux/pinnacle/field/typography/custom_fonts', array( $this, 'send_fonts_redux_list' ) );

		add_filter( 'kadence_theme_custom_fonts', array( $this, 'send_fonts_customizer_list' ) );
		// Add to Beaver builder.
		add_filter( 'fl_theme_system_fonts', array( $this, 'send_fonts_beaver_builder_list' ) );
		add_filter( 'fl_builder_font_families_system', array( $this, 'send_fonts_beaver_builder_list' ) );
		// Add to Elementor.
		add_filter( 'elementor/fonts/groups', array( $this, 'add_font_group_elementor' ) );
		add_filter( 'elementor/fonts/additional_fonts', array( $this, 'send_fonts_elementor_list' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_weight_style_fonts' ), 100 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'add_block_fonts' ), 10 );

		add_action( 'wp_enqueue_scripts', array( $this, 'typekit_custom_font_css' ), 10 );
		add_action( 'admin_init', array( $this, 'admin_typekit_custom_font_css' ), 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'custom_font_css' ), 10 );
		add_action( 'admin_menu', array( $this, 'fonts_apperance_menu' ) );
		add_filter( 'kadence_blocks_font_family_string', array( $this, 'apply_font_fallback' ), 99 );
		add_filter( 'kadence_theme_font_family_string', array( $this, 'apply_font_fallback' ), 99 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'action_add_gutenberg_styles' ), 90 );
		add_filter( 'kadence_custom_fonts_css', array( $this, 'editor_dynamic_css' ) );
	}
	/**
	 * Add Support for fallback fonts.
	 *
	 * @param string $css_string the font string name.
	 * @param string $name the name.
	 */
	public function apply_font_fallback( $font_string ) {
		// We don't need to add the fallback if in the admin.
		if ( is_admin() ) {
			return $font_string;
		}
		// Return if already a fallback structure.
		if ( strpos( $font_string, ',') !== false ) {
			return $font_string;
		}
		$name = $font_string;
		if ( strpos( $font_string, ' ' ) !== false && strpos( $font_string, "'" ) !== false ) {
			$name = str_replace( "'", "", $name );
		}
		$font_name_arrays = (array) $this->get_fallbacks();
		if ( ! empty( $font_name_arrays ) ) {
			if ( ! empty( $font_name_arrays[ $name ] ) ) {
				$font_string = $font_string . ', ' . $font_name_arrays[ $name ];
			}
		}
		return $font_string;
	}
	/**
	 * Get fallbacks.
	 */
	public function get_fallbacks() {
		if ( is_null( self::$font_fallbacks ) ) {
			$fonts = $this->kt_font_query();
			$fallbacks = array();
			if ( $fonts ) {
				foreach ( $fonts as $font ) {
					$type = get_post_meta( $font->ID, '_kad_font_type', true );
					if ( 'adobe' != $type ) {
						$name = get_post_meta( $font->ID, '_kad_font_name', true );
						if ( ! empty( $name ) ) {
							$fallback = get_post_meta( $font->ID, '_kad_font_fallback', true );
							if ( ! empty( $fallback ) ) {
								$fallbacks[ $name ] = $fallback;
							}
						}
					}
				}
			}

			self::$font_fallbacks = $fallbacks;
		}
		return self::$font_fallbacks;
	}
	/**
	 * Add Custom Fonts to apperance menu
	 */
	public function fonts_apperance_menu() {
		add_theme_page(
			__( 'Custom Fonts', 'kadence-custom-fonts' ),
			__( 'Custom Fonts', 'kadence-custom-fonts' ),
			'manage_options',
			'edit.php?post_type=kt_font',
			false
		);
	}
	/**
	 * Add Custom Fonts to the beaver font options.
	 *
	 * @param array $fonts the fonts array.
	 */
	public function send_fonts_beaver_builder_list( $fonts ) {

		$k_font_array = array();
		$k_fonts      = $this->kt_font_query();
		if ( ! empty( $k_fonts ) ) :
			foreach ( $k_fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( 'adobe' == $type ) {
					$typekit = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $typekit ) && isset( $typekit['families'] ) && is_array( $typekit['families'] ) ) {
						foreach ( $typekit['families'] as $font_slug => $font_data ) {
							if ( ! empty( $font_slug ) ) {
								$font_name = ( ! empty( $font_data['name'] ) ? $font_data['name'] : $font_slug );
								if ( isset( $k_font_array[ $font_name ] ) ) {
									if ( isset( $font_data['weights'] ) && is_array( $font_data['weights'] ) ) {
										foreach ( $font_data['weights'] as $weight ) {
											if ( ! isset( $k_font_array[ $font_name ]['weights'][ $weight ] ) ) {
												$k_font_array[ $font_name ]['weights'][ $weight ] = $weight;
											}
										}
									}
								} else {
									$k_font_array[ $font_name ] = array(
										'fallback' => ( isset( $font_data['fallback'] ) ? $font_data['fallback'] : 'sans-serif' ),
										'weights' => array(),
									);
									if ( isset( $font_data['weights'] ) && is_array( $font_data['weights'] ) ) {
										foreach ( $font_data['weights'] as $weight ) {
											if ( ! isset( $k_font_array[ $font_name ]['weights'][ $weight ] ) ) {
												$k_font_array[ $font_name ]['weights'][ $weight ] = $weight;
											}
										}
									}
								}
							}
						}
					}
				} else {
					$name = get_post_meta( $font->ID, '_kad_font_name', true );
					$weight = get_post_meta( $font->ID, '_kad_font_weight', true );
					$fallback = get_post_meta( $font->ID, '_kad_font_fallback', true );
					if ( ! empty( $name ) ) {
						if ( isset( $k_font_array[ $name ] ) ) {
							if ( $weight ) {
								if ( ! isset( $k_font_array[ $name ]['weights'][ $weight ] ) ) {
									$k_font_array[ $name ]['weights'][ $weight ] = $weight;
								}
							}
						} else {
							$k_font_array[ $name ] = array(
								'fallback' => ( ! empty( $fallback ) ? $fallback : 'Verdana, Arial, sans-serif' ),
								'weights' => array( $weight ),
							);
						}
					}
				}
			}
		endif;

		return array_merge( $fonts, $k_font_array );
	}
	/**
	 * Add Custom Font group to elementors font list.
	 *
	 * @param array $font_groups the font groups in elementor.
	 */
	public function add_font_group_elementor( $font_groups ) {
		$new_group['kadence-custom-fonts'] = __( 'Custom Fonts', 'kadence-custom-fonts' );
		$font_groups                       = $new_group + $font_groups;
		return $font_groups;
	}
	/**
	 * Add Custom Fonts to the elementors font options.
	 *
	 * @param array $fonts the fonts array.
	 */
	public function send_fonts_elementor_list( $fonts ) {
		$k_font_array = array();
		$k_fonts      = $this->kt_font_query();
		if ( $k_fonts ) {
			foreach ( $k_fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( 'adobe' == $type ) {
					$typekit = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $typekit ) && isset( $typekit['families'] ) && is_array( $typekit['families'] ) ) {
						foreach ( $typekit['families'] as $font_slug => $font_data ) {
							if ( ! empty( $font_slug ) ) {
								$font_name = ( ! empty( $font_data['name'] ) ? $font_data['name'] : $font_slug );
								$k_font_array[ $font_name ] = 'kadence-custom-fonts';
							}
						}
					}
				} else {
					$name = get_post_meta( $font->ID, '_kad_font_name', true );
					if ( ! empty( $name ) ) {
						$k_font_array[ $name ] = 'kadence-custom-fonts';
					}
				}
			}
		}

		return array_merge( $fonts, $k_font_array );
	}
	/**
	 * Add for Kadence Blocks.
	 */
	public function add_block_fonts() {
		$fonts = $this->kt_font_query();
		if ( $fonts ) {
			$font_array = $this->build_block_font_array();
			wp_enqueue_script( 'kadence_custom_fonts_block_scripts', KT_CUSTOM_FONTS_URL . 'assets/js/custom_fonts_blocks.js', array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-api' ), KT_CUSTOM_FONTS_VERSION, true );
			wp_localize_script( 'kadence_custom_fonts_block_scripts', 'kadence_custom_fonts', $font_array );
		}
	}
	/**
	 * Add for reduct framework options.
	 */
	public function add_weight_style_fonts() {
		if ( class_exists( 'ReduxFramework' ) ) {
			wp_enqueue_script( 'kadence_custom_fonts_admin_scripts', KT_CUSTOM_FONTS_URL . 'assets/js/custom_fonts_admin.js', array( 'jquery' ), KT_CUSTOM_FONTS_VERSION, true );
			$font_array = $this->build_font_array();
			wp_localize_script( 'kadence_custom_fonts_admin_scripts', 'KCFO', $font_array );
		}
	}
	/**
	 * Get all the fonts post types.
	 */
	public static function kt_font_query() {
		if ( is_null( self::$font_query ) ) {
			$fonts = new WP_Query(
				array(
					'post_type'      => 'kt_font',
					'posts_per_page' => -1,
				)
			);

			self::$font_query = $fonts->posts;
		}
		return self::$font_query;
	}
	/**
	 * Build Block Font array.
	 */
	public function build_block_font_array() {
		$font_array = array( 'font_families' => array() );
		$fonts = $this->kt_font_query();
		if ( $fonts ) {
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( 'adobe' == $type ) {
					$typekit = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $typekit ) && isset( $typekit['families'] ) && is_array( $typekit['families'] ) ) {
						foreach ( $typekit['families'] as $font_slug => $font_data ) {
							if ( ! empty( $font_slug ) ) {
								if ( isset( $font_array['font_families'][ $font_slug ] ) ) {
									if ( isset( $font_data['weights'] ) && is_array( $font_data['weights'] ) ) {
										foreach ( $font_data['weights'] as $weight ) {
											if ( ! isset( $font_array['font_families'][ $font_slug ]['weights'][ $weight ] ) ) {
												$font_array['font_families'][ $font_slug ]['weights'][ $weight ] = $weight;
											}
										}
									}
									if ( isset( $font_data['styles'] ) && is_array( $font_data['styles'] ) ) {
										foreach ( $font_data['styles'] as $style ) {
											if ( ! isset( $font_array['font_families'][ $font_slug ]['styles'][ $style ] ) ) {
												$font_array['font_families'][ $font_slug ]['styles'][ $style ] = $style;
											}
										}
									}
								} else {
									$font_array['font_families'][ $font_slug ] = array(
										'name'    => $font_data['name'],
										'weights' => array(),
										'styles'  => array(),
									);
									if ( isset( $font_data['weights'] ) && is_array( $font_data['weights'] ) ) {
										foreach ( $font_data['weights'] as $weight ) {
											if ( ! isset( $font_array['font_families'][ $font_slug ]['weights'][ $weight ] ) ) {
												$font_array['font_families'][ $font_slug ]['weights'][ $weight ] = $weight;
											}
										}
									}
									if ( isset( $font_data['styles'] ) && is_array( $font_data['styles'] ) ) {
										foreach ( $font_data['styles'] as $style ) {
											if ( ! isset( $font_array['font_families'][ $font_slug ]['styles'][ $style ] ) ) {
												$font_array['font_families'][ $font_slug ]['styles'][ $style ] = $style;
											}
										}
									}
								}
							}
						}
					}
				} else {
					$name = get_post_meta( $font->ID, '_kad_font_name', true );
					$weight = get_post_meta( $font->ID, '_kad_font_weight', true );
					$style = get_post_meta( $font->ID, '_kad_font_style', true );
					if ( ! empty( $name ) ) {
						if ( isset( $font_array['font_families'][ $name ] ) ) {
							if ( $weight ) {
								if ( ! isset( $font_array['font_families'][ $name ]['weights'][ $weight ] ) ) {
									$font_array['font_families'][ $name ]['weights'][ $weight ] = $weight;
								}
							}
							if ( $style ) {
								if ( ! isset( $font_array['font_families'][ $name ]['styles'][ $style ] ) ) {
									$font_array['font_families'][ $name ]['styles'][ $style ] = $style;
								}
							}
						} else {
							$font_array['font_families'][ $name ] = array(
								'name' => $name,
								'weights' => array(
									$weight => $weight,
								),
								'styles' => array(
									$style => $style,
								),
							);
						}
					}
				}
			}
		}
		return $font_array;
	}
	/**
	 * Build redux font array.
	 */
	public function build_font_array() {
		$font_array = array( 'font_familes' => array() );
		$fonts = $this->kt_font_query();
		if ( $fonts ) {
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( 'adobe' == $type ) {
					$typekit = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $typekit ) && isset( $typekit['families'] ) && is_array( $typekit['families'] ) ) {
						foreach ( $typekit['families'] as $font_slug => $font_data ) {
							if ( ! empty( $font_slug ) ) {
								$font_name = ( ! empty( $font_data['name'] ) ? $font_data['name'] : $font_slug );
								if ( isset( $font_array['font_familes'][ $font_name ] ) ) {
									if ( isset( $font_data['variations'] ) && is_array( $font_data['variations'] ) ) {
										foreach ( $font_data['variations'] as $variation ) {
											if ( ! in_array( $variation, $font_array['font_familes'][ $font_name ]['styles'] ) ) {
												$font_array['font_familes'][ $font_name ]['styles'][ $variation ] = $variation;
											}
										}
									}
								} else {
									$font_array['font_familes'][ $font_name ] = array(
										'name' => $font_name,
										'styles' => array(),
									);
									if ( isset( $font_data['variations'] ) && is_array( $font_data['variations'] ) ) {
										foreach ( $font_data['variations'] as $variation ) {
											if ( ! in_array( $variation, $font_array['font_familes'][ $font_name ]['styles'] ) ) {
												$font_array['font_familes'][ $font_name ]['styles'][ $variation ] = $variation;
											}
										}
									}
								}
							}
						}
					}
				} else {
					$name = get_post_meta( $font->ID, '_kad_font_name', true );
					$weight = get_post_meta( $font->ID, '_kad_font_weight', true );
					$style = get_post_meta( $font->ID, '_kad_font_style', true );
					if ( ! empty( $style ) && $style != 'normal' ) {
						$style_setting = $weight . $style;
					} else {
						$style_setting = $weight;
					}
					if ( ! empty( $name ) ) {
						if ( isset( $font_array['font_familes'][ $name ] ) ) {
							$font_array['font_familes'][ $name ]['styles'][ $style_setting ] = $style_setting;
						} else {
							$font_array['font_familes'][ $name ] = array(
								'name' => $name,
								'styles' => array(
									$style_setting => $style_setting,
								),
							);
						}
					}
				}
			}
		}
		return $font_array;
	}
	/**
	 * Enqueue Typekit font CSS.
	 */
	public function typekit_custom_font_css() {
		$fonts = $this->kt_font_query();
		if ( $fonts ) {
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( $type == 'adobe' ) {
					$kitdetails = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $kitdetails ) && isset( $kitdetails['id'] ) && ! empty( $kitdetails['id'] ) ) {
						wp_enqueue_style( 'custom-typekit-' . $font->ID, sprintf( 'https://use.typekit.net/%s.css', $kitdetails['id'] ), array(), KT_CUSTOM_FONTS_VERSION );
					}
				}
			}
		}
	}
	/**
	 * Enqueue Typekit font CSS.
	 */
	public function admin_typekit_custom_font_css() {
		$fonts = $this->kt_font_query();
		$wp_styles = wp_styles();
		$style     = $wp_styles->query( 'wp-block-library', 'registered' );
		if ( $fonts ) {
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( $type == 'adobe' ) {
					$kitdetails = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $kitdetails ) && isset( $kitdetails['id'] ) && ! empty( $kitdetails['id'] ) ) {
						wp_enqueue_style( 'custom-typekit-' . $font->ID, sprintf( 'https://use.typekit.net/%s.css', $kitdetails['id'] ), array(), KT_CUSTOM_FONTS_VERSION );
						if ( $style ) {
							if (
								wp_style_is( 'custom-typekit-' . $font->ID, 'registered' ) &&
								! in_array( 'custom-typekit-' . $font->ID, $style->deps, true )
							) {
								$style->deps[] = 'custom-typekit-' . $font->ID;
							}
						}
					}
				}
			}
		}
	}
	/**
	 * Build Custom font css.
	 */
	public function action_add_gutenberg_styles() {
		wp_add_inline_style( 'wp-edit-blocks', trim( apply_filters( 'kadence_custom_fonts_css', '' ) ) );
	}
	/**
	 * Generates the dynamic css based on customizer options.
	 *
	 * @param string $css any custom css.
	 * @return string
	 */
	public function editor_dynamic_css( $css ) {
		$generated_css = $this->generate_editor_css();
		if ( ! empty( $generated_css ) ) {
			$css .= "\n/* Kadence Custom Fonts CSS */\n" . $generated_css;
		}
		return $css;
	}
	/**
	 * Build Custom font css.
	 */
	public function generate_editor_css() {
		$fonts = $this->kt_font_query();
		$css = '';
		if ( $fonts ) {
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( $type == 'adobe' ) {
					continue;
				}
				$name   = get_post_meta( $font->ID, '_kad_font_name', true );
				if ( ! empty( $name ) ) {
					$style_name     = get_post_meta( $font->ID, '_kad_font_style_name', true );
					$weight     = get_post_meta( $font->ID, '_kad_font_weight', true );
					$style      = get_post_meta( $font->ID, '_kad_font_style', true );
					$eot        = get_post_meta( $font->ID, '_kad_font_eot', true );
					$woff2      = get_post_meta( $font->ID, '_kad_font_woff2', true );
					$woff       = get_post_meta( $font->ID, '_kad_font_woff', true );
					$ttf        = get_post_meta( $font->ID, '_kad_font_ttf', true );
					$svg        = get_post_meta( $font->ID, '_kad_font_svg', true );
					$swap       = get_post_meta( $font->ID, '_kad_font_swap', true );
					$css .= '@font-face {';
					$css .= 'font-family: "' . $name . '";';
					if ( ! empty( $style ) ) {
						$css .= 'font-style: ' . $style . ';';
					}
					if ( ! empty( $weight ) ) {
						$css .= 'font-weight: ' . $weight . ';';
					}
					if ( ! empty( $eot ) ) {
						$css .= 'src: url("' . $eot . '");';
					}
					$css .= 'src:';
					if ( ! empty( $style_name ) ) {
						$css .= 'local("' . $style_name . '"),';
					}
					$start = '';
					if ( ! empty( $eot ) ) {
						$css .= 'url("' . $eot . '?#iefix") format("embedded-opentype")';
						$start = 'eot';
					} else if ( ! empty( $ttf ) ) {
						$css .= 'url("' . $ttf . '") format("truetype")';
						$start = 'ttf';
					} else if ( ! empty( $woff2 ) ) {
						$css .= 'url("' . $woff2 . '") format("woff2")';
						$start = 'woff2';
					} else if ( ! empty( $woff ) ) {
						$css .= 'url("' . $woff . '") format("woff")';
						$start = 'woff';
					}
					if ( ! empty( $woff2 ) && 'woff2' !== $start ) {
						$css .= ',url("' . $woff2 . '") format("woff2")';
					}
					if ( ! empty( $woff ) && 'woff' !== $start ) {
						$css .= ',url("' . $woff . '") format("woff")';
					}
					if ( ! empty( $ttf ) && 'ttf' !== $start ) {
						$css .= ',url("' . $ttf . '") format("truetype")';
					}
					if ( ! empty( $svg ) ) {
						$css .= ',url("' . $svg . '") format("svg");';
					} else {
						$css .= ';';
					}
					if ( ! empty( $swap ) && $swap == 'true' ) {
						$css .= 'font-display: swap;';
					}
					$css .= '}';
				}
			}
		}
		return $css;
	}
	/**
	 * Build Custom font css.
	 */
	public function custom_font_css() {
		$fonts = $this->kt_font_query();
		if ( $fonts ) {
			$css = '';
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( $type == 'adobe' ) {
					continue;
				}
				$name   = get_post_meta( $font->ID, '_kad_font_name', true );
				if ( ! empty( $name ) ) {
					$style_name     = get_post_meta( $font->ID, '_kad_font_style_name', true );
					$weight     = get_post_meta( $font->ID, '_kad_font_weight', true );
					$style      = get_post_meta( $font->ID, '_kad_font_style', true );
					$eot        = get_post_meta( $font->ID, '_kad_font_eot', true );
					$woff2      = get_post_meta( $font->ID, '_kad_font_woff2', true );
					$woff       = get_post_meta( $font->ID, '_kad_font_woff', true );
					$ttf        = get_post_meta( $font->ID, '_kad_font_ttf', true );
					$svg        = get_post_meta( $font->ID, '_kad_font_svg', true );
					$swap       = get_post_meta( $font->ID, '_kad_font_swap', true );
					$css .= '@font-face {';
					$css .= 'font-family: "' . $name . '";';
					if ( ! empty( $style ) ) {
						$css .= 'font-style: ' . $style . ';';
					}
					if ( ! empty( $weight ) ) {
						$css .= 'font-weight: ' . $weight . ';';
					}
					if ( ! empty( $eot ) ) {
						$css .= 'src: url("' . $eot . '");';
					}
					$css .= 'src:';
					if ( ! empty( $style_name ) ) {
						$css .= 'local("' . $style_name . '"),';
					}
					$start = '';
					if ( ! empty( $eot ) ) {
						$css .= 'url("' . $eot . '?#iefix") format("embedded-opentype")';
						$start = 'eot';
					} else if ( ! empty( $ttf ) ) {
						$css .= 'url("' . $ttf . '") format("truetype")';
						$start = 'ttf';
					} else if ( ! empty( $woff2 ) ) {
						$css .= 'url("' . $woff2 . '") format("woff2")';
						$start = 'woff2';
					} else if ( ! empty( $woff ) ) {
						$css .= 'url("' . $woff . '") format("woff")';
						$start = 'woff';
					}
					if ( ! empty( $woff2 ) && 'woff2' !== $start ) {
						$css .= ',url("' . $woff2 . '") format("woff2")';
					}
					if ( ! empty( $woff ) && 'woff' !== $start ) {
						$css .= ',url("' . $woff . '") format("woff")';
					}
					if ( ! empty( $ttf ) && 'ttf' !== $start ) {
						$css .= ',url("' . $ttf . '") format("truetype")';
					}
					if ( ! empty( $svg ) ) {
						$css .= ',url("' . $svg . '") format("svg");';
					} else {
						$css .= ';';
					}
					if ( ! empty( $swap ) && $swap == 'true' ) {
						$css .= 'font-display: swap;';
					}
					$css .= '}';
				}
			}
			if ( $css ) {
				wp_register_style( 'kadence-custom-font-css', false );
				wp_enqueue_style( 'kadence-custom-font-css' );
				wp_add_inline_style( 'kadence-custom-font-css', $css );
			}
		}
	}
	/**
	 * Send fonts to redux list.
	 *
	 * @param array $custom_fonts an array with custom fonts.
	 */
	public function send_fonts_redux_list( $custom_fonts ) {

		$fonts_custom = array( 'Custom Fonts' => array() );

		$fonts = $this->kt_font_query();
		if ( $fonts ) {
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( 'adobe' == $type ) {
					$typekit = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $typekit ) && isset( $typekit['families'] ) && is_array( $typekit['families'] ) ) {
						foreach ( $typekit['families'] as $font_slug => $font_data ) {
							$fonts_custom['Custom Fonts'][ $font_slug ] = $font_slug;
						}
					}
				} else {
					$name = get_post_meta( $font->ID, '_kad_font_name', true );
					if ( ! empty( $name ) ) {
						$fonts_custom['Custom Fonts'][ $name ] = $name;
					}
				}
			}
		} else {
			return $custom_fonts;
		}
		return $fonts_custom;
	}
	/**
	 * Send fonts to customizer list.
	 *
	 * @param array $custom_fonts an array with custom fonts.
	 */
	public function send_fonts_customizer_list( $custom_fonts ) {
		$fonts = $this->kt_font_query();
		if ( $fonts ) {
			foreach ( $fonts as $font ) {
				$type = get_post_meta( $font->ID, '_kad_font_type', true );
				if ( 'adobe' == $type ) {
					$typekit = get_post_meta( $font->ID, '_kad_typekit', true );
					if ( is_array( $typekit ) && isset( $typekit['families'] ) && is_array( $typekit['families'] ) ) {
						foreach ( $typekit['families'] as $font_slug => $font_data ) {
							if ( ! empty( $font_slug ) ) {
								$font_name = ( ! empty( $font_data['name'] ) ? $font_data['name'] : $font_slug );
								if ( isset( $custom_fonts[ $font_name ] ) ) {
									if ( isset( $font_data['variations'] ) && is_array( $font_data['variations'] ) ) {
										foreach ( $font_data['variations'] as $variation ) {
											if ( ! in_array( $variation, $custom_fonts[ $font_name ]['v'] ) ) {
												$custom_fonts[ $font_name ]['v'][] = $variation;
											}
										}
									}
								} else {
									$custom_fonts[ $font_name ] = array(
										'v' => array(),
									);
									if ( isset( $font_data['variations'] ) && is_array( $font_data['variations'] ) ) {
										foreach ( $font_data['variations'] as $variation ) {
											if ( ! in_array( $variation, $custom_fonts[ $font_name ]['v'] ) ) {
												$custom_fonts[ $font_name ]['v'][] = $variation;
											}
										}
									}
								}
							}
						}
					}
				} else {
					$name   = get_post_meta( $font->ID, '_kad_font_name', true );
					$weight = get_post_meta( $font->ID, '_kad_font_weight', true );
					$style  = get_post_meta( $font->ID, '_kad_font_style', true );
					if ( ! empty( $style ) && 'normal' !== $style ) {
						$style_setting = $weight . $style;
					} else {
						$style_setting = $weight;
					}
					if ( ! empty( $name ) ) {
						if ( isset( $custom_fonts[ $name ] ) ) {
							if ( isset( $custom_fonts[ $name ]['v'] ) && is_array( $custom_fonts[ $name ]['v'] ) && ! in_array( $style_setting, $custom_fonts[ $name ]['v'] ) ) {
								$custom_fonts[ $name ]['v'][] = $style_setting;
							}
						} else {
							$custom_fonts[ $name ] = array(
								'v' => array( $style_setting ),
							);
						}
					}
				}
			}
		}
		return $custom_fonts;
	}
	/**
	 * No preview image for meta box.
	 */
	public function no_preview_image() {
		return false;
	}
	/**
	 * Add mime types specific to font files
	 *
	 * @param array $mimes
	 *
	 * @return array
	 */
	public function extra_mime_types( $mimes ) {
		$mimes['eot'] = 'application/vnd.ms-fontobject';
		$mimes['ttf'] = 'application/x-font-ttf|application/font-sfnt|font/opentype';
		$mimes['woff']  = 'font/woff|application/font-woff|application/x-font-woff|application/octet-stream';
		$mimes['woff2'] = 'font/woff2|application/octet-stream|font/x-woff2';
		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	public function files_ext_webp( $types, $file, $filename, $mimes ) {
		if ( false !== strpos( $filename, '.webp' ) ) {
			$types['ext']  = 'webp';
			$types['type'] = 'image/webp';
		}
		if ( false !== strpos( $filename, '.ogg' ) ) {
			$types['ext']  = 'ogg';
			$types['type'] = 'audio/ogg';
		}
		if ( false !== strpos( $filename, '.woff' ) ) {
			$types['ext']  = 'woff';
			$types['type'] = 'font/woff|application/font-woff|application/x-font-woff|application/octet-stream';
		}
		if ( false !== strpos( $filename, '.woff2' ) ) {
			$types['ext']  = 'woff2';
			$types['type'] = 'font/woff2|application/octet-stream|font/x-woff2';
		}
		return $types;
	}

}

new Kadence_Custom_Fonts();

/**
 * Plugin Updates.
 */
function kadence_custom_fonts_updating() {
	require KT_CUSTOM_FONTS_PATH . 'kadence-update-checker/kadence-update-checker.php';
	$kadence_custom_fonts_update_checker = Kadence_Update_Checker::buildUpdateChecker(
		'https://kernl.us/api/v1/updates/5af519082deb0942d04555ef/',
		__FILE__,
		'kadence-custom-fonts'
	);
}
add_action( 'after_setup_theme', 'kadence_custom_fonts_updating', 1 );
