<?php
/* これは文字化け防止のための日本語文字列です。
   このソースファイルは UTF-8 で保存されています。
   Above is a Japanese strings to avoid charset mis-understanding.
   This source file is saved with UTF-8.
 */

/* ==================================================
 *   KtaiServices class
   ================================================== */

class KtaiServices {
	private $base;
	protected $theme;
	protected $user_agent;
	protected $search_engine;
	protected $operator = '(Unknown)';
	protected $type = 'N/A';
	protected $pictogram = NULL;
	protected $pictogram_flipped = NULL;
	protected $networks = NULL;
	protected $flat_rate = true;
	protected $use_redir = false;
	protected $show_plugin_icon = false;
	protected $pcview_enabled = true;
	protected $admin_enabled = true;
	protected $term_name = '';
	protected $term_ID = '';
	protected $usim_ID = '';
	protected $sub_ID = '';
	protected $sub_ID_available = false;
	protected $cookie_available = true;
	protected $ext_css_available = true;
	protected $available_js_version = '3.0';
	protected $textarea_size = 50000;
	protected $page_size = 50000;
	protected $cache_size = 524288;
	protected $screen_width = 240;
	protected $screen_height = 320;
	protected $charset = 'UTF-8';
	protected $mime_type = 'text/html';
	protected $preamble = '<?xml version="1.0" encoding="__CHARSET__"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'; // <?php /* syntax highiting fix */
	protected $xhtml_head = '<html xmlns="http://www.w3.org/1999/xhtml">';
	protected static $search_ip;
	const DEFAULT_CHARSET = 'SJIS';

/* ==================================================
 * @param	none
 * @return	object  $ktai
 */
public static function factory($ua = NULL) {
	$ktai = NULL;
	$ua = $ua ? $ua : $_SERVER['HTTP_USER_AGENT'];
	if ( isset($_GET['preview']) && isset($_GET['mobile']) ) {
		$ua = 'Ktai_Theme_Preview';
		$ktai = new KtaiService_Preview($ua);
	} elseif (preg_match('!\b(iP(hone|od));!', $ua, $name) && ks_option('ks_theme_touch')) {
		$ktai = new KtaiService_iPhone($ua);
		$ktai->set('term_name', $name[1]);
	} elseif (preg_match('!\b(Android) !', $ua, $name) && ks_option('ks_theme_touch')) {
		$ktai = new KtaiService_Android($ua);
		$ktai->set('term_name', $name[1]);
	} elseif (preg_match('!^(BlackBerry[0-9a-z]+)/!', $ua, $name) && ks_option('ks_theme_touch')) {
		$ktai = new KtaiService_BlackBerry($ua);
		$ktai->set('term_name', $name[1]);
	} elseif (preg_match('!\bWindows Phone OS \d!', $ua) && ks_option('ks_theme_touch')) {
		$ktai = new KtaiService_WindowsPhone($ua);
		$ktai->set('term_name', 'Windows Phone');
	} elseif (preg_match('!^DoCoMo/1!', $ua)) {
		require_once dirname(__FILE__) . '/i-mode.php';
		$ktai = new KtaiService_imode_mova($ua);
	} elseif (preg_match('!^DoCoMo/2!', $ua)) {
		require_once dirname(__FILE__) . '/i-mode.php';
		if (preg_match('/\(c(\d+);/', $ua, $cache) && $cache[1] >= 500) {
			$ktai = new KtaiService_imode_Browser2($ua);
		} else {
			$ktai = new KtaiService_imode_FOMA($ua);
		}
	} elseif (preg_match('/(DDIPOCKET|WILLCOM);/', $ua)) {
		require_once dirname(__FILE__) . '/willcom.php';
		$ktai = new KtaiService_WILLCOM($ua);
	} elseif (preg_match('!^(emobile|Huawei|IAC)/!', $ua)) {
		require_once dirname(__FILE__) . '/emobile.php';
		$ktai = new KtaiService_EMOBILE($ua);
	} elseif (preg_match('!^J-(PHONE|EMULATOR)/!', $ua)) { // The service is stopped, but need for mobile search engine
		require_once dirname(__FILE__) . '/softbank.php';
		$ktai = new KtaiService_Softbank_PDC($ua);
	} elseif (preg_match('!^(Vodafone/|SoftBank/|[VS]emulator/)!', $ua)) {
		require_once dirname(__FILE__) . '/softbank.php';
		$ktai = new KtaiService_Softbank_3G($ua);
	} elseif (preg_match('!^MOT(EMULATOR)?-\w+/!', $ua)) {
		if ( isset($_SERVER['HTTP_X_JPHONE_MSNAME']) ) {
			require_once dirname(__FILE__) . '/softbank.php';
			$ktai = new KtaiService_Softbank_3G($ua);
		} else {
			$ktai = new KtaiService_General($ua);
		}
	} elseif (preg_match('/^KDDI-/',$ua)) {
		require_once dirname(__FILE__) . '/ezweb.php';
		$ktai = new KtaiService_EZweb_WAP2($ua);
	} elseif (preg_match('/^UP\.Browser/',$ua)) {
		require_once dirname(__FILE__) . '/ezweb.php';
		$ktai = new KtaiService_EZweb_HDML($ua);
	} elseif (preg_match('/\b(Windows CE); ?(.*)$/', $ua, $specs)) {
		$ktai = new KtaiService_WindowsCE($ua, $specs);
	} elseif (preg_match('!\(PDA; (SL-\w+)/!', $ua, $name)) {
		$ktai = new KtaiService_General_Japan($ua);
		$ktai->set('term_name', 'Zaurus ' . $name[1]);
	} elseif (preg_match('!^sharp pda browser/.*\((MI-\w+)/!', $ua, $name)) { // MI Zaurus
		$ktai = new KtaiService_General_Japan($ua);
		$ktai->set('term_name', 'Zaurus ' . $name[1]);
	} elseif (preg_match('!\(web dayomon/\w+; Zaurus (MI-\w+)/!', $ua, $name)) { // MI Zaurus
		$ktai = new KtaiService_General_Japan($ua);
		$ktai->set('term_name', 'Zaurus ' . $name[1]);
	} elseif (preg_match('!(^Nokia\w+|^SAMSUNG\b|Opera Mini|PalmOS\b)!', $ua, $name)) {
		$ktai = new KtaiService_General($ua);
		$ktai->set('term_name', $name[1]);
	} elseif (preg_match('/\bOpera Mobi\b[^)]*\) ?(\w*)/', $ua, $name)) {
		$ktai = new KtaiService_General($ua);
		$ktai->set('term_name', $name[1]); // S21HT
		if ( !empty($name[1]) && preg_match('!SHARP/([^;]*)!', $user_agent, $name)) { // W-ZERO3 (modified)
			$ktai->set('term_name', $name[1]);
		}
	} elseif (preg_match('/\(PSP \(PlayStation Portable\);/', $ua)) {
		$ktai = new KtaiService_General($ua);
		$ktai->set('term_name', 'PlayStation Portable');
	} elseif (preg_match('!SONY/COM!', $ua)) {
		$ktai = new KtaiService_General($ua);
		$ktai->set('term_name', 'Somy mylo');
	} elseif (preg_match('/(\bNitro\) Opera|Nintendo (\w+);)/', $ua, $type)) {
		$ktai = new KtaiService_General($ua);
		$ktai->set('term_name', isset($type[2]) ? "Nintendo $type[2]" : 'Nintendo DS');
	} elseif (preg_match('!^mixi-mobile-converter/!', $ua)) {
		$ktai = new KtaiService_Converter($ua);
		$ktai->set('term_name', 'mixi Mobile');
	}
	$ktai = apply_filters('ktai_detect_agent', $ktai, $ua);
	if ($ktai) {
		if (preg_match('#\b(Googlebot-Mobile)/#', $ua, $name)
		||  preg_match('#\b(Y!J-(SRD|MBS))/#', $ua, $name)
		||  preg_match('#\b(LD_mobile_bot);#', $ua, $name)
		||  preg_match('#(ichiro/mobile goo);#', $ua, $name)
		||  preg_match('#\((symphonybot\d\.froute\.jp);#', $ua, $name)
		||  preg_match('#\b(moba-crawler);#', $ua, $name)
		||  preg_match('#\b(BaiduMobaider)/#', $ua, $name)
		||  preg_match('#\b(Hatena-Mobile-Gateway)/#', $ua, $name)
		) {
			$ktai->set('search_engine', $name[1]);
			$ktai->set('admin_enabled', false);
		}
	}
	return $ktai;
}

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 */
public function __construct($user_agent) {
	global $Ktai_Style;
	$this->base = $Ktai_Style;
	$this->user_agent = $user_agent;
	if (empty($this->theme)) {
		$this->theme = ks_option('ks_theme');
	}
	self::$search_ip = array(
		// http://googlejapan.blogspot.com/2008/05/google.html
		'72.14.199.0/25',
		'209.85.238.0/25',
		// http://help.yahoo.co.jp/help/jp/search/indexing/indexing-27.html
		'124.83.159.146-124.83.159.185',
		'124.83.159.224-124.83.159.247',
		// http://helpguide.livedoor.com/help/search/qa/grp627
		'203.104.254.0/24',
		// http://help.goo.ne.jp/help/article/1142/
		'210.150.10.32/27',
		'203.131.250.0/24',
		// http://search.froute.jp/howto/crawler.html
		'60.43.36.253/32',
		// http://crawler.dena.jp/
		'202.238.103.126/32',
		'202.213.221.9/32',
		// http://www.baidu.jp/spider/
		'119.63.195.0/24',
	);
}

/* ==================================================
 * @param	string $key
 * @return	mix    $value
 */
public function get($key) {
	switch ($key) {
	case 'iana_charset':
		return $this->base->encode->iana_charset($this->charset);
	case 'preamble':
		return str_replace('__CHARSET__', $this->get('iana_charset'), $this->preamble);
	case 'term_name':
		return isset($this->term_name) ? $this->term_name : 'N/A';
	case 'sub_ID':
		return $this->sub_ID_available ? $this->sub_ID : NULL;
	default:
		return isset($this->$key) ? $this->$key : NULL;
	}
}

/* ==================================================
 * @param	string  $key
 * @param	mix     $value
 * @return	mix     $value
 */
public function set($key, $value = NULL) {
	switch ($key) {
	case 'term_ID':
	case 'usim_ID':
	case 'sub_ID':
		$value = NULL;
		break;
	default:
		if (is_null($value)) {
			unset($this->$key);
		} else {
			$this->$key = $value;
		}
	}
	return $value;
}

/* ==================================================
 * @param	none
 * @return	boolean $is_search_engine
 */
public function is_search_engine() {
	return isset($this) && isset($this->search_engine) ? $this->search_engine : NULL;
}

/* ==================================================
 * @param	boolean $allow_search_engine
 * @return	boolean $in_network
 */
public function in_network($allow_search_engine = false) {
	$networks = $this->networks;
	if ($allow_search_engine) {
		$search_ip = apply_filters('ktai_mobile_search_ip', self::$search_ip);
		$networks = array_merge($networks, $search_ip);
	}
	if (! $networks) {
		return false;
	}
	$in_network = false;
	$ip = ip2long($_SERVER['REMOTE_ADDR']);
	foreach ( (array) $networks as $n) {
		if (strpos($n, '/') !== false) {
			// parse NN.NN.NN.NN/MASK
			list($network, $mask) = explode('/', $n);
			$net = ip2long($network);
			if (! $net || $mask < 8 || $mask > 32) {
				continue;
			}
			if ($ip >> (32 - $mask) == $net >> (32 - $mask)) {
				$in_network = true;
				break;
			}	
		} elseif (strpos($n, '-') !== false) {
			// parse MM.MM.MM.MM-NN.NN.NN.NN
			list($start, $end) = array_map('ip2long', explode('-', $n));
			if ($ip >= $start && $ip <= $end) {
				$in_network = true;
				break;
			}
		}
	}
	return $in_network;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_pre_encode($buffer) {
	if (strtoupper(get_bloginfo('charset')) == 'UTF-8') {
		$buffer = preg_replace("/\xc2\xa0/", '&nbsp;', $buffer); // no-break space
		$buffer = preg_replace("/\xe2\x99\xa0/", '&#9824;', $buffer); // spade
		$buffer = preg_replace("/\xe2\x99\xa4/", '&#9824;', $buffer); // white spade
		$buffer = preg_replace("/\xe2\x99\xa3/", '&#9827;', $buffer); // club
		$buffer = preg_replace("/\xe2\x99\xa7/", '&#9827;', $buffer); // white club
		$buffer = preg_replace("/\xe2\x99\xa5/", '&#9829;', $buffer); // heart
		$buffer = preg_replace("/\xe2\x99\xa1/", '&#9825;', $buffer); // white heart
		$buffer = preg_replace("/\xe2\x99\xa6/", '&#9830;', $buffer); // diamond
		$buffer = preg_replace("/\xe2\x99\xa2/", '&#9830;', $buffer); // white diamond
		$buffer = preg_replace("/\xe3\x80\xb0/", '&#12336;', $buffer); // wavy dash
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_pre_split($buffer) {
	$buffer = str_replace("\r\n", "\n", $buffer);
	if ($this->get('mime_type') != 'application/xhtml+xml') {
		$buffer = $this->strip_styles($buffer);
	}
	// ----- save pre elements -----
	$pre = array();
	while (preg_match('!<pre>.*?</pre>!s', $buffer, $p, PREG_OFFSET_CAPTURE)) {
		$buffer = substr_replace($buffer, "\376\376\376" . count($pre) . "\376\376\376", $p[0][1], strlen($p[0][0]));
		$pre[] = $p[0][0];
		if (count($pre) > 9999) { // infinity loop check
			 break;
		}
	}
	// ----- remove redudant spaces -----
	$buffer = preg_replace('!<(p|div)( (id|class|align)=([\'"])[-_ a-zA-Z0-9]+\\4)*>\s*</\\1>\s*!', '', $buffer); //"syntax highlighting fix
	$buffer = preg_replace('!^[ \t]+!m', '', $buffer);
	$buffer = preg_replace('!>\t+<!', '><', $buffer);
	$buffer = preg_replace('!>\s+<!', ">\n<", $buffer);
	$buffer = preg_replace('!/>[\r\n]+!', "/>\n", $buffer);
	$buffer = preg_replace('![\r\n]+</!', "\n</", $buffer);
	// ----- restore pre elements -----
	$buffer = preg_replace('/\376\376\376(\d+)\376\376\376/e', '$pre[$1]', $buffer);
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function strip_styles($buffer) {
	$buffer = preg_replace('!</?span([^>]*?)?>!s', '', $buffer); // <?php /* syntax hilighting fix */
	$buffer = preg_replace(
		'!<([a-z]+?[^>]*?) style=([\'"])' . KtaiStyle::QUOTED_STRING_REGEX . '\\2( [^>]*?)?>!s', // <?php /* syntax hilighting fix */
		'<$1$3>', 
		$buffer);
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function replace_smiley($buffer, $smiles = NULL) {
	if ($smiles && preg_match_all('!<img src=([\'"])([^>]*?/([-_.a-zA-Z0-9]+))\\1( alt=([\'"])' . KtaiStyle::QUOTED_STRING_REGEX . '\\5)? class=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\6 ?/?>!s', $buffer, $images, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach($images as $i) {
			$img      = $i[0];
			$src      = $i[2];
			$basename = $i[3];
			$alt_elem = $i[4];
			$class    = $i[7];
			if (preg_match('/(^| )wp-smiley( |$)/', $class)) {
				if (preg_match('/(^| )ktai( |$)/', $class)) {
					$buffer = str_replace($img, sprintf('<img src="%s"%s />', $src, $alt_elem), $buffer);
				} else {
					$buffer = str_replace($img, $smiles[$basename], $buffer);
				}
			}
		}
	}
	return $buffer;
}


/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function pickup_pics($buffer) {
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_post_split($buffer) {
	if ($this->get('mime_type') == 'application/xhtml+xml') {
		$buffer = $this->body_to_style($buffer);
		$buffer = preg_replace('/<a name=/', '<a id=', $buffer);
		$buffer = $this->block_align_to_style($buffer);
		$buffer = $this->horizontal_rule_to_style($buffer);
		$buffer = $this->font_to_style($buffer);
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function body_to_style($buffer) {
	if (preg_match('!</head>\s*<body([^>]+?)>!', $buffer, $body)) {
		$body_style = '';
		$head_style = '';
		$has_bgcolor = preg_match('/bgcolor="([^"]+)"/', $body[1], $bgcolor);
		$has_text    = preg_match('/text="([^"]+)"/', $body[1], $text);
		$has_image   = preg_match('/background="([^"]+)"/', $body[1], $image);
		if ($has_bgcolor || $has_text || $has_image) {
			$body_style = ' style="' 
			. (isset($text[1]) ? 'color:' . $text[1] . ';' : '') 
			. (isset($bgcolor[1]) ? 'background-color:' . $bgcolor[1] . ';' : '') 
			. (isset($image[1]) ? 'background-image:url(' . $image[1] . ');' : '') 
			. '"';
		}
		if (preg_match('/link="([^"]+)"/', $body[1], $color)) {
			$head_style .= 'a:link {color:' . $color[1] . ';} ';
		}
		if (preg_match('/alink="([^"]+)"/', $body[1], $color)) {
			$head_style .= 'a:focus {color:' . $color[1] . ';} ';
		}
		if (preg_match('/vlink="([^"]+)"/', $body[1], $color)) {
			$head_style .= 'a:visited {color:' . $color[1] . ';} ';
		}
		if ($head_style) {
			if (preg_match('#(<style[^>]*>\s*(<!\[CDATA\[)?.*?)((\]\]>)?\s*</style>)#s', $buffer, $head)) {
				$buffer = str_replace($head[0], $head[1] . " " . $head_style . $head[3], $buffer);
			} else {
				$buffer = str_replace('</head>', '<style type="text/css"><![CDATA[ ' . $head_style . ']]></style></head>', $buffer);
			}
		}
		if ($body_style) {
			$buffer = str_replace($body[0], '</head><body' . $body_style . '>', $buffer);
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function block_align_to_style($buffer) {
	if (preg_match_all('!(<h[1-6]|div|p) ([^>]*?)/?>!s', $buffer, $block, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach ($block as $b) {
			$old_style = '';
			$style = '';
			$html = $b[0];
			$elem = $b[1];
			if (preg_match_all('/ ?(\w+)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $b[2], $attr, PREG_SET_ORDER)) { //"syntax highlighting fix
				foreach ($attr as $a) {
					$key   = $a[1];
					$value = $a[3];
					switch ($key) {
					case 'style':
						if (strlen($value)) {
							$old_style = $value . (substr_compare($value, ';', -1, 1) !== 0 ? ';' : '');
							$html = str_replace($a[0], '', $html);
						}
						break;
					case 'align':
						$style .= "text-align:$value;"; 
						$html = str_replace($a[0], '', $html);
						break;			
					}
				}
			}
			if ($style) {
				$style = ' style="' . $old_style . $style . '"';
				$html = str_replace($elem, $elem . $style, $html);
				$buffer = preg_replace('!' . preg_quote($b[0], '!') . '!', $html, $buffer, 1);
			}
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function horizontal_rule_to_style($buffer) {
	if (preg_match_all('!<hr ([^>]*?)/?>!s', $buffer, $hr, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach ($hr as $h) {
			$old_style = '';
			$style = '';
			if (preg_match_all('/ ?(\w+)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $h[1], $attr, PREG_SET_ORDER)) { //"syntax highlighting fix
				foreach ($attr as $a) {
					$key   = $a[1];
					$value = $a[3];
					switch ($key) {
					case 'style':
						if (strlen($value)) {
							$old_style = $value . (substr_compare($value, ';', -1, 1) !== 0 ? ';' : '');
						}
						break;
					case 'color':
						$style .= "color:$value;border-style:solid;border-color:$value;";
						break;
					case 'size':
						$style .= "height:$value;";
					case 'width':
						$style .= "width:$value;";
						break;
					case 'align':
						$style .= 'float:' . str_replace('center','none', $value); 
						break;			
					}
				}
			}
			if ($style) {
				$style = ' style="' . $old_style . $style . '"';
				$buffer = preg_replace('!' . preg_quote($h[0], '!') . '!', "<hr$style />", $buffer, 1);
			}
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function font_to_style($buffer) {
	$buffer = str_replace(array("\375", "\376"), array('', ''), $buffer);
	$buffer = str_replace(array('<font', '</font>'), array("\375", "\376"), $buffer, $num_replaced);
	$loop = 0;
	while (preg_match('!\\375([^<>]*)>([^\\375\\376]*?)\\376!s', $buffer, $f)) {
		if ($loop++ > $num_replaced) { // infinity loop check
			break;
		}
		$old_style = '';
		$style = '';
		$font = $f[1];
		$html = $f[2];
		if (preg_match_all('/ ?(\w+)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $font, $attr, PREG_SET_ORDER)) { //"syntax highlighting fix
			foreach ($attr as $a) {
				$key   = $a[1];
				$value = $a[3];
				switch ($key) {
				case 'style':
					if (strlen($value)) {
						$old_style = $value . (substr_compare($value, ';', -1, 1) !== 0 ? ';' : '');
					}
					break;
				case 'size':
					if ($value === '+1') {
						$style .= 'font-size:larger;';
					} else {
						switch ($value) {
						case '-1':
							$style .= 'font-size:smaller;';
							break;
						case '1':
							$style .= 'font-size:x-small;';
							break;
						case '2':
							$style .= 'font-size:small;';
							break;
						case '3':
							$style .= ' ';
							break;
						case '4':
							$style .= 'font-size:large;';
							break;
						case '5':
							$style .= 'font-size:x-large;';
							break;
						case '6':
						case '7':
							$style .= 'font-size:xx-large;';
							break;
						}
					}
					break;
				case 'face':
					$style .= "font-family:$value;";
					break; 
				default:
					$style .= "$key:$value;";
				}
			}
		}
		if ($style) {
			$style = ' style="' . $old_style . $style . '"';
			$html = "<span$style>" . $html . '</span>';
		} else {
			$html = str_replace(array("\375", "\376"), array('<font', '</font>'), $f[0]);
		}
		$buffer = preg_replace('!' . preg_quote($f[0], '!') . '!', $html, $buffer, 1);
	}
	$buffer = str_replace(array("\375", "\376"), array('<font', '</font>'), $buffer, $num_replaced); // restore rest font tags
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
protected function input_to_style($buffer) {
	if (preg_match_all('|<input ([^>]*?)/?>|', $buffer, $input, PREG_SET_ORDER)) { // <?php /* syntax hilighting fix */
		foreach ($input as $i) {
			$old_style = '';
			$style = '';
			$html = $i[0];
			$attr = $i[1];
			if (preg_match('/ ?\bstyle=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\1/s', $attr, $s) && strlen($s[2])) { //"syntax highlighting fix
				$old_style = $s[2] . (substr_compare($s[2], ';', -1, 1) !== 0 ? ';' : '');
				$html = str_replace($s[0], '', $html);
			}
			if (preg_match('/ ?\bistyle=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\1/s', $attr, $istyle)) { //"syntax highlighting fix
				switch ($istyle[2]) {
				case 1: // Fullwidth Kana
					$style .= '-wap-input-format:*M;';
					break; 
				case 2: // Halfwidth Kana
					$style .= '-wap-input-format:*M;';
					break; 
				case 3: // Alphabet
					$style .= '-wap-input-format:*m;';
					break; 
				case 4: // Numeric
					$style .= '-wap-input-format:*N;';
					break;
				}
				$html = str_replace($istyle[0], '', $html);
			}
			if ($style) {
				$style = ' style="' . $old_style . $style . '"';
				$html = str_replace('<input', '<input' . $style, $html);
				$buffer = preg_replace('!' . preg_quote($i[0], '!') . '!', $html, $buffer, 1);
			}
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	int     $comment_ID
 * @param	string  $comment_approved
 * @return	none
 * @since	2.0.0
 */
public function store_term_id ($comment_ID, $comment_approved) {
	$term_ID = $this->get('term_ID');
	if ($term_ID) {
		add_comment_meta($comment_ID, 'terminal_id', $term_ID);
	}
	$usim_ID = $this->get('usim_ID');
	if ($usim_ID) {
		add_comment_meta($comment_ID, 'usim_id', $usim_ID);
	}
	$sub_ID = $this->get('sub_ID');
	if ($sub_ID) {
		add_comment_meta($comment_ID, 'subscriber_id', $sub_ID);
	}
}

/* ==================================================
 * @param	string  $user_agent
 * @return	string  $user_agent
 */
public function add_term_id ($user_agent) {
	$id = array();
	$term_ID = $this->get('term_ID');
	if ($term_ID) {
		$id[] = "Term ID: {$term_ID}";
	}
	$usim_ID = $this->get('usim_ID');
	if ($usim_ID) {
		$id[] = "USIM ID: {$usim_ID}";
	}
	$sub_ID = $this->get('sub_ID');
	if ($sub_ID) {
		$id[] = "Sub ID: {$sub_ID}";
	}
	if ($id) {
		$user_agent .= ' (' . implode(' ', $id) . ')';
	}
	return $user_agent;
}

/* ==================================================
 * @param	object  $comment
 * @return	array   $id
 */
public function read_term_id($comment) {
	$id = array(NULL, NULL, NULL);
	if (function_exists('get_comment_meta')) {
		$id[0] = get_comment_meta($comment->comment_ID, 'terminal_id', true);
		$id[1] = get_comment_meta($comment->comment_ID, 'usim_id', true);
		$id[2] = get_comment_meta($comment->comment_ID, 'subscriber_id', true);
	}
	if ( empty($id[0]) && empty($id[1]) && empty($id[2]) 
	&& preg_match('/\((Term ID: ([^ ]*)( USIM ID: ([^ ]*))?)? ?(Sub ID: ([^)]*))?\)$/', $comment->comment_agent, $agent)) {
		$id[0] = isset($agent[2]) ? $agent[2] : NULL;
		$id[1] = isset($agent[4]) ? $agent[4] : NULL;
		$id[2] = isset($agent[6]) ? $agent[6] : NULL;
	}
	return $id;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_General class
   ================================================== */

class KtaiService_General extends KtaiServices {
	private $pict_images;
	private $convert_fullwidth_tild = false;

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->use_redir  = false;
	$this->show_plugin_icon = true;
	$this->user_agent = $user_agent;
	require dirname(__FILE__) . '/pictogram-images.php';
	$this->pict_images = new KtaiPictogramImages();
	return;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function convert_pict($buffer) {
	$buffer = preg_replace(
		'!<img localsrc="([^"]+)"( alt="(' . KtaiStyle::DOUBLE_QUOTED_STRING_REGEX . ')")?[^/>]*/?>!se', // <?php /* syntax hilighting fix */
		'$this->pict_images->pict_replace("$1", "$2", "$3", $this->charset)', 
		$buffer);
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function shrink_post_split($buffer) {
	$buffer = $this->horizontal_rule_to_style($buffer);
	if (strtoupper($this->charset) == 'UTF-8' && $this->convert_fullwidth_tild) {
		$buffer = preg_replace("/\x{301c}/u", "\xef\xbd\x9e", $buffer);
	}
	return parent::shrink_post_split($buffer);
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_Preview class
   ================================================== */

class KtaiService_Preview extends KtaiService_General {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 * @since	2.0.0
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->type = 'Theme_Preview';
	$this->use_redir = true;
	$this->theme = stripslashes($_GET['template']);
	remove_action('setup_theme', 'preview_theme');
	global $Ktai_Style;
	add_action('setup_theme', array($this, 'check_mobile_preview'), 9);
	remove_action('setup_theme', 'preview_theme');
	add_action('setup_theme', array('KtaiThemes', 'preview_theme'));
	add_filter('ktai_mime_type', create_function('', 'return "text/html";')); // prevent downloading against Internet Explorer
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	2.0.0
 */
public function check_mobile_preview() {
	if ( !KtaiStyle::verify_anon_nonce($_GET['_wpnonce'], 'switch-theme_' . stripslashes($_GET['template'])) ) {
		wp_die('Invalid Mobile Preview');
	}
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_Converter class
   ================================================== */

class KtaiService_Converter extends KtaiService_General {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->admin_enabled = false;
	$this->xhtml_head = ''; // force text/html
	return;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_iPhone class
   ================================================== */

class KtaiService_iPhone extends KtaiService_General {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 * @since	1.81
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->type = 'TouchPhone';
	add_action('ktai_wp_head', array($this, 'viewport') );
	return;
}

public function viewport() {
	echo '<meta name="viewport" content="width=device-width,initial-scale=1.0" />' . "\n";
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_Android class
   ================================================== */

class KtaiService_Android extends KtaiService_General {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 * @since	1.81
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->type = 'TouchPhone';
	return;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_BlackBerry class
   ================================================== */

class KtaiService_BlackBerry extends KtaiService_General {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 * @since	1.81
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->type = 'TouchPhone';
	return;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_WindowsPhone class
   ================================================== */

class KtaiService_WindowsPhone extends KtaiService_General {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 * @since	1.81
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->type = 'TouchPhone';
	return;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_WindowsCE class
   ================================================== */

class KtaiService_WindowsCE extends KtaiService_General {
	public static $dcm_smartphones = array(
		'DCM06' => 'htcZ',
	);

/* ==================================================
 * @param	string  $user_agent
 * @param	array	$specs
 * @return	object  $this
 * @since	2.1.0
 */
public function __construct($user_agent, $specs) {
	parent::__construct($user_agent);
	$this->convert_fullwidth_tild = true;
	$this->charset = 'SJIS-win';
	if ( $specs[2] ) {
		if (preg_match('!SHARP/([^;]*)!', $specs[2], $name)) { // W-ZERO3, EM-ONE
			$this->term_name = $name[1];
		} elseif (preg_match('/IEMobile [\d.]*\) (FOMA )?(\w+)/', $specs[2], $name)) { // FOMA 1100 series
			$this->term_name = $name[2];
		} elseif (preg_match('/DCM\d+/', $specs[2], $name)) { // htc Z
			$this->term_name = self::$dcm_smartphones[$name[0]];
		} elseif (preg_match('/^([^;]*);/', $specs[2], $name)) {
			$this->term_name = $name[1];
		}
	}
	if ( empty($this->term_name) ) {
		$this->term_name = $specs[1]; // 'Windows CE'
	}
	return;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiService_General_Japan class
   ================================================== */

class KtaiService_General_Japan extends KtaiService_General {

/* ==================================================
 * @param	string  $user_agent
 * @return	object  $this
 */
public function __construct($user_agent) {
	parent::__construct($user_agent);
	$this->charset = 'SJIS-win';
	return;
}

// ===== End of class ====================
}

/* ==================================================
 * @param	boolean  $echo
 * @param	boolean  $detect_search_engine
 * @return	none
 * @since	0.91
 */
function ks_term_name($echo = true, $detect_search_engine = true) {
	global $Ktai_Style;
	$term_name = $detect_search_engine ? $Ktai_Style->get('search_engine') : NULL;
	if ( !$term_name ) {
		$term_name = $Ktai_Style->get('term_name');
	}
	if ($echo) {
		echo esc_html($term_name);
	}
	return $term_name;
}

/* ==================================================
 * @param	none
 * @return	srting  $type
 * @since	1.20
 */
function ks_service_type() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('type') : false;
}

/* ==================================================
 * @param	none
 * @return	boolean $is_flat_rate
 * @since	1.20
 */
function ks_is_flat_rate() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('flat_rate') : false;
}

/* ==================================================
 * @param	none
 * @return	boolean $cookie_available
 * @since	1.60
 */
function ks_cookie_available() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('cookie_available') : false;
}

/* ==================================================
 * @param	none
 * @return	boolean $ext_css_available
 * @since	1.70
 */
function ks_ext_css_available() {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->get('ext_css_available') : false;
}

/* ==================================================
 * @param	boolean $allow_search_engine
 * @return	boolean $in_network
 * @since	1.30
 */
function ks_in_network($allow_search_engine = false) {
	global $Ktai_Style;
	return isset($Ktai_Style->ktai) ? $Ktai_Style->ktai->in_network($allow_search_engine) : false;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.98
 */
function ks_use_appl_xhtml() {
	global $Ktai_Style;
	if ($Ktai_Style->ktai->get('xhtml_head')) {
		$Ktai_Style->ktai->set('mime_type', 'application/xhtml+xml');
		echo $Ktai_Style->ktai->get('xhtml_head');
	} else { ?>
<html <?php language_attributes(); ?>>
<?php }	
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	1.20
 */
function ks_applied_appl_xhtml() {
	global $Ktai_Style;
	return ($Ktai_Style->ktai->get('mime_type') == 'application/xhtml+xml');
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.97
 */
function ks_force_text_html() {
	global $Ktai_Style;
	$Ktai_Style->ktai->set('mime_type', 'text/html');
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.95
 */
function ks_mimetype($echo = true) {
	global $Ktai_Style;
	if ($echo) {
		echo esc_html($Ktai_Style->ktai->get('mime_type'));
	}
	return $Ktai_Style->get('mime_type');
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	0.91
 */
function ks_charset($echo = true) {
	global $Ktai_Style;
	if ($echo) {
		echo esc_html($Ktai_Style->ktai->get('iana_charset'));
	}
	return $Ktai_Style->get('iana_charset');
}

?>