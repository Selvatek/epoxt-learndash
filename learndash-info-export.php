<?php

/**
 * Plugin Name: learndash-info-export for Rites De Femmes
 * Plugin URI:https://www.selvatek.com
 * Description: learndash-info-export for Rites De Femmes
 * Author: Antoine Serin
 * Author URI: https://www.selvatek.com
 * Text Domain: Export user info learndash 
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//charger le code se trouvant dans d'autres fichiers
require_once __DIR__ . '/includes/role_and_capabilities.php';
require_once __DIR__ . '/includes/learndash-info-export-main-functions.php';
