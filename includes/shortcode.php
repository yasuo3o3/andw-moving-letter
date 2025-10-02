<?php
if (!defined('ABSPATH')) {
    exit;
}

function andw_register_shortcode() {
    add_shortcode('andw_moving_letter', 'andw_shortcode_callback');
}

function andw_shortcode_callback($atts) {
    $settings = andw_get_settings();
    
    $atts = shortcode_atts(array(
        'rows' => $settings['rows'],
        'visible_desktop' => $settings['visible_desktop'],
        'preload_desktop' => $settings['preload_desktop'],
        'visible_tablet' => $settings['visible_tablet'],
        'preload_tablet' => $settings['preload_tablet'],
        'visible_mobile' => $settings['visible_mobile'],
        'preload_mobile' => $settings['preload_mobile'],
        'tour_code' => '',
        'speed' => $settings['speed'],
        'pause_on_hover' => $settings['pause_on_hover'] ? 'true' : 'false',
        'gap' => $settings['gap']
    ), $atts, 'andw_moving_letter');
    
    $query_args = array(
        'posts_per_page' => intval($atts['preload_desktop'])
    );
    
    if (!empty($atts['tour_code'])) {
        $query_args['tour_code'] = $atts['tour_code'];
    }
    
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['tour_code']) && !empty($_GET['tour_code'])) {
        $query_args['tour_code'] = sanitize_text_field(wp_unslash($_GET['tour_code']));
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
    
    $posts = andw_get_posts($query_args);
    
    if (empty($posts)) {
        return '<p class="ml-no-posts">' . __('お客様の声が見つかりませんでした。', 'andw-moving-letter') . '</p>';
    }
    
    $unique_id = 'ml-' . wp_rand(1000, 9999);
    
    ob_start();
    ?>
    <div class="ml-container" 
         id="<?php echo esc_attr($unique_id); ?>"
         data-visible-desktop="<?php echo esc_attr($atts['visible_desktop']); ?>"
         data-preload-desktop="<?php echo esc_attr($atts['preload_desktop']); ?>"
         data-visible-tablet="<?php echo esc_attr($atts['visible_tablet']); ?>"
         data-preload-tablet="<?php echo esc_attr($atts['preload_tablet']); ?>"
         data-visible-mobile="<?php echo esc_attr($atts['visible_mobile']); ?>"
         data-preload-mobile="<?php echo esc_attr($atts['preload_mobile']); ?>"
         data-rows="<?php echo esc_attr($atts['rows']); ?>"
         data-speed="<?php echo esc_attr($atts['speed']); ?>"
         data-pause-on-hover="<?php echo esc_attr($atts['pause_on_hover']); ?>"
         data-gap="<?php echo esc_attr($atts['gap']); ?>">
        
        <?php for ($row = 0; $row < intval($atts['rows']); $row++): ?>
            <div class="ml-row ml-row-<?php echo esc_attr( $row ); ?>" data-direction="<?php echo esc_attr( ($row % 2 === 0) ? 'ltr' : 'rtl' ); ?>">
                <div class="ml-track">
                    <?php
                    $rows_count = max(1, intval($atts['rows'])); // Prevent division by zero
                    // intdivで整数化し、余りをfloat→int変換警告なしで処理
                    $posts_per_row = intdiv(count($posts), $rows_count);
                    $remainder = count($posts) % $rows_count;
                    
                    // 余りがある場合は末尾の行に均等に分散（UI一貫性を保持）
                    if ($row < $remainder) {
                        $posts_per_row += 1;
                        $offset = $row * $posts_per_row;
                    } else {
                        $offset = $remainder * ($posts_per_row + 1) + ($row - $remainder) * $posts_per_row;
                    }
                    
                    $posts_for_row = array_slice($posts, $offset, $posts_per_row);
                    foreach ($posts_for_row as $post):
                        echo wp_kses_post( andw_get_card_html($post) );
                    endforeach;
                    ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <?php
    
    return ob_get_clean();
}

function andw_ajax_load_more() {
    check_ajax_referer('andw_load_more', 'nonce');
    
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 10;
    $tour_code = isset($_POST['tour_code']) ? sanitize_text_field(wp_unslash($_POST['tour_code'])) : '';
    
    $args = array(
        'offset' => $offset,
        'posts_per_page' => $posts_per_page
    );
    
    if (!empty($tour_code)) {
        $args['tour_code'] = $tour_code;
    }
    
    $posts = andw_get_posts($args);
    
    if (!empty($posts)) {
        foreach ($posts as $post) {
            echo wp_kses_post( andw_get_card_html($post) );
        }
    }
    
    wp_die();
}

function andw_inline_script() {
    if (andw_has_shortcode()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AndwMovingLetterMarquee !== 'undefined') {
                const containers = document.querySelectorAll('.ml-container');
                containers.forEach(function(container) {
                    new AndwMovingLetterMarquee(container);
                });
            }
        });
        </script>
        <?php
    }
}

function andw_has_shortcode() {
    global $post;
    
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'andw_moving_letter')) {
        return true;
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
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    return false;
}