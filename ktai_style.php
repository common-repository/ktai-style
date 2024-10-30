<?php
/*
Plugin Name: Ktai Style
Plugin URI: http://wordpress.org/extend/plugins/ktai-style/
Version: 2.1.0-beta4
Description: Provides lightweight pages and simple admin interfaces for mobile phones.
Author: IKEDA Yuriko
Author URI: http://en.yuriko.net/
Text Domain: ktai_style
Domain Path: /languages
*/
define ('KTAI_STYLE_VERSION', '2.1.0');

/*  Copyright (c) 2007-2010 IKEDA Yuriko

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( (defined('WP_INSTALLING') && WP_INSTALLING) || (defined('DOING_CRON') && DOING_CRON) ) {
	return;
}

define('KTAI_DO_ECHO', true);
define('KTAI_NOT_ECHO', false);
define('KTAI_NOT_ESCAPE', false);
if (! defined('KTAI_COOKIE_PCVIEW')) :
	define ('KTAI_COOKIE_PCVIEW', 'ktai_pc_view');
endif;

if (! defined('WP_LOAD_CONF')) {
	define('WP_LOAD_CONF', 'wp-load-conf.php');
	define('WP_LOAD_PATH_STRING', 'WP-LOAD-PATH:');
}

/* ==================================================
 *   KtaiStyle class
   ================================================== */

class KtaiStyle {
	private static $wp_vers = NULL;
	private $plugin_dir;
	private $plugin_url;
	private $plugin_basename;
	private $admin_dir;
	public	$textdomain_loaded = false;
	private $encoding_converted = false;
	private static $queries = array('menu', 'view', 'img', 'kp');
	private static $host;
	public  $ktai;
	public	$encode;
	public	$theme;
	public	$admin;
	public  $config;
	public	$shrinkage;
	public  $redir;
	const TEXT_DOMAIN = 'ktai_style';
	const DOMAIN_PATH = '/languages';
	const ADMIN_AVAILABLE_WP_OLDEST = '3.1';
	const ADMIN_AVAILABLE_WP_NEWEST = false;
	const ADMIN_DIR    = 'admin';
	const LOGIN_PAGE   = 'login.php';
	const CONFIG_DIR   = 'config';
	const INCLUDES_DIR = 'inc';
	const PATCHES_DIR  = 'patches';
	const PCVIEW_QUERY = 'pcview';
	const QUOTED_STRING_REGEX = '[^\\\\>]*?(?:\\\\.[^\\\\>]*?)*';
	const DOUBLE_QUOTED_STRING_REGEX = '[^"\\\\>]*?(?:\\\\.[^"\\\\>]*?)*';

/* ==================================================
 * @param	none
 * @return	object  $this
 * @since	0.70
 */
public function __construct() {
	$this->plugin_dir = basename(dirname(__FILE__));
	$this->plugin_url = plugins_url($this->plugin_dir . '/');
	$this->plugin_basename = plugin_basename(__FILE__);
	$this->admin_dir = dirname(__FILE__) . '/' . self::ADMIN_DIR;
	$this->set_allowedtags();
	add_action('plugins_loaded', array($this, 'load_textdomain'));
	add_action('plugins_loaded', array($this, 'determine_pcview'));

	require dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/switch-content.php';
	add_action('ktai_init_mobile', array('KtaiSwitchContent', 'mobile_content'));
	add_action('ktai_init_pc', array('KtaiSwitchContent', 'pc_content'));
}

/* ==================================================
 * @param	string  $key
 * @return	mixed   $value
 */
public function get($key) {
	switch ($key) {
	case 'wp_vers':
	case 'queries':
	case 'host';
		return self::${$key};
	case 'plugin_dir':
	case 'plugin_url':
	case 'plugin_basename':
	case 'textdomain_loaded':
	case 'encoding_converted':
	case 'theme':
	case 'theme_root':
	case 'theme_root_uri':
	case 'template_dir':
	case 'template_uri':
		return $this->$key;
	default:
		return isset($this->$key) ? $this->$key : NULL;
	}
}

/* ==================================================
 * @param	string  $name
 * @return	mix     $value
 */
public function get_option($name, $return_default = false) {
	if (! $return_default) {
		$value = get_option($name);
		if (preg_match('/^ks_theme/', $name)) {
			$value = preg_replace('#^wp-content/#', '', $value);
		}
		if (false !== $value) {
			return $value;
		}
	}
	// default values 
	switch ($name) {
	case 'ks_theme':
		return 'default';
	case 'ks_date_color':
		return '#00aa33';
	case 'ks_author_color':
		return '#808080';
	case 'ks_comment_type_color':
		return '#808080';
	case 'ks_external_link_color':
		return '#660099';
	case 'ks_edit_color':
		return 'maroon';
	case 'ks_year_format':
		return 'Y-m-d';
	case 'ks_month_date_format':
		return 'n/j H:i';
	case 'ks_time_format':
		return 'H:i';
	case 'ks_theme_touch':
	case 'ks_theme_mova':
	case 'ks_theme_foma':
	case 'ks_theme_ezweb':
	case 'ks_theme_sb_pdc':
	case 'ks_theme_sb_3g':
	case 'ks_theme_willcom':
	case 'ks_theme_emobile':
	default:
		return NULL;
	}
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function load_textdomain() {
	if (! $this->textdomain_loaded) {
		load_plugin_textdomain(self::TEXT_DOMAIN, false, $this->get('plugin_dir') . self::DOMAIN_PATH);
		$this->textdomain_loaded = true;
	}
}

/* ==================================================
 * @param	none
 * @return	boolean $is_ktai
 */
public function is_ktai() {
	if ($this->ktai && ! isset($_COOKIE[KTAI_COOKIE_PCVIEW])) {
		return $this->ktai->get('operator');
	} 
	return false;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
private function set_allowedtags() {
	global $allowedposttags, $allowedtags;
	if ($allowedposttags) {
		$allowedposttags['a']['accesskey'] = array();
		$allowedposttags['a']['ktai'] = array();
		$allowedposttags['a']['ifb'] = array();
		$allowedposttags['a']['lcs'] = array();
		$allowedposttags['a']['utn'] = array();
		$allowedposttags['a']['z'] = array();
		$allowedposttags['bgsound']['loop'] = array();
		$allowedposttags['bgsound']['src'] = array();
		$allowedposttags['blink'] = array();
		$allowedposttags['form']['lcs'] = array();
		$allowedposttags['form']['utn'] = array();
		$allowedposttags['form']['z'] = array();
		$allowedposttags['hr']['color'] = array();
		$allowedposttags['img']['copyright'] = array();
		$allowedposttags['img']['ktai'] = array();
		$allowedposttags['img']['localsrc'] = array();
		$allowedposttags['img']['title'] = array();
		$allowedposttags['input']['accesskey'] = array();
		$allowedposttags['input']['checked'] = array();
		$allowedposttags['input']['emptyok'] = array();
		$allowedposttags['input']['format'] = array();
		$allowedposttags['input']['istyle'] = array();
		$allowedposttags['input']['localsrc'] = array();
		$allowedposttags['input']['maxlength'] = array();
		$allowedposttags['input']['mode'] = array();
		$allowedposttags['input']['name'] = array();
		$allowedposttags['input']['size'] = array();
		$allowedposttags['input']['type'] = array();
		$allowedposttags['input']['value'] = array();
		$allowedposttags['marquee']['behavior'] = array();
		$allowedposttags['marquee']['bgcolor'] = array();
		$allowedposttags['marquee']['direction'] = array();
		$allowedposttags['marquee']['height'] = array();
		$allowedposttags['marquee']['loop'] = array();
		$allowedposttags['marquee']['scrollamount'] = array();
		$allowedposttags['marquee']['scrolldelay'] = array();
		$allowedposttags['marquee']['width'] = array();
		$allowedposttags['object']['copyright'] = array();
		$allowedposttags['object']['declare'] = array();
		$allowedposttags['object']['data'] = array();
		$allowedposttags['object']['height'] = array();
		$allowedposttags['object']['id'] = array();
		$allowedposttags['object']['standby'] = array();
		$allowedposttags['object']['type'] = array();
		$allowedposttags['object']['width'] = array();
		$allowedposttags['param']['name'] = array();
		$allowedposttags['param']['value'] = array();
		$allowedposttags['param']['valuetype'] = array();
		$allowedposttags['select']['name'] = array();
		$allowedposttags['select']['size'] = array();
		$allowedposttags['select']['multiple'] = array();
		$allowedposttags['textarea']['istyle'] = array();
		$allowedposttags['textarea']['mode'] = array();
	}
	if ($allowedtags) {
		$allowedtags['a']['ktai'] = array();
		$allowedtags['img']['localsrc'] = array();
		$allowedtags['img']['alt'] = array();
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function determine_pcview() {
	if ( isset($this->ktai) && $this->ktai->get('pcview_enabled') && isset($_GET[self::PCVIEW_QUERY]) ) {
		$is_pcview = ($_GET[self::PCVIEW_QUERY] == 'true') ? true : false;
		setcookie(KTAI_COOKIE_PCVIEW, $is_pcview, 0, COOKIEPATH, COOKIE_DOMAIN);
		if ( COOKIEPATH != SITECOOKIEPATH ) {
			setcookie(KTAI_COOKIE_PCVIEW, $is_pcview, 0, SITECOOKIEPATH, COOKIE_DOMAIN);
		}
		$location = remove_query_arg(self::PCVIEW_QUERY, $_SERVER['REQUEST_URI']);
		wp_redirect($location);
		exit;
	}
}

/* ==================================================
 * @param	string   $version
 * @param	string   $operator
 * @return	boolean  $result
 */
public function check_wp_version($version, $operator = '>=') {
	if (! isset(self::$wp_vers)) {
		self::$wp_vers = get_bloginfo('version');
	}
	return version_compare(self::$wp_vers, $version, $operator);
}

/* ==================================================
 * @return	boolean  $result
 * @since	2.0.4
 */
public function admin_available_wp() {
	return ($this->check_wp_version(self::ADMIN_AVAILABLE_WP_OLDEST) 
	&& ( self::ADMIN_AVAILABLE_WP_NEWEST < 1 
		|| $this->check_wp_version(self::ADMIN_AVAILABLE_WP_NEWEST, '<=') )
	);
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function init_mobile() {
	require dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/theme.php';
	$this->theme = new KtaiThemes();
	add_action('sanitize_comment_cookies', array($this, 'convert_input_encodings'));
	add_filter('query_vars', array($this, 'query_vars'));
	add_action('setup_theme', array($this, 'patch_mobile_phase2'));
	add_action('setup_theme', array($this->theme, 'load_theme_function'));
	add_action('comments_template', array($this->theme, 'comments_template'));
	add_action('template_redirect', array($this, 'output'), 11);
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'locale_stylesheet');
	remove_action('wp_head', 'wp_print_scripts');
	remove_action('wp_head', 'wp_generator');
	if (file_exists($this->admin_dir)) {
		if ( $this->admin_available_wp() && $this->ktai->get('flat_rate') ) {
			require $this->admin_dir . '/pluggable-override.php'; // must be loaded before pluggable.php
			require $this->admin_dir . '/class.php';
			if ( !defined('KTAI_ADMIN_MODE') ) {
				add_action('plugins_loaded', array($this, 'check_ktai_login'));
			}
		} else {
			/* don't load admin feature */
		}
	} elseif ( !defined('KTAI_KEEP_ADMIN_ACESS') || !KTAI_KEEP_ADMIN_ACESS ) {
		// kill access to WP's admin feature
		function auth_redirect() {
			exit();
		}
		add_action('plugins_loaded', array($this, 'shutout_login'));
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.1.0
 */
public function patch_mobile_phase2() {
	include dirname(__FILE__) . '/' . KtaiStyle::PATCHES_DIR . '/mobile-phase2.php';
}

/* ==================================================
 * @param	boolean $exit
 * @return	none
 */
public function check_ktai_login($exit = false) {
	if ( class_exists('KtaiStyle_Admin') ) {
		$this->admin = new KtaiStyle_Admin;
		$user_login = $this->admin->check_session();
		$this->admin->renew_session();
	}
	$login_url = parse_url(site_url('/wp-login', 'login'));
	if (preg_match('!^' . preg_quote($login_url['path'], '!') . '($|\?|\.php)!', $_SERVER['REQUEST_URI'])) {
		if ( $exit ) {
			wp_die(__('Mobile admin feature is not available.', 'ktai_style'));
			exit;
		}
		wp_redirect($this->get('plugin_url') . self::LOGIN_PAGE);
		exit();
	}
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function shutout_login() {
	$this->check_ktai_login(true);
}

/* ==================================================
 * @param	none
 * @return	string  $charset
 * @since	2.1.0
 */
public function detect_encoding() {
	$encoding = NULL;
	if ( isset($_POST['charset_detect']) ) {
		$encoding = $this->encode->guess(stripslashes($_POST['charset_detect']));
	}
	if ( !$encoding ) {
		$encoding = $this->encode->guess_from_http(KtaiEncode::ALLOW_AUTO);
	}
	return $encoding;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	1.80
 */
public function convert_input_encodings() {
	if (isset($_GET['ks']) && !empty($_GET['ks'])) {
		$encoding = $this->encode->guess_from_http();
		if ( !$encoding ) {
			$encoding = isset($_GET['Submit']) ? $this->encode->guess($_GET['Submit'], KtaiEncode::ALLOW_AUTO) : $this->encode->get('mobile_encoding');
		}
		$_GET['s'] = $this->decode_from_ktai($_GET['ks'], $encoding, false);
	} else {
		$_GET['s'] = NULL;
	}
	if (isset($_POST['urlquery']) && isset($_POST['post_password'])) {
		parse_str(stripslashes_deep($_POST['urlquery']), $query);
		foreach($query as $k => $v) {
			$_GET[$k] = $v;
		}
	}
	if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['charset_detect']) ) {
		$_POST = $this->convert_post_encodings( $_POST, $this->encode->guess(stripslashes($_POST['charset_detect']), KtaiEncode::ALLOW_AUTO) );
		$this->encoding_converted = true;
	}
}

/* ==================================================
 * @param	array    $post
 * @param	string   $encoding
 * @return	array    $post
 * @since	1.80
 */
private function convert_post_encodings($post, $encoding) {
	if ( empty($post) ) {
		return $post;
	}
	foreach ($post as $k => $v) {
		if ( empty($v) ) {
			$post[$k] = $v;
		} elseif ( is_array($v) ) {
			$post[$k] = $this->convert_post_encodings($v, $encoding);
		} else {
			$post[$k] = $this->decode_from_ktai($v, $encoding);
		}
	}
	return $post;
}

/* ==================================================
 * @param	string  $buffer
 * @param	string  $encoding
 * @return	string  $buffer
 * @since	1.80
 */
public function decode_from_ktai($buffer, $encoding = NULL, $allow_pics = NULL) {
	if ( !$encoding ) {
		$encoding = $this->encode->get('input_encoding');
	}
	if (! $this->encode->check( (string) $buffer, $encoding)) {
		$this->ks_die(sprintf(__('Invalid character found for %s encoding', 'ktai_style'), $encoding));
		// exit;
	}
	if ( is_null($allow_pics) ) {
		$allow_pics = $this->get_option('ks_allow_pictograms');
	}
	$buffer = stripslashes($buffer);
	if ( $this->encode->similar($encoding, $this->encode->get('mobile_encoding')) ) {
		$buffer = $this->ktai->pickup_pics($buffer);
		if ( !$allow_pics ) {
			$buffer = preg_replace('!<img localsrc="[^"]*" />!', '', $buffer);
		}
	}
	$buffer = $this->encode->from_mobile($buffer, $encoding);
	if ( $buffer ) {
		$buffer = add_magic_quotes($buffer); // avoid returning empty array
	}
	return $buffer;
}

/* ==================================================
 * @param	array   $vars
 * @return	array   $vars
 * @since	2.0.0
 */
public function query_vars($vars) {
	foreach ( self::$queries as $q ) {
		$vars[] = $q;
	}
	return $vars;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.70
 */
public function output() {
	if (is_robots() || is_feed() || is_trackback()) {
		return;
	}

	require dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/template-tags.php';
	if (is_404()) {
		$this->theme->bypass_admin_404();
	}
	if (! $template = $this->theme->load_template()) {
		$this->ks_die(__("Can't display pages. Bacause mobile phone templates are collapsed.", 'ktai_style'));
		// exit;
	}

	if (ks_is_front() || ks_is_menu('comments')) {
		nocache_headers();
	}
	add_filter('ktai_raw_content', array($this->ktai, 'shrink_pre_encode'), 9);
	add_filter('ktai_encoding_converted', array($this->ktai, 'shrink_pre_split'), 5);
	add_filter('ktai_encoding_converted', array($this->ktai, 'replace_smiley'), 7);
	add_filter('ktai_encoding_converted', array($this->ktai, 'convert_pict'), 9);
	add_filter('ktai_split_page', array($this->ktai, 'shrink_post_split'), 15);
	add_action('ktai_wp_head', array($this, 'disallow_index'));
	$buffer = $this->ktai->get('preamble');
	$buffer .= ($buffer ? "\n" : '');
	ob_start();
	include $template;
	$buffer .= ob_get_contents();
	ob_end_clean();
	if ( isset($this->admin) ) {
		$this->admin->store_referer()->save_data();
		$this->admin->unset_prev_session($Ktai_Style->admin->get_sid());
	}
	$buffer = apply_filters('ktai_raw_content', $buffer);
	$buffer = $this->encode->to_mobile($buffer);
	$buffer = apply_filters('ktai_encoding_converted', $buffer);
	$buffer = apply_filters('ktai_split_page', $buffer, $this->shrinkage->get_page_num());
	$mime_type    = apply_filters('ktai_mime_type', $this->ktai->get('mime_type'));
	$iana_charset = $this->encode->iana_charset();
	if (function_exists('mb_http_output')) {
		mb_http_output('pass');
	}
	header ("Content-Type: $mime_type; charset=$iana_charset");
	echo $buffer;
	exit;
}

/* ==================================================
 * @param	string  $html
 * @return	string  $html
 */
public function filter_tags($html) {
	global $allowedposttags;
	$html = wp_kses($html, apply_filters('ktai_allowedtags', $allowedposttags));
	return $html;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function init_pc() {
	if ( defined('WP_USE_THEMES') && WP_USE_THEMES ) {
		if ( preg_match('!^(https?://[^/]*)!', get_bloginfo('url'), $host) ) {
			self::$host = $host[1];
		} else {
			$scheme = is_ssl() ? 'https://' : 'http://';
			self::$host = $scheme . esc_url($_SERVER['SERVER_NAME']);
		}
		
		add_action('wp_head', array($this, 'show_mobile_url'));
//		add_action('atom_head', array($this, 'show_mobile_url_atom_head'));
//		add_action('atom_entry', array($this, 'show_mobile_url_atom_entry'));
		add_action('rss2_ns', array($this, 'show_mobile_url_rss2_ns'));
		add_action('rss2_head', array($this, 'show_mobile_url_rss2_head'));
		add_action('rss2_item', array($this, 'show_mobile_url_rss2_item'));
		if (isset($_COOKIE[KTAI_COOKIE_PCVIEW])) {
			add_action('wp_head', array($this, 'switch_ktai_view_css'));
			add_action('wp_footer', array($this, 'switch_ktai_view'));
		}
	} elseif (is_admin())  {
		add_filter('tiny_mce_before_init', array($this, 'add_ktaistyle_tag'));
		require dirname(__FILE__) . '/' . self::INCLUDES_DIR . '/theme.php';
		require dirname(__FILE__) . '/' . self::CONFIG_DIR . '/panel.php';
		$this->config = new KtaiStyle_Config();
		add_action('in_plugin_update_message-' . $this->plugin_basename, array($this, 'add_update_notice'));
		if ( file_exists($this->admin_dir) && $this->admin_available_wp() ) {
			require $this->admin_dir . '/install.php';
			register_activation_hook(__FILE__, array($this, 'check_wp_load'));
			register_activation_hook(__FILE__, array('KtaiStyle_Install', 'install'));
			register_deactivation_hook(__FILE__, array('KtaiStyle_Install', 'uninstall'));
			if (function_exists('get_blog_list')) {
				add_action('activate_sitewide_plugin', array('KtaiStyle_Install', 'install_sitewidely'));
				add_action('deactivate_sitewide_plugin', array('KtaiStyle_Install', 'uninstall_sitewidely'));
			}
		}
	}
	add_action('setup_theme', array($this, 'patch_pc_phase2'));
	require_once dirname(__FILE__) . '/operators/pictogram-images.php';
	$this->pict_images = new KtaiPictogramImages();
	add_filter('the_content', array($this->pict_images, 'convert_pict_image'));
	add_filter('get_comment_text', array($this->pict_images, 'convert_pict_image'));
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.1.0
 */
public function patch_pc_phase2() {
	include dirname(__FILE__) . '/' . KtaiStyle::PATCHES_DIR . '/pc-phase2.php';
}

/* ==================================================
 * @param	array   $init
 * @return	array   $init
 * @since	1.80
 */
public function add_ktaistyle_tag($init) {
	if ( isset($init['extended_valid_elements']) ) {
		if ( preg_match('/\ba\[/', $init['extended_valid_elements']) ) {
			$init['extended_valid_elements'] = preg_replace('/\a\[/', 'img[ktai|', $init['extended_valid_elements']);
		}
		if ( preg_match('/\bimg\[/', $init['extended_valid_elements']) ) {
			$init['extended_valid_elements'] = preg_replace('/\bimg\[/', 'img[localsrc|', $init['extended_valid_elements']);
		}
	} else {
		$init['extended_valid_elements'] = 'a[ktai|rel|rev|charset|hreflang|tabindex|accesskey|type|name|href|target|title|class|onfocus|onblur|id|style],img[localsrc|longdesc|usemap|src|border|alt|title|hspace|vspace|width|height|align|id|class|style]';
	}
	return $init;
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function check_wp_load() {
	$wp_root = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
	if ( !file_exists($wp_root . 'wp-load.php') && !file_exists($wp_root . 'wp-config.php') && function_exists('plugins_url')) {
		$conf = dirname(__FILE__) . '/' . WP_LOAD_CONF;
		if (file_put_contents($conf, "<?php /*\n" . WP_LOAD_PATH_STRING . ABSPATH . "\n*/ ?>", LOCK_EX)) { // <?php /* syntax highiting fix */
			$stat = stat(dirname(__FILE__));
			chmod($conf, 0000666 & $stat['mode']);
		}
	}
}

/* ==================================================
 * @param   int      $post_id
 * @return	string   $url
 * @since	1.10
 */
public static function get_self_url() {
	global $wp_the_query;
	if ( is_singular() && $id = $wp_the_query->get_queried_object_id() ) {
		$url = get_permalink( $id );
	} else {
		$url = esc_url(self::$host . remove_query_arg(self::$queries), $_SERVER['REQUEST_URI']);
	}
	return apply_filters('ktai_self_url', $url);
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.10
 */
public function show_mobile_url() {
	$url = self::get_self_url();
?>
<link rel="alternate" media="handheld" type="text/html" href="<?php echo esc_attr($url); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_atom_head() {
	$url = preg_replace('!(\?feed=atom|feed/atom/?)$!', '', self::get_self_url());
?>
<link rel="alternate" x:media="handheld" type="text/html" href="<?php echo esc_attr($url); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_atom_entry() {
	$url = get_permalink();
?>
<link rel="alternate" x:media="handheld" type="text/html" href="<?php echo esc_attr($url); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_rss2_ns() {
?>
	xmlns:xhtml="http://www.w3.org/1999/xhtml"
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_rss2_head() {
	$url = preg_replace('!(\?feed=rss2|feed/rss2/?)$!', '', self::get_self_url());
?>
<xhtml:link rel="alternate" media="handheld" type="text/html" href="<?php echo esc_attr($url); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	1.40
 */
public function show_mobile_url_rss2_item() {
	$url = get_permalink();
?>
<xhtml:link rel="alternate" media="handheld" type="text/html" href="<?php echo esc_attr($url); ?>" />
<?php 
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	0.95
 */
public function switch_ktai_view_css() {
	$style = <<< E__O__T
#switch-mobile {color:white; background:gray; text-align:center; clear:both;}
#switch-mobile a, #switch-mobile a:link, #switch-mobile a:visited {color:white;}
E__O__T;
	$style = apply_filters('ktai_switch_mobile_view_css', $style);
	if ($style) {
		echo '<style type="text/css">' . $style . '</style>';
	}
}

/* ==================================================
 * @param   none
 * @return	none
 * @since	0.95
 */
public function switch_ktai_view() {
	$here = $_SERVER['REQUEST_URI'];
	$menu = '<div id="switch-mobile"><a href="' . 
	esc_attr($here . (strpos($here, '?') === false ? '?' : '&') . self::PCVIEW_QUERY . '=false') . 
	'">' . __('To Mobile view', 'ktai_style') . '</a></div>';
	$menu = apply_filters('ktai_switch_mobile_view', $menu, $here);
	echo $menu;
}

/* ==================================================
 * @param	string   $action
 * @return	string   $nonce
 */
function create_anon_nonce($action = -1) {
	$i = wp_nonce_tick();
	return substr(wp_hash($i . $action), -12, 10);
}

/* ==================================================
 * @param	string   $nonce
 * @param	string   $action
 * @return	boolean  $verified
 */
function verify_anon_nonce($nonce, $action = -1) {
	$i = wp_nonce_tick();
	// Nonce generated 0-12 hours ago
	if ( substr(wp_hash($i . $action), -12, 10) == $nonce )
		return 1;
	// Nonce generated 12-24 hours ago
	if ( substr(wp_hash(($i - 1) . $action), -12, 10) == $nonce )
		return 2;
	// Invalid nonce
	return false;
}

/* ==================================================
 * @param	string  $url
 * @return	string  $url
 */
public function strip_host($url = '/') {
	$url_parts = parse_url($url);
	$http_host = explode(':', $_SERVER['HTTP_HOST']);
	if (  isset($url_parts['host']) && $url_parts['host'] == $http_host[0]
	&& ( !isset($url_parts['port']) || $url_parts['port'] == $http_host[1] ) ) {
		$url = preg_replace('!^https?://[^/]*/?!', '/', $url);
	}
	return $url;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function disallow_index() {
	if ( ks_is_comment_post() || ks_is_redir() ) {
		echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
	}
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.5
 */
public function add_update_notice() {
	echo '<br />';
	_e('Mobile themes in <code>ktai-style/themes/*</code> are initialized to the distribution state. If you customize these themes directory, create a <code>wp-content/ktai-themes/</code> directory and move your themes to there.', 'ktai_style');
}

/* ==================================================
 * @param	string  $message
 * @param	string  $title
 * @param	boolean $show_back_link
 * @param	boolean $encoded
 * @return	none
 * based on wp_die() at wp-includes/functions() of WP 2.2.3
 */
public function ks_die($message, $title = '', $show_back_link = true, $encoded = false) {

	if ( is_wp_error( $message ) ) {
		if ( empty($title) ) {
			$error_data = $message->get_error_data();
			if ( is_array($error_data) && isset($error_data['title']) )
				$title = $error_data['title'];
		}
		$errors = $message->get_error_messages();
		switch ( count($errors) ) :
		case 0 :
			$message = '';
			break;
		case 1 :
			$message = '<p>' . $errors[0] . '</p>';
			break;
		default :
			$message = '<ul><li>' . join( '</li><li>', $errors ) . '</li></ul>';
			break;
		endswitch;
	} elseif (is_string($message) && strpos($message, '<p>') === false) {
		$message = '<p>' . $message . '</p>';
	}
	if ($show_back_link && isset($this->admin) && $referer = $this->admin->get_referer()) {
		$message .= sprintf(__('Back to <a href="%s">the previous page</a>.', 'ktai_style'), esc_attr($referer));
	}

	$header = '';
	switch ($this->is_ktai()) {
	case 'KDDI':
	case 'SoftBank':
		$header = '<style><![CDATA[ p {margin-bottom:1em;} ]]></style>';
		break;
	case 'Touch':
		$header = '<meta name="viewport" content="width=device-width,initial-scale=1.0" />';
	default:
		break;
	}
	if (! defined('KTAI_ADMIN_HEAD')) :
		$mime_type    = 'text/html';
		$this->ktai->set('mime_type', $mime_type);
		$encoding     = $this->encode->get('mobile_encoding');
		$iana_charset = $this->encode->iana_charset();
		if ( function_exists('mb_http_output') ) {
			mb_http_output('pass');
		}
		header ("Content-Type: $mime_type; charset=$iana_charset");

		if ( empty($title) ) {
			$title = __('WordPress | Error', 'ktai_style');
		}
		if ( !$encoded ) {
			$title   = $this->encode->to_mobile($title, $encoding);
			$message = $this->encode->to_mobile($message, $encoding);
		}
		echo '<?xml version="1.0" encoding="' . $iana_charset .'" ?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="<?php echo $mime_type; ?>; charset=<?php echo $iana_charset; ?>" />
<meta name="robots" content="noindex,nofollow" />
<title><?php echo esc_html($title); ?></title>
<?php echo $header; ?>
</head>
<body>
<?php endif; // KTAI_ADMIN_HEAD
$logo_url = $this->strip_host($this->get('plugin_url')) . self::INCLUDES_DIR . '/wplogo.gif';
$title = '<div><h1 id="logo"><img alt="WordPress" src="' . $logo_url . '" /></h1></div>';
$title = apply_filters('ktai_die_logo', $title, $logo_url);
echo $title, $message; ?>
</body>
</html>
<?php
	if (defined('KTAI_ADMIN_HEAD')) {
		ob_flush();
	}
	exit();
}

// ===== End of class ====================
}

/* ==================================================
 *   KS_Error class
   ================================================== */

function is_ks_error($thing) {
	return (is_object($thing) && is_a($thing, 'KS_Error'));
}

class KS_Error extends Exception {

public function setCode($code) {
	$this->code = $code;
}

// ===== End of class ====================
}

/* ==================================================
 * @param	string  $attribute
 * @return	string  $is_ktai
 */
function is_ktai($attribute = NULL) {
	global $Ktai_Style;
	switch ($attribute) {
	case 'type':
		return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('type') : false;
	case 'flat_rate':
		return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('flat_rate') : false;
	case 'search_engine':
		return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->is_search_engine() : KtaiStyle::is_search_engine();
	default:
		return $Ktai_Style->is_ktai();
	}
}

/* ==================================================
 * @param	string  $name
 * @return	mix     $value
 */
function ks_option($name) {
	return KtaiStyle::get_option($name);
}

// ==================================================
global $Ktai_Style;
$Ktai_Style = new KtaiStyle;
require dirname(__FILE__) . '/operators/base.php';
$Ktai_Style->ktai = KtaiServices::factory(); // must be out of KtaiStyle::__construct
require dirname(__FILE__) . '/' . KtaiStyle::INCLUDES_DIR . '/encode.php';
if ( is_ktai() ) {
	$Ktai_Style->encode = KtaiEncode::factory($Ktai_Style->ktai->get('charset'));
	include dirname(__FILE__) . '/' . KtaiStyle::PATCHES_DIR . '/mobile-phase1.php';
	$Ktai_Style->init_mobile();
	do_action('ktai_init_mobile');
} else {
	$Ktai_Style->encode = KtaiEncode::factory(get_bloginfo('charset'));
	include dirname(__FILE__) . '/' . KtaiStyle::PATCHES_DIR . '/pc-phase1.php';
	$Ktai_Style->init_pc();
	do_action('ktai_init_pc');
}
?>