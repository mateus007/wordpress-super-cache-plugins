<?php

class Cache_Hook_Minify {

	/**
	 * Placeholders for HTML
	 * @var array
	 */
	public $htmlPlaceholders = array();

	/**
	 * Compress HTML to reduce load
	 * Remove line breaks, tab spaces and double spaces
	 * @param string $html
	 * @return string
	 **/
	public function htmlMinify($html){

		// Replace PREs with placeholders
		$html = preg_replace_callback(
			'/\\s*(<pre\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i',
			array($this, 'setPlaceholder'),
			$html
		);

		// Replace TEXTAREAs with placeholders
		$html = preg_replace_callback(
			'/\\s*(<textarea\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i',
			array($this, 'setPlaceholder'),
			$html
		);

		// Replace SCRIPTs with placeholders
		$html = preg_replace_callback(
			'/\\s*(<script\\b[^>]*?>[\\s\\S]*?<\\/script>)\\s*/i',
			array($this, 'setScriptPlaceholder'),
			$html
		);

		// Remove HTML comments
		$html = preg_replace_callback(
			'/(<!--[\\s\\S]*?-->)/',
			array($this, 'removeComments'),
			$html
		);

		// Trim each line
		$html = str_replace(array("\n","\r","\t"), ' ', $html);
		$html = preg_replace('/^\\s+|\\s+$/m', '', $html);

		// Remove white space around block/undisplayed elements
		// $html = preg_replace('/(<\\/?(?:area|base(?:font)?|blockquote|body'
		// 		. '|caption|center|cite|col(?:group)?|dd|dir|div|dl|dt|fieldset|form'
		// 		. '|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta'
		// 		. '|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)'
		// 		. '|ul)\\b[^>]*>)\\s+/i', '$1', $html);

		// Remove white space outside of all elements
		// $html = preg_replace(
		// 	'/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</',
		// 	'>$1$2$3<',
		// 	$html
		// );

		// Remove all multiple whitespace
		$html = preg_replace('/\s+/', ' ', $html);

		// Remove id=999 from images URLs
		// Visual Composer???
		$html = preg_replace('/\.jpeg\?id\=[0-9]+/', '.jpeg', $html);
		$html = preg_replace('/\.jpg\?id\=[0-9]+/', '.jpg', $html);
		$html = preg_replace('/\.png\?id\=[0-9]+/', '.png', $html);

		// Replace placeholders
		$this->htmlPlaceholders = array_reverse($this->htmlPlaceholders, TRUE);

		foreach( $this->htmlPlaceholders as $key => $value ){
			$html = str_replace($key, $value, $html);
		}

		return $html;
	}

	/**
	 * Generate a placeholderID and replace the string with placeholderID
	 * @param array $match
	 * @return string
	 */
	protected function setPlaceholder($match){

		$placeholder = 'MINIFY_PLACEHOLDER'. count($this->htmlPlaceholders);
		$this->htmlPlaceholders[ $placeholder ] = $match[1];

		return $placeholder;
	}

	/**
	 * Generate a script placeholderID and replace the string with placeholderID
	 * This method also remove tabs in scripts
	 * @param array $match
	 * @return string
	 */
	protected function setScriptPlaceholder($match){

		$match[1] = str_replace("\t", '', $match[1]);
		$match[1] = preg_replace('/^\\s+/m', '', $match[1]);

		return $this->setPlaceholder($match);
	}

	/**
	 * Remove HTML comments
	 * Do not remote IE conditional comments
	 * @param array $match
	 * @return string
	 */
	protected function removeComments($match){
		return ( FALSE !== strpos($match[1], '<!--[')
				 OR FALSE !== strpos($match[1], '<![') ) ? $match[0] : '';
	}

}

/**
 * Compress HTML before sending response
 * @param string $html
 * @return void
 */
function wp_cache_hook_minify($html){

    $minify = new Cache_Hook_Minify;
	$html = $minify->htmlMinify($html);

    return $html;
}

/**
 * Add filter to minify HTML page
 * @return void
 */
function wp_cache_hook_minify_filter() {
    add_filter('wpsupercache_buffer', 'wp_cache_hook_minify', 100);
}

if( function_exists('add_cacheaction') ){
    add_cacheaction('add_cacheaction', 'wp_cache_hook_minify_filter');
}