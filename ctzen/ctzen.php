<?php
/*
Plugin Name: C.T.ZEN
Plugin URI:  https://ctzen.de
Description: Mach Kommunalpolitik für die Bürger:innen deiner Gemeinde transparent und interaktiv.
Version:     0.2
Author:      Christian Hübner
Text Domain: ctzen
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CTZEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTZEN_URL', plugin_dir_url(  __FILE__ ) );

require_once CTZEN_DIR . 'includes/class-ctzen-db.php';
require_once CTZEN_DIR . 'includes/admin/class-ctzen-admin.php';
require_once CTZEN_DIR . 'includes/frontend/class-ctzen-frontend.php';

register_activation_hook( __FILE__, ['CTZEN_DB', 'install'] );
register_deactivation_hook(__FILE__, ['CTZEN_DB', 'uninstall']);

add_action('plugins_loaded', function(){
    CTZEN_Admin::init();
    CTZEN_Frontend::init();
});
