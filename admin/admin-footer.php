<?php 
/* ==================================================
 *   Ktai Admin Output Footer
   ================================================== */

global $Ktai_Style, $mime_type, $iana_charset, $ks_settings;
?>
<!--end paging-->
<hr color="<?php echo $ks_settings['hr_color']; ?>" />
<a name="tail" href="#head"><img localsrc="29" alt="<?php esc_attr_e('&uarr;', 'ktai_style'); ?>" /></a>| <?php include_once dirname(__FILE__) . '/menu-header.php'; ?>
<hr color="<?php echo $ks_settings['hr_color']; ?>" />
<div align="right">Ktai Style <?php echo KTAI_STYLE_VERSION; ?></div>
</body></html>
<?php 
$buffer = $Ktai_Style->ktai->get('preamble') . "\n";
$buffer .= ob_get_contents();
ob_end_clean();
$referer = $Ktai_Style->admin->get_referer(); // before store_referer()
$Ktai_Style->admin->store_referer()->save_data();
$Ktai_Style->admin->unset_prev_session($Ktai_Style->admin->get_sid());
$buffer = $Ktai_Style->ktai->shrink_pre_encode($buffer);
$buffer = $Ktai_Style->encode->to_mobile($buffer);
$buffer = $Ktai_Style->ktai->shrink_pre_split($buffer);
$buffer = $Ktai_Style->ktai->convert_pict($buffer);
if ( !$Ktai_Style->ktai->get('cookie_available') ) {
	$buffer = $Ktai_Style->admin->add_link_sid($buffer);
}
list($header, $buffer) = preg_split('/\n*<!--start paging-->\n*/', $buffer, 2);
list($buffer, $footer) = preg_split('/\n*<!--end paging-->\n*/', $buffer, 2);
if (strlen($header . $buffer . $footer) > $Ktai_Style->ktai->get('cache_size')) {
	$buffer = sprintf(__('<p>The page is too big for your terminal. Please back to <a href="%s">the previous page</a>.</p>', 'ktai_style'), esc_attr($referer));
	$buffer = $Ktai_Style->encode->to_mobile($buffer);
}
$buffer = $Ktai_Style->ktai->shrink_post_split($header . $buffer . $footer);
if (function_exists('mb_http_output')) {
	mb_http_output('pass');
}
header ("Content-Type: $mime_type; charset=$iana_charset");
echo $buffer;
?>