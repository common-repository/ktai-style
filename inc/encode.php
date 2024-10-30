<?php
/* ==================================================
 *   KtaiEncode class
   ================================================== */
   
/*  Copyright (c) 2010 IKEDA Yuriko

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

class KtaiEncode {
	private $original_encoding;
	private $input_encoding;
	private $mobile_encoding;
	private $blog_encoding;
	const ALLOW_AUTO = true;
	const DISALLOW_AUTO = false;
	const LOOSE = true;

/* ==================================================
 * @param	string  $mobile_encoding
 * @return	object  $this
 * @since 2.1.0
 */
public static function factory($mobile_encoding = NULL) {
	if ( function_exists('mb_convert_encoding') ) {
		return new KtaiEncode_mbstring($mobile_encoding);
	} else { // assume having iconv extension
		return new KtaiEncode_iconv($mobile_encoding);
	}
}

/* ==================================================
 * @param	string  $mobile_encoding
 * @return	object  $this
 * @since 2.1.0
 */
public function __construct($mobile_encoding = NULL) {
	$this->mobile_encoding = $this->input_encoding = $mobile_encoding;
	$this->blog_encoding = get_bloginfo('charset');
}

/* ==================================================
 * @param	string  $key
 * @return	mixed   $value
 * @since 2.1.0
 */
public function get($key) {
	return $this->$key;
}

/* ==================================================
 * @param	string  $key
 * @param	mixed   $value
 * @return	none
 * @since 2.1.0
 */
public function set($key, $value) {
	$this->$key = $value;
}

/* ==================================================
 * @param	string  $charset
 * @return	mixed   $value
 * @since 2.1.0
 */
public function iana_charset($charset = NULL) {
	if ( !$charset ) {
		$charset = $this->mobile_encoding;
	}
	return apply_filters( 'ktai_iana_charset', KtaiEncode_iconv::normalize($charset, self::LOOSE) );
}

/* ==================================================
 * @param	string  $encoding1
 * @param	string  $encoding2
 * @return	boolean $is_same
 * @since 2.1.0
 */
public function similar($encoding1, $encoding2) {
	return (strcmp(
		strtolower($this->normalize($encoding1, self::LOOSE)), 
		strtolower($this->normalize($encoding2, self::LOOSE))
	) === 0);
}

/* ==================================================
 * @param	string  $buffer
 * @param	string  $in_encoding
 * @return	string  $buffer
 * @since 2.1.0
 */
public function from_mobile($buffer, $encoding = NULL) {
	if ( !$encoding ) {
		$encoding = $this->input_encoding;
	}
	$buffer = $this->convert($buffer, $this->blog_encoding, $encoding);
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 * @since 2.1.0
 */
public function to_mobile($buffer) {
	if ($this->mobile_encoding) {
		$buffer = $this->convert($buffer, $this->mobile_encoding, $this->blog_encoding);
	}
	return $buffer;
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiEncode_mbstring class
   ================================================== */

class KtaiEncode_mbstring extends KtaiEncode {
	public static $detect_order = array('ASCII', 'JIS', 'UTF-8', 'SJIS', 'EUC-JP', 'SJIS-win');

public function __construct($mobile_encoding = NULL) {
	parent::__construct($mobile_encoding);
	$this->original_encoding = mb_internal_encoding();
}

/* ==================================================
 * @param	string  $key
 * @return	mixed   $value
 * @since 0.9.0
 */
public function get($key) {
	if ($key == 'detect_order') {
		return apply_filters('ktai_detect_order', self::$detect_order); // must be at child class
	} else {
		return $this->$key;
	}
}

/* ==================================================
 * @param	string  $encoding
 * @param	boolean $loose
 * @return	string  $encoding
 * @since 2.1.0
 */
public function normalize($encoding, $loose = false) {
	$normalize = array(
		'ujis'           => 'EUC-JP',
		'cp932'          => 'SJIS-win',
		'shift_jis'      => 'SJIS',
		'ms_kanji'       => 'SJIS',
		'windows-31j'    => 'SJIS-win',
		'iso-2022-jp'    => 'JIS',
		'iso-2022-jp-1'  => 'JIS',
		'iso-2022-jp-2'  => 'JIS',
		'iso-2022-jp-ms' => 'ISO-2022-JP-MS', // prevent normalizing into 'JIS-ms'
	);
	if ($loose) {
		$normalize = array_merge($normalize, array(
			'cp932'          => 'SJIS', // override
			'sjis-win'       => 'SJIS', // override
			'windows-31j'    => 'SJIS', // override
			'eucjp-win'      => 'EUC-JP',
			'iso-2022-jp-ms' => 'JIS', // override
		));
	}
	return strtr(strtolower($encoding), $normalize);
}

/* ==================================================
 * @param	string   $buffer
 * @return	string   $encoding
 * @since 2.1.0
 */
public function check($buffer, $encoding) {
	if ($encoding == 'auto') {
		$encoding = $this->guess($buffer, prent::DISALLOW_AUTO);
	}
	if ($this->similar($encoding, 'SJIS')) {
		$result = mb_check_encoding($buffer, 'SJIS') || mb_check_encoding($buffer, 'SJIS-win');
	} elseif ($this->similar($encoding, 'EUC-JP')) {
		$result = mb_check_encoding($buffer, 'EUC-JP') || mb_check_encoding($buffer, 'eucJP-win');
	} elseif ($this->similar($encoding, 'JIS')) {
		$result = mb_check_encoding($buffer, 'ISO-2022-JP') || mb_check_encoding($buffer, 'ISO-2022-JP-MS');
	} else {
		$result = mb_check_encoding($buffer, $this->normalize($encoding));
	}
	return $result;
}

/* ==================================================
 * @param	string  $input
 * @param	boolean $allow_auto
 * @return	string  $encoding
 * @since 2.1.0
 */
public function guess_from_http($allow_auto = false) {
	$encoding = ini_get('mbstring.encoding_translation') ? $this->original_encoding : mb_http_input('G');
	if ( !$encoding && $allow_auto ) {
		$encoding = 'auto';
	}
	if ( $encoding ) {
		$this->input_encoding = $encoding;
	}
	return $encoding;
}

/* ==================================================
 * @param	string  $input
 * @param	boolean $allow_auto
 * @return	string  $encoding
 * @since 2.1.0
 */
public function guess($input, $allow_auto = false) {
	$default = $this->input_encoding ? $this->input_encoding : $this->original_encoding;
	if ( $input ) {
		$encoding = mb_detect_encoding($input, $this->get('detect_order'));
		if ( $allow_auto && $encoding == 'ASCII' ) {
			$encoding = 'auto';
		}
	} else {
		$encoding = $allow_auto ? 'auto' : $default;
	}
	return $encoding;
}

/* ==================================================
 * @param	string  $buffer
 * @param	string  $out_encoding
 * @param	string  $in_encoding
 * @return	string  $buffer
 * @since 2.1.0
 */
public function convert($buffer, $out_encoding = 'UTF-8', $in_encoding = 'auto') {
	if ( !$this->similar($in_encoding, $out_encoding) ) {
		$buffer = mb_convert_encoding($buffer, $this->normalize($out_encoding), $this->normalize($in_encoding));
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 * @since 2.1.0
 */
public function halfwidth_kana($buffer, $encoding = '') {
	if ( empty($encoding) ) {
		$encoding = $this->blog_encoding;
	}
	if (preg_match('/^(utf-8|shift_jis|sjis(-win)?|cp932|euc(-jp|jp-win))$/i', $encoding)) {
		$buffer = mb_convert_kana($buffer, 'knrs', $encoding);
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $pattern
 * @param	string  $replacement
 * @param	string  $string
 * @param	string  $option
 * @param	string  $encoding
 * @return	string  $buffer
 * @since 2.1.0
 */
public function regex_replace($pattern, $replacement, $string, $encoding = '', $option = '') {
	if ( empty($encoding) ) {
		$encoding = $this->input_encoding;
	}
	mb_regex_encoding($encoding);
	return mb_ereg_replace($pattern, $replacement, $string, $option);
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiEncode_iconv class
   ================================================== */

class KtaiEncode_iconv extends KtaiEncode {
	public static $detect_order = array('US-ASCII', 'ISO-2022-JP', 'UTF-8', 'Shift_JIS', 'EUC-JP', 'CP932');

public function __construct($mobile_encoding = NULL) {
	parent::__construct($mobile_encoding);
	$this->original_encoding = $this->blog_encoding;
}

/* ==================================================
 * @param	string  $key
 * @return	mixed   $value
 * @since 0.9.0
 */
public function get($key) {
	if ($key == 'detect_order') {
		return apply_filters('ktai_detect_order', self::$detect_order); // must be at child class
	} else {
		return $this->$key;
	}
}

/* ==================================================
 * @param	string  $encoding
 * @param	boolean $loose
 * @return	string  $encoding
 * @since 2.1.0
 */
public function normalize($encoding, $loose = false) {
	$normalize = array(
		'jis'            => 'ISO-2022-JP',
		'sjis'           => 'Shift_JIS',
		'shift_jis'      => 'Shift_JIS', // prevent normalizing into 'shift_ISO-2022-JP'
		'sjis-win'       => 'CP932',
		'ujis'           => 'EUC-JP',
		'ms_kanji'       => 'Shift_JIS',
		'windows-31j'    => 'CP932',
		'eucjp-win'      => 'EUC-JP',
		'iso-2022-jp-ms' => 'ISO-2022-JP',
	);
	if ($loose) {
		$normalize = array_merge($normalize, array(
			'cp932'          => 'Shift_JIS',
			'sjis-win'       => 'Shift_JIS', // override
			'windows-31j'    => 'Shift_JIS', // override
			'iso-2022-jp-1'  => 'ISO-2022-JP',
			'iso-2022-jp-2'  => 'ISO-2022-JP',
		));
	}
	return strtr(strtolower($encoding), $normalize);
}

/* ==================================================
 * @param	string   $buffer
 * @return	string   $encoding
 * @since 2.1.0
 */
public function check($buffer, $encoding) {
	if ($encoding == 'auto') {
		$encoding = $this->guess($buffer, parent::DISALLOW_AUTO);
	}
	if ($this->similar($encoding, 'shift_jis')) {
		$converted1 = iconv('Shift_JIS', 'Shift_JIS//IGNORE', $buffer);
		$converted2 = iconv('CP932',     'CP932//IGNORE',     $buffer);
		$result = ( $converted1 === $buffer || $converted2 === $buffer );
	} elseif ($this->similar($encoding, 'iso-2022-jp')) {
		$converted1 = iconv('ISO-2022-JP',   'ISO-2022-JP//IGNORE',   $buffer);
		$converted2 = iconv('ISO-2022-JP-1', 'ISO-2022-JP-1//IGNORE', $buffer);
		$converted3 = iconv('ISO-2022-JP-2', 'ISO-2022-JP-2//IGNORE', $buffer);
		$result = ( $converted1 === $buffer || $converted2 === $buffer || $converted3 === $buffer );
	} else {
		$encoding = $this->normalize($encoding);
		$converted = iconv($encoding, $encoding . '//IGNORE', $buffer);
		$result = ( $converted === $buffer );
	}
	return $result;
}

/* ==================================================
 * @param	string  $input
 * @param	boolean $allow_auto
 * @return	string  $encoding
 * @since 2.1.0
 */
public function guess_from_http($allow_auto = false) {
	$encoding = NULL;
	if ( $allow_auto ) {
		$encoding = 'auto';
		$this->input_encoding = $encoding;
	}
	return $encoding;
}

/* ==================================================
 * @param	string  $input
 * @param	boolean $allow_auto
 * @return	string  $encoding
 * @since 2.1.0
 */
public function guess($input, $allow_auto = false) {
	$default = $this->input_encoding ? $this->input_encoding : $this->original_encoding;
	if ( $input ) {
		$encoding = NULL;
		$detect_order = $this->get('detect_order');
		foreach ($detect_order as $enc) {
			$enc = $this->normalize($enc);
			$converted = iconv($enc, $enc . '//IGNORE', $buffer);
			if ( $converted === $input ) {
				$encoding = $enc;
				break;
			}
		}
	} else {
		$encoding = $allow_auto ? 'auto' : $default;
	}
	return $encoding;
}

/* ==================================================
 * @param	string  $buffer
 * @param	string  $out_encoding
 * @param	string  $in_encoding
 * @return	string  $buffer
 * @since 2.1.0
 */
public function convert($buffer, $out_encoding = 'UTF-8', $in_encoding = 'auto') {
	if ( $in_encoding == 'auto' ) {
		$in_encoding = $this->input_encoding ? $this->input_encoding : $this->original_encoding;
	}
	if ( !$this->similar($in_encoding, $out_encoding) ) {
		$buffer = iconv($this->normalize($in_encoding), $this->normalize($out_encoding) . '//TRANSLIT', $buffer);
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 * @since 2.1.0
 */
public function halfwidth_kana($buffer, $encoding = '') {
	if ( empty($encoding) ) {
		$encoding = $this->blog_encoding;
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $pattern
 * @param	string  $replacement
 * @param	string  $string
 * @param	string  $encoding
 * @param	string  $option
 * @return	string  $buffer
 * @since 2.1.0
 */
public function regex_replace($pattern, $replacement, $string, $encoding = '', $option = '') {
	if ( empty($encoding) ) {
		$encoding = $this->input_encoding;
	}
	return preg_replace('/' . preg_quote($pattern, '/') . '/' . $option, $replacement, $string);
}

// ===== End of class ====================
}
?>