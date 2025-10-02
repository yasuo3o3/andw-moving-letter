<?php
if (!defined('ABSPATH')) {
    exit;
}

function andw_get_settings($key = null) {
    $defaults = array(
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
    
    $saved_settings = get_option('andw_settings', array());
    $settings = is_array($saved_settings) ? array_merge($defaults, $saved_settings) : $defaults;
    
    if ($key) {
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    return $settings;
}

function andw_sanitize_tour_codes($codes) {
    if (empty($codes)) {
        return array();
    }
    
    $codes_array = explode(',', $codes);
    return array_map('sanitize_text_field', array_map('trim', $codes_array));
}

function andw_get_posts($args = array()) {
    $defaults = array(
        'post_type' => 'andw_moving_letter',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $args = is_array($args) ? array_merge($defaults, $args) : $defaults;
    
    if (!empty($args['tour_code'])) {
        $tour_codes = is_array($args['tour_code']) ? $args['tour_code'] : andw_sanitize_tour_codes($args['tour_code']);
        
        if (!empty($tour_codes)) {
            $meta_query = array(
                'relation' => 'OR'
            );
            
            foreach ($tour_codes as $code) {
                $meta_query[] = array(
                    'key' => 'andw_tour_code',
                    'value' => $code,
                    'compare' => 'LIKE'
                );
            }
            
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta_queryは仕様上必要
            $args['meta_query'] = $meta_query;
        }
        
        unset($args['tour_code']);
    }
    
    return get_posts($args);
}

function andw_excerpt($text, $length = 100) {
    $text = wp_strip_all_tags($text);
    
    // lengthを整数化してmb_substrに渡す（PHP 8.3互換性）
    $length = (int) $length;
    
    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length) . '...';
    }
    
    return $text;
}

function andw_get_card_html($post) {
    $nickname = get_post_meta($post->ID, 'andw_nickname', true);
    $plan_title = get_post_meta($post->ID, 'andw_plan_title', true);
    $plan_url = get_post_meta($post->ID, 'andw_plan_url', true);
    $body = get_post_meta($post->ID, 'andw_body', true);
    $tour_code = get_post_meta($post->ID, 'andw_tour_code', true);
    
    ob_start();
    ?>
    <div class="ml-card" data-post-id="<?php echo esc_attr($post->ID); ?>">
        <div class="ml-card-content">
            <?php if ($body): ?>
                <div class="ml-card-body">
                    <?php echo wp_kses_post(wpautop($body)); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($nickname): ?>
                <div class="ml-card-author">
                    <?php echo esc_html($nickname); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($plan_title): ?>
                <div class="ml-card-plan">
                    <?php if ($plan_url): ?>
                        <a href="<?php echo esc_url($plan_url); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($plan_title); ?>
                        </a>
                    <?php else: ?>
                        <?php echo esc_html($plan_title); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($tour_code): ?>
                <div class="ml-card-tour-code">
                    <?php echo esc_html($tour_code); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}