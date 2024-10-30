<?php
/* ==================================================
 *   Patches to other plugins (at loading Ktai Style)
   ================================================== */

/* ==================================================
 * Keep access to admin screen if not exists ktai style admin directory
 */
if (file_exists(WP_PLUGIN_DIR . '/wphone') || file_exists(WP_PLUGIN_DIR . '/mobileadmin')) {
	define ('KTAI_KEEP_ADMIN_ACESS', true);
}

/* ==================================================
 * Shrink FireStats Images
 */
if (defined('FS_WORDPRESS_PLUGIN_VER')):
	global $Ktai_Flags, $Ktai_Browsers;
	$Ktai_Flags = array(
		'jp' => 237, 'us' => 90,  'es' => 366, 'ru' => 367, 'fr' => 499,
		'de' => 700, 'it' => 701, 'gb' => 702, 'cn' => 703, 'kr' => 704, 
	);
	$Ktai_Browsers = array(
		'macos' => 434, 'linux' => 252, 'debian' => 190, 'java' => 93,
		'docomo' => 'd109',
	);
	function ks_shrink_firestat_images($return) {
		global $Ktai_Flags, $Ktai_Browsers;
		if (is_ktai() == 'Unknown') {
			return $return;
		}
		if (preg_match("|<img src='[^']*plugins/firestats/img/flags/(\w+)\.png' alt='([^']*)' [^>]*class='fs_flagicon' ?/>|", $return, $match) && isset($Ktai_Flags[$match[1]])) {
			$return = str_replace($match[0], '<img localsrc="' . $Ktai_Flags[$match[1]] . '" alt="' . $match[2] . '" />', $return);
		}
		if (preg_match("|<img src='[^']*plugins/firestats/img/browsers/(\w+)\.png' alt='([^']*)' [^>]*class='fs_browsericon' ?/>|", $return, $match) && isset($Ktai_Browsers[$match[1]])) {
			$return = str_replace($match[0], '<img localsrc="' . $Ktai_Browsers[$match[1]] . '" alt="' . $match[2] . '" />', $return);
		}
		return $return;
	}
	add_filter('get_comment_author_link', 'ks_shrink_firestat_images', 101);
endif;

/* ==================================================
 * Disable WP-SpamFree
 */
if ( !class_exists('wpSpamFree') && !ks_option('ks_keep_wpspamfree') ):
	class wpSpamFree {
		public function __construct() {
			return;
		}
	}
endif;
?>