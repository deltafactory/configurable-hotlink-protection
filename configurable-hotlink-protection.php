<?php
/*
Plugin Name: Configurable Hotlink Protection
Plugin URI: http://wordpress.org/extend/plugins/configurable-hotlink-protection/
Description: Save bandwidth by easily blocking links to video, audio, and other files from unapproved 3rd-party sites. Requires mod_rewrite.
Version: 0.2
Author: Jeff Brand
Author URI: http://www.deltafactory.com

*/


class DFHotlink {
	public static $marker = 'Configurable Hotlink Protection';

	static $extensions;

	static function setup() {
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		if ( is_admin() ) {
			require( __DIR__ . '/admin.php' );
			DFHotlink_Admin::setup();
		}
	}

	public static function common_extensions() {
		//Extensions that shouldn't be on the main list.
		$excluded = array( 'php', 'html', 'htm', 'css', 'js' );

		$ext = array();
		foreach( self::gather_extensions() as $branch ) {
			$ext = array_merge( $ext, $branch );
		}

		$ext = array_diff( $ext, $excluded );
		sort( $ext );
		return apply_filters( 'df_hotlink_common_extensions', $ext );
	}

	public static function selected_extensions() {
		$ext = get_option('hotlink_extensions', array() );
		$ext = array_map( 'trim', (array)$ext );
		$ext = array_filter( $ext );
		return $ext;
	}

	public static function hotlink_domains() {
		$domains = get_option('hotlink_domains', [] );
		if ( empty( $domains ) ) {
			$domains = [];
		}

		return $domains;
	}

	public static function hotlink_rules() {

		// Not protecting anything..
		$ext = self::selected_extensions();
		if ( empty( $ext ) ) {
			return [];
		}

		$rules = array(
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'RewriteBase /'
		);
		if ( get_option('hotlink_allowdirectlink') )
			$rules[] = 'RewriteCond %{HTTP_REFERER} !^$';	//Deny if referrer is not blank

		$url = self::clean_url();
		$rules[] = 'RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?'.$url.' [NC]';

		$domains = array_map( 'trim', self::hotlink_domains() );

		// 3rd party plugins can add themselves to the list here.
		$domains = apply_filters( 'df_hotlink_domains', $domains );

		//Remove duplicates and blanks
		$domains = array_filter( array_unique( (array)$domains ) );

		foreach ($domains as $d) {
			$rules[] = 'RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?'.$d.' [NC]';
		}

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

	// Gather a list of known file types provided in WP core via the ext2type extension list.
	public static function gather_extensions() {
		// Run once.
		if ( empty( self::$extensions ) ) {
			$cb = array(__CLASS__, 'gather_extensions_filter');
			add_filter( 'ext2type', $cb );
			wp_ext2type('');
			remove_filter( 'ext2type', $cb );
		}

		return self::$extensions;
	}

	public static function gather_extensions_filter( $types ) {
		self::$extensions = $types;
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
		$rules = self::hotlink_rules();

		if ( empty( $rules ) ) {
			return false;
		}

		return insert_with_markers( self::htaccess_path(), self::$marker, $rules );
	}

	public static function deactivate() {
		return insert_with_markers( self::htaccess_path(), self::$marker, null );
	}

	static function htaccess_path() {
		return get_home_path() . '.htaccess';
	}
}


DFHotlink::setup();