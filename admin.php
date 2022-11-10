<?php

class DFHotlink_Admin {

	public static $title = 'Configurable Hotlink Protection';
	public static $slug = 'configurable-hotlink-protection';

	static function setup() {
		add_filter( 'plugin_action_links_configurable-hotlink-protection/configurable-hotlink-protection.php', array( __CLASS__, 'plugin_action_links') );
		add_action('admin_init', array(__CLASS__, 'admin_init') );
		add_action('admin_menu', array(__CLASS__, 'admin_menu') );
		add_filter('pre_update_option_hotlink_extensions', array(__CLASS__, 'pre_update_option_hotlink_extensions' ) );

	}

	public static function admin_init() {
		register_setting( 'df_hotlink', 'hotlink_domains', array( __CLASS__, 'sanitize_domains') );
		register_setting( 'df_hotlink', 'hotlink_extensions', array( __CLASS__, 'sanitize_extensions' ) );
		register_setting( 'df_hotlink', 'hotlink_allowdirectlink', 'intval' );
	}

	public static function admin_menu() {
		add_options_page( self::$title, 'Hotlink Protection', 'manage_options', self::$slug, array( __CLASS__, 'hotlink_settings_page') );
	}

	public static function plugin_action_links($actions) {
		$url = admin_url('options-general.php?page=' . self::$slug);
		$html = sprintf( '<a title="Configure Hotlink Protection" href="%s" style="font-weight: bold">Settings</a>', $url );
		array_unshift( $actions, $html);
		return $actions;
	}

	public static function hotlink_settings_page() {
		require( __DIR__ . '/settings-page.php' );
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
		if ( is_array( $domains ) ) {
			return $domains;
		}

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
}