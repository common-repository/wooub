<?php
/**
 * @package Izi-woocommerce-uber-delivery
 */
/*
Plugin Name: Phanes UberRush for WooCommerce
Plugin URI: https://phanes.com/
Description: <strong>UberRUSH is an on-demand</strong> delivery network powered by Uber that lets you and your customer track the exact location of your delivery from any device.
Version: 0.1
Author: Phanes
Author URI: https://phanes.com
License: GPLv2 or later
Text Domain: Phanes UberRush
*/

@ini_set('display_errors', 'on');
@error_reporting(E_ALL | E_STRICT);
define('_PS_DEBUG_SQL_', true);
if ( version_compare( PHP_VERSION, '5.3.7', '<' ) ) {
    function uber_php_outdated()
    {
        add_action( is_network_admin() ? 'network_admin_notices' : 'admin_notices', create_function( '', 'echo \'<div class="updated"><h3>Uber delivery on demand</h3><p>To install the plugin - <strong>PHP 5.3.7</strong> or higher is required.</p></div>\';' ) );
    }
    add_action( 'init', 'uber_php_outdated' );
} else {
    include dirname(__FILE__).DIRECTORY_SEPARATOR.'autoload.php';
    $app = '\UBER\Classes\UberRush';
    call_user_func( array( $app, 'run' ) );
    new $app();
}
