<?php 
	function loadMobileOutsideHeader( $tpl, $params )
	{ 
		global $C;
		
		// Pages used without an account (sign-up, password recovery, contacts, terms) look like the
		// mobile sign-in page (controllers/mobile/home.php).
		$lang_abbrv = explode( '.', $C->PHP_LOCALE );
		if( is_array($lang_abbrv) ){
			$lang_abbrv = explode('_', $lang_abbrv[0]);
		}
		$lang_abbrv = (isset($lang_abbrv[0]))? strtolower($lang_abbrv[0]) : 'en';
		$tpl->layout->setVar( 'html_lang_abbrv', $lang_abbrv );
		
		$tmp = $tpl->designer->getMetaData().$tpl->designer->getFaviconData();
		$tmp .= '<link href="'.$C->STATIC_URL.'css/mobile-login.css" type="text/css" rel="stylesheet" />';
		$tpl->layout->setVar( 'header_data', $tmp );
		$tpl->layout->setVar( 'logo_data', $tpl->designer->loadNetworkLogo() );
	}
?>
