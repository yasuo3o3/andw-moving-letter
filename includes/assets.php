<?php
if (!defined('ABSPATH')) {
    exit;
}

function andw_enqueue_assets() {
    global $post;
    
    $should_enqueue = false;
    
    if (is_singular('andw_moving_letter') || 
        is_post_type_archive('andw_moving_letter') || 
        (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'andw_moving_letter'))) {
        $should_enqueue = true;
    }
    
    if (is_home() || is_front_page()) {
        $widgets = wp_get_sidebars_widgets();
        foreach ($widgets as $sidebar => $widget_list) {
            if (is_array($widget_list)) {
                foreach ($widget_list as $widget) {
                    if (strpos($widget, 'text') === 0) {
                        $widget_content = get_option('widget_text');
                        if (is_array($widget_content)) {
                            foreach ($widget_content as $instance) {
                                if (isset($instance['text']) && has_shortcode($instance['text'], 'andw_moving_letter')) {
                                    $should_enqueue = true;
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    if (!$should_enqueue) {
        return;
    }
    
    // CSS with filemtime cache busting
    $css_path = ANDW_MOVING_LETTER_PLUGIN_DIR . 'assets/css/andw-moving-letter.css';
    $css_version = file_exists($css_path) ? filemtime($css_path) : ANDW_MOVING_LETTER_VERSION;
    
    wp_enqueue_style(
        'andw-moving-letter-style',
        ANDW_MOVING_LETTER_PLUGIN_URL . 'assets/css/andw-moving-letter.css',
        array(),
        $css_version
    );
    
    // JavaScript with filemtime cache busting
    $js_path = ANDW_MOVING_LETTER_PLUGIN_DIR . 'assets/js/marquee.js';
    $js_version = file_exists($js_path) ? filemtime($js_path) : ANDW_MOVING_LETTER_VERSION;
    
    wp_enqueue_script(
        'andw-moving-letter-marquee',
        ANDW_MOVING_LETTER_PLUGIN_URL . 'assets/js/marquee.js',
        array(),
        $js_version,
        true
    );
    
    // Use wp_json_encode for safe JavaScript embedding
    wp_localize_script('andw-moving-letter-marquee', 'mlAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('andw_load_more')
    ));
    
    if (is_post_type_archive('andw_moving_letter')) {
        $archive_css_path = ANDW_MOVING_LETTER_PLUGIN_DIR . 'assets/css/archive.css';
        $archive_css_version = file_exists($archive_css_path) ? filemtime($archive_css_path) : ANDW_MOVING_LETTER_VERSION;
        
        wp_enqueue_style(
            'andw-moving-letter-archive',
            ANDW_MOVING_LETTER_PLUGIN_URL . 'assets/css/archive.css',
            array('andw-moving-letter-style'),
            $archive_css_version
        );
    }
}

function andw_admin_enqueue_assets($hook) {
    global $post_type;
    
    if ($post_type !== 'andw_moving_letter') {
        return;
    }
    
    wp_enqueue_style(
        'andw-moving-letter-admin',
        ANDW_MOVING_LETTER_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        ANDW_MOVING_LETTER_VERSION
    );
    
    // Admin JavaScript can be added later if needed
}

add_action('wp_head', 'andw_inline_styles');
function andw_inline_styles() {
    if (!andw_has_shortcode() && !is_singular('andw_moving_letter') && !is_post_type_archive('andw_moving_letter')) {
        return;
    }
    
    $settings = andw_get_settings();
    ?>
    <style id="ml-dynamic-styles">
    :root {
        --ml-gap: <?php echo intval($settings['gap']); ?>px;
        --ml-speed: <?php echo intval($settings['speed']); ?>s;
    }
    
    @media (max-width: 768px) {
        .ml-container {
            --ml-visible-cards: <?php echo intval($settings['visible_mobile']); ?>;
        }
    }
    
    @media (min-width: 769px) and (max-width: 1024px) {
        .ml-container {
            --ml-visible-cards: <?php echo intval($settings['visible_tablet']); ?>;
        }
    }
    
    @media (min-width: 1025px) {
        .ml-container {
            --ml-visible-cards: <?php echo intval($settings['visible_desktop']); ?>;
        }
    }
    </style>
    <?php
}

