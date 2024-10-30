<?php
/* ==================================================
 *   functions to override pluggable.php
   ================================================== */

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.9.3
 * Based on auth_redirect() at wp-includes/pluggable.php at WP 2.8
 */
function auth_redirect() {
	global $Ktai_Style;
	nocache_headers();
	$uri = preg_replace('!^.*/wp-admin/!' , KtaiStyle::ADMIN_DIR . '/', $_SERVER['REQUEST_URI']);
	wp_redirect($Ktai_Style->get('plugin_url') . KtaiStyle::LOGIN_PAGE . '?redirect_to=' . urlencode($uri));
	exit();
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.9.3
 * Based on auth_redirect() at wp-includes/pluggable.php at WP 2.5
 */
function check_admin_referer($action = -1, $query_arg = '_wpnonce') {
	global $Ktai_Style;
	if ( !isset($Ktai_Style->admin) ) {
		$Ktai_Style->ks_die('No admin functions.');
	}
	$adminurl = strtolower($Ktai_Style->get('plugin_url') . KtaiStyle::ADMIN_DIR . '/');
	$referer = strtolower($Ktai_Style->admin->get_referer());
	$result = isset($_REQUEST[$query_arg]) ? wp_verify_nonce($_REQUEST[$query_arg], $action) : false;
	if ( !$result && (-1 != $action || strpos($referer, $adminurl) === false)) {
		$Ktai_Style->admin->nonce_ays($action);
		exit();
	}
	do_action('check_admin_referer', $action);
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.9.3
 * Based on get_currentuserinfo() at wp-includes/pluggable.php at WP 2.9.2
 */
function get_currentuserinfo() {
	global $current_user, $Ktai_Style;
	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
		return false;
	}
	if (! empty($current_user)) {
		return;
	}
	if ( !$user_id = KtaiStyle_Admin::check_session() ) {
		if ( is_admin() || empty($_COOKIE[LOGGED_IN_COOKIE]) || !$user_id = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in') ) {
			wp_set_current_user(0);
			return false;
		}
	}
	wp_set_current_user($user_id);
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
function wp_set_auth_cookie($user_id, $remember = false, $secure = false) {
	global $Ktai_Style;
	$sid = false;
	if ( isset($Ktai_Style->admin) && $user_id ) {
		$sid = $Ktai_Style->admin->set_auth_cookie($user_id, $remember, $secure);
	}
	return $sid;
}
?>