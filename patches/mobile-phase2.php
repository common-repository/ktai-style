<?php
/* ==================================================
 *   Patches to other plugins (at setup_theme)
   ================================================== */

/* ==================================================
 * Erase Location URL for Ktai Location
 */
function ks_erase_location_url($content) {
	return preg_replace('!\s*<div class="([-. \w]+ +)?locationurl( +[-. \w]+)?">.*?</div>!se', '"$1$2" ? "<div class=\"$1$2\">$3</div>" : ""', $content);
}
add_filter('the_content', 'ks_erase_location_url', 88);

/* ==================================================
 * Disable title-replace by All in One SEO Pack 
 */
if ( !ks_option('ks_keep_allinoneseopack') ) :
	global $aiosp;
	if (isset($aiosp) && is_object($aiosp)) {
		remove_action('wp_head', array($aiosp, 'wp_head'));
		remove_action('template_redirect', array($aiosp, 'template_redirect'));
	}
endif;

/* ==================================================
 * Insert ks_fix_encoding_form() for Contact Form 7
 */
if ( defined('WPCF7_VERSION') ) {
	function ks_remove_fragment($url) {
		return preg_replace('/#[^#]*$/', '', $url);
	}
	add_filter('wpcf7_form_action_url', 'ks_remove_fragment');
	function ks_add_fix_encoding_form($form) {
		return $form . ks_fix_encoding_form(false);
	}
	add_filter('wpcf7_form_elements', 'ks_add_fix_encoding_form');
}

/* ==================================================
 * Disable fortysix-mobile
 */
if ( function_exists('fsmb_response_mobile') ) {
	remove_action('template_redirect', 'fsmb_response_mobile', 1);
}

/* ==================================================
 * Disable Disqus comment system
 */
if ( defined('DISQUS_URL') && !ks_option('ks_keep_disqus') ) {
	remove_filter('comments_template', 'dsq_comments_template');
	remove_filter('comments_number', 'dsq_comments_number');
	remove_filter('get_comments_number', 'dsq_get_comments_number');
	remove_filter('bloginfo_url', 'dsq_bloginfo_url');
	remove_action('loop_start', 'dsq_loop_start');
	remove_action('loop_end', 'dsq_comment_count');
	remove_action('wp_footer', 'dsq_comment_count');
}

/* ==================================================
 * Disable WP-FollowMe
 */
if ( function_exists('wp_followme_scripts') && !ks_option('ks_keep_wpfollowme') ):
	remove_action('init', 'wp_followme_scripts');
	remove_action('wp_head', 'wp_followme_css');
	remove_action('get_footer', 'show_followme');
endif;
?>