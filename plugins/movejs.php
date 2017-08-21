<?php

class Cache_Hook_MoveJS {

	/**
	 * MoveJS HTML to bottom of page to increase load
	 * @param string $html
	 * @return string
	 **/
	public function moveJs($html){

		$regex = '#(<\!--\[if[^\>]*>\s*<script.*</script>\s*<\!\[endif\]-->)|(<script.*</script>)#isU';
		$regexCloseBody = '#</body>\s*</html>#isU';
		$regexNoMove = '#(<script(.*)?nomove)#isU';
		$regexJsonLd = '#(application\/ld\+json)#isU';

		$append = array();

		preg_match_all($regex, $html, $matches);
		preg_match_all($regexCloseBody, $html, $closeMatches);

		if( !$matches OR !$matches[0]
			OR !$closeMatches OR !$closeMatches[0] ){
			return;
		}

		foreach( $matches[0] as $match ){

			if( preg_match($regexNoMove, $match) ){
				$fixed = str_replace('nomove', '', $match);
				$html = str_replace($match, $fixed, $html);
				continue;
			}

			if( preg_match($regexJsonLd, $match) ){
				continue;
			}

			$html = str_replace($match, '', $html);
			$append[] = $match;

		}

		$close = $closeMatches[0][0];
		$html = str_replace($close, '', $html);
		$append[] = $close;

		$html .= implode("\n", $append);

		return $html;
	}

}

/**
 * Move JS to botton before sending response
 * @param string $html
 * @return void
 */
function wp_cache_hook_movejs($html){

    $move = new Cache_Hook_MoveJS;
	$html = $move->moveJs($html);

    return $html;
}

/**
 * Add filter to move JS to the botton of HTML page
 * @return void
 */
function wp_cache_hook_movejs_filter() {
    add_filter('wpsupercache_buffer', 'wp_cache_hook_movejs', 100);
}

if( function_exists('add_cacheaction') ){
    add_cacheaction('add_cacheaction', 'wp_cache_hook_movejs_filter');
}