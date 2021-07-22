<?php
/**
 * Plugin Name:       CF7 Coder
 * Plugin URI:        https://wordpress.org/cf7-coder
 * Description:       Add custom CSS and HTML editor for Contact Form 7 forms.
 * Version:           0.1
 * Author:            Wow-Company
 * Author URI:        https://wow-estore.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cf7-coder
 * Domain Path:       /languages
 *
 * PHP version 5.6.0
 *
 * @category    Wordpress_Plugin
 * @package     Wow_Plugin
 * @author      Dmytro Lobov <i@lobov.dev>
 * @copyright   2021 Wow-Company
 * @license     GNU Public License
 * @version     0.1
 */

if ( ! defined( "ABSPATH" ) ) {
	exit();
}

class CF7_Coder {
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, "text_domain" ] );
		add_action( "admin_enqueue_scripts", [ $this, "style_script" ] );
		add_filter( 'wpcf7_editor_panels', [ $this, 'wpcf7_editor_add_panels' ] );
		add_filter( 'wpcf7_contact_form_properties', [ $this, 'wpcf7_add_properties' ] );
		add_action( 'wpcf7_save_contact_form', [ $this, 'wpcf7_save' ] );
		add_filter( 'do_shortcode_tag', [ $this, 'wpcf7_frontend' ], 10, 4 );
		add_action( 'wpcf7_admin_misc_pub_section', [ $this, 'wpcf7_add_test_mode' ] );
	}

	// Download the folder with languages
	public function text_domain() {
		$languages_folder = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		load_plugin_textdomain( 'cf7-coder', false, $languages_folder );
	}

	// Include script and style in CF7 page
	public function style_script( $hook ) {
		$page_new = 'contact_page_wpcf7-new';
		$page     = 'toplevel_page_wpcf7';


		if ( $page == $hook || $page_new == $hook ) {

			$version = '0.1';

			wp_enqueue_script( 'code-editor' );
			wp_enqueue_style( 'code-editor' );
			wp_enqueue_script( 'htmlhint' );
			wp_enqueue_script( 'csslint' );
			wp_enqueue_script( 'jshint' );

			$url_style = plugin_dir_url( __FILE__ ) . 'assets/style.css';
			wp_enqueue_style( "coder-wpcf7", $url_style );

			$url_script = plugin_dir_url( __FILE__ ) . 'assets/script.js';
			wp_enqueue_script( "coder-wpcf7", $url_script, [ "jquery" ], $version, false );
		}

	}

	// Add panels
	function wpcf7_editor_add_panels( $panels ) {
		$panels['wpcf7-editor-panel-style'] = array(
			'title'    => esc_attr__( 'CSS', 'cf7-coder' ),
			'callback' => [ $this, 'wpcf7_editor_style_settings' ],
		);

		$panels['wpcf7-editor-panel-script'] = array(
			'title'    => esc_attr__( 'JS', 'cf7-coder' ),
			'callback' => [ $this, 'wpcf7_editor_script_settings' ],
		);

		return $panels;

	}

	// Panel for CSS
	function wpcf7_editor_style_settings( $post ) {
		?>
        <h2><?php esc_html_e( 'CSS', 'cf7-coder' ); ?></h2>
        <fieldset>

            <textarea id="wpcf7-custom-css" name="wpcf7-custom-css" cols="100" rows="8" class="large-text"
                      data-config-field="additional_style.body"><?php echo esc_textarea( $post->prop( 'wpcf7_custom_css' ) ); ?></textarea>
        </fieldset>
        <legend class="wpcf7-custom-css">
            You can use the next classes:
            <ul>
                <li><b>wpcf7</b> - for style of the form wrapper</li>
                <li><b>wpcf7-form</b> - for form style</li>
                <li><b>wpcf7-not-valid-tip</b> - field validation text </li>
                <li><b>wpcf7-response-output</b> - send status message </li>
                <li><b>wpcf7-response-output</b> - send status message </li>
            </ul>
        </legend>
		<?php
	}

	// Panel for Script
	function wpcf7_editor_script_settings( $post ) {
		?>
        <h2><?php esc_html_e( 'JS', 'cf7-coder' ); ?></h2>
        <fieldset>

            <textarea id="wpcf7-custom-js" name="wpcf7-custom-js" cols="100" rows="8"
                      class="large-text"><?php echo esc_textarea( $post->prop( 'wpcf7_custom_js' ) ); ?></textarea>
        </fieldset>
		<?php
	}


	// Add checkbox 'Test Mode' in sidebar
	public function wpcf7_add_test_mode() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : '-1';
		$checked = get_post_meta( $post_id, '_wpcf7_test_mode', true );
		?>
        <div class="misc-pub-section">
            <label class="wpcf7-test-mode">
                <input value="1" type="checkbox" name="wpcf7-test-mode" <?php checked( $checked ); ?>>
				<?php esc_html_e( 'Test Mode', 'cf7-coder' ); ?>
            </label>
        </div>
		<?php
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : '-1';
		$checked = get_post_meta( $post_id, '_wpcf7_remove_auto_tags', true );
		?>
        <div class="misc-pub-section">
            <label class="wpcf7-remove-auto-tags">
                <input value="1" type="checkbox" name="wpcf7-remove-auto-tags" <?php checked( $checked ); ?>>
				<?php esc_html_e( 'Remove Auto tags p and br', 'cf7-coder' ); ?>
            </label>
        </div>
		<?php
	}

	// Add properties for form
	function wpcf7_add_properties( $properties ) {
		$more_properties = array(
			'wpcf7_custom_css'       => '',
			'wpcf7_custom_js'        => '',
			'wpcf7_test_mode'        => '',
			'wpcf7_remove_auto_tags' => '',
		);

		return array_merge( $more_properties, $properties );

	}

	// Save custom properties
	function wpcf7_save( $contact_form ) {

		$properties = $contact_form->get_properties();

		if ( isset( $_POST['wpcf7-custom-css'] ) ) {
			$properties['wpcf7_custom_css'] = trim( sanitize_textarea_field( $_POST['wpcf7-custom-css'] ) );
		}
		if ( isset( $_POST['wpcf7-custom-js'] ) ) {
			$properties['wpcf7_custom_js'] = trim( sanitize_textarea_field( $_POST['wpcf7-custom-js'] ) );
		}

		$properties['wpcf7_test_mode']        = isset( $_POST['wpcf7-test-mode'] ) ? '1' : '';
		$properties['wpcf7_remove_auto_tags'] = isset( $_POST['wpcf7-remove-auto-tags'] ) ? '1' : '';


		$contact_form->set_properties( $properties );

	}

	// Work with Frontend
	function wpcf7_frontend( $output, $tag, $atts, $m ) {

		if ( $tag === 'contact-form-7' ) {
			$remove_tags = get_post_meta( $atts['id'], '_wpcf7_remove_auto_tags', true );
			if ( ! empty( $remove_tags ) ) {
				$output = str_replace( array( '<p>', '</p>', '<br/>' ), '', $output );;
			}
			$css = get_post_meta( $atts['id'], '_wpcf7_custom_css', true );
			if ( ! empty( $css ) ) {
				$css    = trim( preg_replace( '~\s+~s', ' ', $css ) );
				$output .= '<style>' . esc_attr( $css ) . '</style>';
			}
			$js = get_post_meta( $atts['id'], '_wpcf7_custom_js', true );
			if ( ! empty( $js ) ) {
				echo '<script type="text/javascript">' . wp_specialchars_decode( $js, ENT_QUOTES ) . '</script>';
			}

			$test_mode = get_post_meta( $atts['id'], '_wpcf7_test_mode', true );
			if ( ! empty( $test_mode ) && ! current_user_can( 'administrator' ) ) {
				$output = '';
			}
		}

		return $output;
	}

}

add_action( 'plugins_loaded', 'wpcf7_coder_load', 999 );
function wpcf7_coder_load() {
	if ( ! class_exists( 'WPCF7' ) ) {
		require_once 'class.wpcf7coder-extension-activation.php';
		$activation = new wpcf7_Coder_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		 $activation = $activation->run();
		deactivate_plugins( plugin_basename( __FILE__ ) );
	} else {
		new CF7_Coder();
	}
}
