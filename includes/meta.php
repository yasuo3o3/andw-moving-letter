<?php
if (!defined('ABSPATH')) {
    exit;
}

function ml_register_meta_fields() {
    $meta_fields = array(
        'ml_nickname' => array(
            'type' => 'string',
            'description' => 'ニックネーム',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ),
        'ml_plan_title' => array(
            'type' => 'string',
            'description' => 'プラン名',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        ),
        'ml_plan_url' => array(
            'type' => 'string',
            'description' => 'プランURL',
            'single' => true,
            'sanitize_callback' => 'esc_url_raw',
            'show_in_rest' => true,
        ),
        'ml_body' => array(
            'type' => 'string',
            'description' => 'お便り本文',
            'single' => true,
            'sanitize_callback' => 'ml_sanitize_textarea',
            'show_in_rest' => true,
        ),
        'ml_tour_code' => array(
            'type' => 'string',
            'description' => 'ツアーコード',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => true,
        )
    );
    
    foreach ($meta_fields as $key => $args) {
        register_post_meta('moving_letter', $key, $args);
    }
}

function ml_sanitize_textarea($value) {
    return wp_kses_post($value);
}

function ml_add_meta_boxes() {
    add_meta_box(
        'ml_meta_box',
        __('お客様の声 詳細情報', 'moving-letter'),
        'ml_meta_box_callback',
        'moving_letter',
        'normal',
        'high'
    );
}

function ml_meta_box_callback($post) {
    wp_nonce_field(basename(__FILE__), 'ml_meta_box_nonce');
    
    $nickname = get_post_meta($post->ID, 'ml_nickname', true);
    $plan_title = get_post_meta($post->ID, 'ml_plan_title', true);
    $plan_url = get_post_meta($post->ID, 'ml_plan_url', true);
    $body = get_post_meta($post->ID, 'ml_body', true);
    $tour_code = get_post_meta($post->ID, 'ml_tour_code', true);
    ?>
    
    <table class="form-table">
        <tr>
            <th><label for="ml_nickname"><?php esc_html_e('ニックネーム', 'moving-letter'); ?></label></th>
            <td>
                <input type="text" id="ml_nickname" name="ml_nickname" 
                       value="<?php echo esc_attr($nickname); ?>" 
                       class="regular-text" 
                       placeholder="例：Y.S. さん">
                <p class="description"><?php esc_html_e('お客様のニックネームや頭文字を入力してください。', 'moving-letter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th><label for="ml_plan_title"><?php esc_html_e('プラン名', 'moving-letter'); ?></label></th>
            <td>
                <input type="text" id="ml_plan_title" name="ml_plan_title" 
                       value="<?php echo esc_attr($plan_title); ?>" 
                       class="regular-text" 
                       placeholder="例：沖縄3日間ツアー">
                <p class="description"><?php esc_html_e('該当するツアープラン名を入力してください。', 'moving-letter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th><label for="ml_plan_url"><?php esc_html_e('プランURL', 'moving-letter'); ?></label></th>
            <td>
                <input type="url" id="ml_plan_url" name="ml_plan_url" 
                       value="<?php echo esc_url($plan_url); ?>" 
                       class="regular-text" 
                       placeholder="https://example.com/tour/plan-123">
                <p class="description"><?php esc_html_e('プランページのURLを入力してください（任意）。', 'moving-letter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th><label for="ml_tour_code"><?php esc_html_e('ツアーコード', 'moving-letter'); ?></label></th>
            <td>
                <input type="text" id="ml_tour_code" name="ml_tour_code" 
                       value="<?php echo esc_attr($tour_code); ?>" 
                       class="regular-text" 
                       placeholder="例：A-1,B-3">
                <p class="description"><?php esc_html_e('ツアーコードを入力してください。複数ある場合はカンマ区切りで入力。', 'moving-letter'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th><label for="ml_body"><?php esc_html_e('お便り本文', 'moving-letter'); ?></label></th>
            <td>
                <?php
                wp_editor($body, 'ml_body', array(
                    'textarea_name' => 'ml_body',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => true
                ));
                ?>
                <p class="description"><?php esc_html_e('お客様からいただいたお便りの内容を入力してください。', 'moving-letter'); ?></p>
            </td>
        </tr>
    </table>
    
    <?php
}

function ml_save_meta_box_data($post_id) {
    // Nonce検証 - セキュリティ対策
    $nonce = isset($_POST['ml_meta_box_nonce'])
        ? sanitize_text_field( wp_unslash($_POST['ml_meta_box_nonce']) )
        : '';
    if ( empty($nonce) || !wp_verify_nonce($nonce, basename(__FILE__)) ) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (get_post_type($post_id) !== 'moving_letter') {
        return;
    }
    
    // メタフィールド定義 - サニタイズコールバック付き
    $meta_fields = array(
        'ml_nickname' => 'sanitize_text_field',
        'ml_plan_title' => 'sanitize_text_field',
        'ml_plan_url' => 'esc_url_raw',
        'ml_body' => 'ml_sanitize_textarea',
        'ml_tour_code' => 'sanitize_text_field'
    );
    
    // 各メタフィールドの処理 - セキュリティ対策済み
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    foreach ($meta_fields as $key => $sanitize_callback) {
        $value_raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
        if (!empty($value_raw)) {
            $value = is_callable($sanitize_callback)
                ? call_user_func($sanitize_callback, $value_raw)
                : ( is_array($value_raw) ? array_map('sanitize_text_field', $value_raw) : sanitize_text_field($value_raw) );
            update_post_meta($post_id, $key, $value);
        } else {
            delete_post_meta($post_id, $key);
        }
    }
    // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}