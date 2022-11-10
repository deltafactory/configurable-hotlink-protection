<?php
/*
Plugin Name: Configurable Hotlink Protection
Plugin URI: http://wordpress.org/extend/plugins/configurable-hotlink-protection/
Description: Save bandwidth by easily blocking links to video, audio, and other files from unapproved 3rd-party sites. Requires mod_rewrite.
Version: 0.2
Author: Jeff Brand
Author URI: http://www.deltafactory.com

*/

global $df_hotlink_wp_extensions;

add_action('admin_init', array('DFHotlink', 'admin_init') );
add_action('admin_menu', array('DFHotlink', 'admin_menu') );
add_filter('pre_update_option_hotlink_extensions', array('DFHotlink', 'pre_update_option_hotlink_extensions' ) );

register_activation_hook( __FILE__, array('DFHotlink', 'activate') );
register_deactivation_hook( __FILE__, array('DFHotlink', 'deactivate') );

class DFHotlink {
	public static $title = 'Configurable Hotlink Protection';
	public static $slug = 'configurable-hotlink-protection';
	public static $marker = 'Configurable Hotlink Protection';

	public static function common_extensions() {
		//Extensions that shouldn't be on the main list.
		$excluded = array( 'php', 'html', 'htm', 'css', 'js' );

		// The following 3 lines are a fun trick to expose the list of extensions defined by WordPress.
		// It's mostly a way to be lazy and generate a thorough list of file types
		global $df_hotlink_wp_extensions;
		add_filter( 'ext2type', array(__CLASS__, 'ext2type_filter_hack') );
		wp_ext2type('');	//This populates the global $df_hotlink_wp_extensions

		$ext = array();
		foreach( $df_hotlink_wp_extensions as $branch ) {
			$ext = array_merge( $ext, $branch );
		}

		foreach( $excluded as $exc ) {
			if ( false !== $key = array_search( $exc, $ext ) ) {
				unset( $ext[$key] );
			}
		}
		sort( $ext );
		return apply_filters( 'df_hotlink_common_extensions', $ext );
	}

	public static function admin_init() {
		register_setting( 'df_hotlink', 'hotlink_domains', array( __CLASS__, 'sanitize_domains') );
		register_setting( 'df_hotlink', 'hotlink_extensions', array( __CLASS__, 'sanitize_extensions' ) );
		register_setting( 'df_hotlink', 'hotlink_allowdirectlink', 'intval' );

		add_filter( 'contextual_help_list', array( __CLASS__, 'contextual_help_list'), 10, 2 );
		add_filter( 'plugin_action_links_configurable-hotlink-protection/configurable-hotlink-protection.php', array( __CLASS__, 'plugin_action_links') );
	}

	public static function admin_menu() {
		add_options_page( self::$title, 'Hotlink Protection', 'manage_options', self::$slug, array( __CLASS__, 'hotlink_settings_page') );
	}

	public static function plugin_action_links($actions) {
		$url = admin_url('options-general.php?page=configurable-hotlink-protection');
		$html = sprintf( '<a title="Configure Hotlink Protection" href="%s" style="font-weight: bold">Settings</a>', $url );
		array_unshift( $actions, $html);
		return $actions;
	}

	public static function hotlink_settings_page() {
		require( dirname( __FILE__ ) . '/settings-page.php' );
	}

	public static function contextual_help_list($contextual_help, $screen) {
		$contextual_help['settings_page_configurable-hotlink-protection'] = file_get_contents( dirname( __FILE__ ) . '/settings-page-help.php' );
		return $contextual_help;
	}

	public static function pre_update_option_hotlink_extensions($value) {
		if ( isset( $_POST['df_common_extensions'] ) && is_array( $_POST['df_common_extensions'] ) ) {
			//$value .= "\n".implode( "\n", $_POST['df_common_extensions'] );
			$value = array_merge( $value, $_POST['df_common_extensions'] );
			$value = array_unique( $value );
		}
		return $value;
	}

	public static function sanitize_domains( $domains ) {
		if ( is_array( $domains ) ) return $domains;

		$d = explode( "\n", $domains );
		$d = array_map( 'trim', $d);
		$d = array_map( array( __CLASS__, 'clean_url'), $d );
		$d = array_filter( $d );
		return $d;
	}

	public static function sanitize_extensions( $ext ) {
		if ( is_array( $ext ) ) return $ext;

		$e = explode( "\n", $ext );
		$e = array_map( 'trim', $e);
		$e = array_unique( $e );
		$e = array_filter( $e );
		return $e;
	}

	public static function selected_extensions() {
		$ext = get_option('hotlink_extensions');
		$ext = array_map( 'trim', (array)$ext );
		$ext = array_filter( $ext );
		return $ext;
	}

	public static function hotlink_rules() {
		$rules = array(
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'RewriteBase /'
		);
		if ( get_option('hotlink_allowdirectlink') )
			$rules[] = 'RewriteCond %{HTTP_REFERER} !^$';	//Deny if referrer is not blank

		$url = self::clean_url();
		$rules[] = 'RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?'.$url.' [NC]';

		$domains = get_option('hotlink_domains');
		$domains = array_map( 'trim', (array)$domains );

		// 3rd party plugins can add themselves to the list here.
		$domains = apply_filters( 'df_hotlink_domains', $domains );

		//Remove duplicates and blanks
		$domains = array_filter( array_unique( (array)$domains ) );

		foreach ($domains as $d) {
			$rules[] = 'RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?'.$d.' [NC]';
		}

		$ext = self::selected_extensions();
		$rules[] = 'RewriteRule \.('. implode('|', $ext) .')$ - [NC,F,L]';
		$rules[] = '</IfModule>';

		return $rules;
	}

	// Emulate output of insert_with_markers() for display on settings page.
	public static function hotlink_rules_text() {
		$output = array( '# BEGIN '.self::$marker );
		$output = array_merge( $output, self::hotlink_rules() );
		$output[] = '# END '.self::$marker;

		return implode( "\n", $output );
	}

	// A really lame hack to extra the current ext2type extension list.
	public static function ext2type_filter_hack($types) {
		global $df_hotlink_wp_extensions;
		$df_hotlink_wp_extensions = $types;
		return $types;
	}

	public static function clean_url($url = null) {
		if ( null === $url ) $url = strtolower(get_bloginfo('url'));
		$url = str_replace('https://','',$url);
		$url = str_replace('http://','',$url);
		$url = str_replace('www.','',$url);
		$url = str_replace('/','',$url);
		return $url;
	}

	public static function activate() {
		$rules = get_option('hotlink_extensions') ? self::hotlink_rules() : null;

		$home_path = get_home_path();
		$htaccess_file = $home_path.'.htaccess';
		return insert_with_markers( $htaccess_file, self::$marker, $rules );
	}

	public static function deactivate() {
		$home_path = get_home_path();
		$htaccess_file = $home_path.'.htaccess';
		return insert_with_markers( $htaccess_file, self::$marker, null );
	}

}
