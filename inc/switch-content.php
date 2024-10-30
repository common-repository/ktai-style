<?php
/* ==================================================
 *   PC/Mobile Content Switcher
   ================================================== */

/*
  If you stop using Ktai Style, put only this PHP file into `wp-content/plugins/` and active "PC/Mobile Content Switcher" plugin
  */
/*
Plugin Name: PC/Mobile Content Switcher
Plugin URI: http://wordpress.org/extend/plugins/ktai-style/
Version: 1.0.0
Description: Strip [mobile-only] block from the content and delete ktai attribute from anchor elements.
Author: IKEDA Yuriko
Author URI: http://en.yuriko.net/
*/

class KtaiSwitchContent {
	const PC_ONLY_SHORTCODE     = 'pc-only';
	const MOBILE_ONLY_SHORTCODE = 'mobile-only';
	const MOBILE_LINK_ATTRIBUTE = 'ktai';
	const QUOTED_STRING_REGEX = '[^\\\\>]*?(?:\\\\.[^\\\\>]*?)*';

public function __construct() {
	add_action('init', array($this, 'pc_content'));
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function mobile_content() {
	add_shortcode(self::PC_ONLY_SHORTCODE,     array(__CLASS__, 'strip_block'));
	add_shortcode(self::MOBILE_ONLY_SHORTCODE, array(__CLASS__, 'keep_block'));
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function pc_content() {
	add_shortcode(self::PC_ONLY_SHORTCODE,     array(__CLASS__, 'keep_block'));
	add_shortcode(self::MOBILE_ONLY_SHORTCODE, array(__CLASS__, 'strip_block'));
	add_action('the_content', array(__CLASS__, 'strip_mobile_link'));
}

/* ==================================================
 * @param	array   $attr
 * @param	string  $content
 * @return	string  $content
 * @since	2.1.0
 */
public function strip_block($attr, $content = null) {
	return '';
}

/* ==================================================
 * @param	array   $attr
 * @param	string  $content
 * @return	string  $content
 * @since	2.1.0
 */
public function keep_block($attr, $content = null) {
	return $content;
}

/* ==================================================
 * @param	string  $content
 * @return	string  $content
 * @since	2.1.0
 */
public function strip_mobile_link($content) {
	$content = preg_replace('!<a ([^>]*?)\b' . self::MOBILE_LINK_ATTRIBUTE . '=([\'"])' . self::QUOTED_STRING_REGEX . '\\1([^>]*?)>!s', '<a $1$2>', $content);
	return $content;
}

// ===== End of class ====================
}

global $KtaiSwitchContent;
if ( !defined('KTAI_STYLE_VERSION') ) {
	$KtaiSwitchContent = new KtaiSwitchContent;
}
?>