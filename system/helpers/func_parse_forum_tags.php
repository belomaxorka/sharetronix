<?php
	function bb_apply_tags($text)
	{
		$text	= preg_replace( '#\[b\](.+?)\[/b\]#is', '<b>\\1</b>', $text );
		$text	= preg_replace( '#\[i\](.+?)\[/i\]#is', '<i>\\1</i>', $text );
		$text	= preg_replace( '#\[u\](.+?)\[/u\]#is', '<u>\\1</u>', $text );
		
		$text = preg_replace_callback('#(^|\s)((http|https|news|ftp)://\w+[^\s\[\]]+)#i', create_function ('$matches', 'return bb_build_url($matches[2], $matches[2]);'), $text);
		$text = preg_replace_callback( '#\[url\](\S+?)\[/url\]#i', create_function ('$matches', 'return bb_build_url($matches[1], $matches[1]);'), $text);
		$text = preg_replace_callback( '#\[url\s*=\s*\&quot\;\s*(\S+?)\s*\&quot\;\s*\](.*?)\[\/url\]#i', create_function ('$matches', 'return bb_build_url($matches[1], $matches[2]);'), $text );
		$text = preg_replace_callback( '#\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]#i', create_function ('$matches', 'return bb_build_url($matches[1], $matches[2]);'), $text );
	
		return $text;
	}
	
	function bb_build_url( $link, $txt )
	{
		$url	= array();
		$url['html']	= $link;
		$url['show']	= $txt;
		$url['st']	= '';
		$url['end']	= '';
		$skip_it = 0;
	
		if ( preg_match( "/([\.,\?]|&#33;)$/", $url['html'], $match) ) {
			$url['end'] .= $match[1];
			$url['html'] = preg_replace( "/([\.,\?]|&#33;)$/", "", $url['html'] );
			$url['show'] = preg_replace( "/([\.,\?]|&#33;)$/", "", $url['show'] );
		}
	
		$url['html'] = preg_replace( "/&amp;/" , "&" , $url['html'] );
	
		$url['html'] = preg_replace( "/javascript:/i", "java script&#58;", $url['html'] );
	
		if ( ! preg_match("#^(http|news|https|ftp|aim)://#", $url['html'] ) ) {
			$url['html'] = 'http://'.$url['html'];
		}
	
		if (preg_match( "/^img src/i", $url['show'] )) $skip_it = 1;
	
		$url['show'] = preg_replace( "/&amp;/" , "&" , $url['show'] );
		$url['show'] = preg_replace( "/javascript:/i", "javascript&#58;", $url['show'] );
	
		if ( (strlen($url['show']) -58 ) < 3 )  $skip_it = 1;
	
		if (!preg_match( "/^(http|ftp|https|news):\/\//i", $url['show'] )) $skip_it = 1;
	
		$show     = $url['show'];
	
		if ($skip_it != 1) {
			$stripped = preg_replace( "#^(http|ftp|https|news)://(\S+)$#i", "\\2", $url['show'] );
			$uri_type = preg_replace( "#^(http|ftp|https|news)://(\S+)$#i", "\\1", $url['show'] );
			$show = $uri_type.'://'.substr( $stripped , 0, 35 ).'...'.substr( $stripped , -15   );
		}
		return $url['st'] . '<a href="'.$url['html'].'" target="_blank">'.$show.'</a>' . $url['end'];
	}