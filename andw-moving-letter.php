<?php
/**
 * Plugin Name:       andW Moving Letter
 * Plugin URI:        https://your-domain.jp/
 * Description:       お客様の声を美しい動くカードで表示するWordPressプラグイン
 * Version:           1.0.1
 * Author:            Netservice
 * Author URI:  https://netservice.jp/
 * License:           GPLv2 or later
 * Text Domain:       andw-moving-letter
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.8
 * Requires PHP:      8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ANDW_MOVING_LETTER_VERSION', '1.0.1');
define('ANDW_MOVING_LETTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ANDW_MOVING_LETTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ANDW_MOVING_LETTER_TEXT_DOMAIN', 'andw-moving-letter');

/**
 * Main plugin class
 */
class AndwMovingLetter {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hook into WordPress initialization
        add_action('plugins_loaded', array($this, 'init'), 10);
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_activation_hook(__FILE__, array($this, 'add_capabilities'));
        register_activation_hook(__FILE__, array($this, 'migrate_autoload_no'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // WordPress 4.6+ では、プラグインヘッダーの Text Domain と Domain Path が
        // 適切に設定されていれば翻訳ファイルは自動で読み込まれる
        // load_plugin_textdomain() は不要（WordPress.org配布の場合）
        
        // Include required files
        $this->include_files();
        
        // Setup hooks
        $this->setup_hooks();
        
        // Initialize components only after WordPress is fully loaded
        add_action('init', array($this, 'init_components'), 20);
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        $files = array(
            'includes/helpers.php',
            'includes/cpt.php',
            'includes/meta.php',
            'includes/shortcode.php', 
            'includes/assets.php',
            'includes/settings.php',
            'includes/cli.php'
        );
        
        foreach ($files as $file) {
            $file_path = ANDW_MOVING_LETTER_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', 'andw_enqueue_assets');
        add_action('wp_head', 'andw_inline_styles');
        add_action('wp_footer', 'andw_inline_script');
        
        // AJAX hooks
        add_action('wp_ajax_andw_load_more', 'andw_ajax_load_more');
        add_action('wp_ajax_nopriv_andw_load_more', 'andw_ajax_load_more');
        
        // Admin hooks
        add_action('admin_enqueue_scripts', 'andw_admin_enqueue_assets');
        add_action('add_meta_boxes', 'andw_add_meta_boxes');
        add_action('save_post', 'andw_save_meta_box_data');
        
        // Cache invalidation hooks
        add_action('save_post', array($this, 'invalidate_tour_codes_cache'));
        add_action('delete_post', array($this, 'invalidate_tour_codes_cache'));
        add_action('wp_trash_post', array($this, 'invalidate_tour_codes_cache'));
        add_action('untrash_post', array($this, 'invalidate_tour_codes_cache'));
        
        // Admin columns
        add_filter('manage_andw_moving_letter_posts_columns', 'andw_admin_columns');
        add_action('manage_andw_moving_letter_posts_custom_column', 'andw_admin_columns_content', 10, 2);
        add_filter('manage_edit-andw_moving_letter_sortable_columns', 'andw_admin_sortable_columns');
        add_action('pre_get_posts', 'andw_admin_posts_orderby');
        
        // Settings
        if (function_exists('andw_register_settings')) {
            andw_register_settings();
        }
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Register post type
        if (function_exists('andw_register_post_type')) {
            andw_register_post_type();
        }
        
        // Register meta fields
        if (function_exists('andw_register_meta_fields')) {
            andw_register_meta_fields();
        }
        
        // Register shortcode
        if (function_exists('andw_register_shortcode')) {
            andw_register_shortcode();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Include required files
        $this->include_files();
        
        // Register post type for flush_rewrite_rules
        if (function_exists('andw_register_post_type')) {
            andw_register_post_type();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Add default settings
        if (!get_option('andw_settings')) {
            $default_settings = array(
                'visible_desktop' => 5,
                'preload_desktop' => 7,
                'visible_tablet' => 3,
                'preload_tablet' => 5,
                'visible_mobile' => 2,
                'preload_mobile' => 4,
                'rows' => 3,
                'speed' => 50,
                'pause_on_hover' => true,
                'gap' => 20
            );
            add_option('andw_settings', $default_settings, '', 'no');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
        
        // Remove capabilities from roles (cleanup on deactivation)
        $roles = array( 'administrator', 'editor' );
        $caps = array(
            'read_andw_moving_letter',
            'read_andw_moving_letters',
            'create_andw_moving_letters',
            'edit_andw_moving_letter',
            'edit_andw_moving_letters',
            'edit_others_andw_moving_letters',
            'publish_andw_moving_letters',
            'delete_andw_moving_letter',
            'delete_andw_moving_letters',
            'delete_others_andw_moving_letters',
            'edit_published_andw_moving_letters',
            'delete_published_andw_moving_letters',
            'delete_private_andw_moving_letters',
            'edit_private_andw_moving_letters',
        );
        foreach ( $roles as $role_name ) {
            if ( $role = get_role( $role_name ) ) {
                foreach ( $caps as $cap ) {
                    $role->remove_cap( $cap );
                }
            }
        }
    }
    
    /**
     * Add capabilities to roles
     */
    public function add_capabilities() {
        $roles = array( 'administrator', 'editor' );
        $caps = array(
            'read_andw_moving_letter',
            'read_andw_moving_letters',
            'create_andw_moving_letters',
            'edit_andw_moving_letter',
            'edit_andw_moving_letters',
            'edit_others_andw_moving_letters',
            'publish_andw_moving_letters',
            'delete_andw_moving_letter',
            'delete_andw_moving_letters',
            'delete_others_andw_moving_letters',
            'edit_published_andw_moving_letters',
            'delete_published_andw_moving_letters',
            'delete_private_andw_moving_letters',
            'edit_private_andw_moving_letters',
        );
        foreach ( $roles as $role_name ) {
            if ( $role = get_role( $role_name ) ) {
                foreach ( $caps as $cap ) {
                    $role->add_cap( $cap );
                }
            }
        }
    }
    
    /**
     * Migrate existing andw_settings autoload from 'yes' to 'no'
     */
    public function migrate_autoload_no() {
        $existing_value = get_option('andw_settings');
        if ( $existing_value !== false ) {
            // 既存オプションを削除して autoload='no' で再作成
            delete_option('andw_settings');
            add_option('andw_settings', $existing_value, '', 'no');
        }
    }
    
    /**
     * ツアーコードキャッシュの無効化
     */
    public function invalidate_tour_codes_cache($post_id) {
        if (get_post_type($post_id) === 'moving-letter') {
            wp_cache_delete('andw_available_tour_codes', 'moving-letter');
        }
    }
}

// Initialize the plugin
AndwMovingLetter::get_instance();

