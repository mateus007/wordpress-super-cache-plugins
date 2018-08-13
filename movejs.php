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
    global $cache_movejs;

    if( isset($cache_movejs) AND $cache_movejs ){
        $move = new Cache_Hook_MoveJS;
        $html = $move->moveJs($html);
    }

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

/**
 * Add plugin on WP Super Cache admin options
 * @return void
 */
function wp_cache_movejs_admin() {
    global $cache_movejs, $wp_cache_config_file, $valid_nonce;

    $cache_movejs = '' === $cache_movejs ? '0' : $cache_movejs;
    $id = 'movejs-section';

    if( isset($_POST['cache_movejs'])
        AND $valid_nonce ) {

        $changed = ( $cache_movejs === (int) $_POST['cache_movejs'] ) ? FALSE : TRUE;
        $cache_movejs = (int) $_POST['cache_movejs'];

        wp_cache_replace_line(
            '^ *\$cache_movejs',
            "\$cache_movejs = '$cache_movejs';",
            $wp_cache_config_file
        );

    }
    ?>
    <fieldset id="<?php echo $id ?>" class="options">
        
        <h4><?php _e('MoveJS', 'wp-super-cache') ?></h4>
        <form name="wp_manager" action="" method="post">

            <label><input type="radio" name="cache_movejs" value="1" <?php if ( $cache_movejs ) { echo 'checked="checked" '; } ?>/> <?php _e( 'Enabled', 'wp-super-cache' ); ?></label>
            <label><input type="radio" name="cache_movejs" value="0" <?php if ( ! $cache_movejs ) { echo 'checked="checked" '; } ?>/> <?php _e( 'Disabled', 'wp-super-cache' ); ?></label>

            <p><?php _e('Automatically moves every Javascripts to the footer of pages.', 'wp-super-cache') ?></p>

            <?php
            if ( isset( $changed ) && $changed ) {
                if ( $cache_movejs ) {
                    $status = __( 'enabled', 'wp-super-cache' );
                } else {
                    $status = __( 'disabled', 'wp-super-cache' );
                }
                echo '<p><strong>' . sprintf( __( 'MoveJS is now %s', 'wp-super-cache' ), $status ) . '</strong></p>';
            }
            ?>

            <div class="submit">
                <input class="button-primary" <?php echo SUBMITDISABLED ?> type="submit" value="<?php _e('Update', 'wp-super-cache') ?>" />
            </div>
            <?php wp_nonce_field( 'wp-cache' ); ?>

        </form>
    </fieldset>
    <?php
}

if( function_exists('add_cacheaction') ){
    add_cacheaction('cache_admin_page', 'wp_cache_movejs_admin');
}

/**
 * Add plugin on WP Super Cache plugins
 * @param array $list
 * @return array
 */
function wp_cache_movejs_list( $list ) {

    $list['movejs'] = array(
        'key'   => 'movejs',
        'url'   => 'https://github.com/mateus007/wp-super-cache-plugins',
        'title' => __('MoveJS', 'wp-super-cache'),
        'desc'  => __('Automatically moves every Javascripts to the footer of pages.', 'wp-super-cache')
    );

    return $list;
}

if( function_exists('add_cacheaction') ){
    add_cacheaction('wpsc_filter_list', 'wp_cache_movejs_list');
}