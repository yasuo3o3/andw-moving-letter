<?php
if (!defined('ABSPATH')) {
    exit;
}

function ml_register_settings() {
    add_action('admin_menu', 'ml_add_settings_page');
    add_action('admin_init', 'ml_settings_init');
}

function ml_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=moving_letter',
        __('Moving Letter 設定', 'moving-letter'),
        __('設定', 'moving-letter'),
        'manage_options',
        'moving-letter-settings',
        'ml_settings_page_callback'
    );
}

function ml_settings_init() {
    register_setting(
        'ml_settings_group',
        'ml_settings',
        array(
            'sanitize_callback' => 'ml_sanitize_settings',
            'default' => ml_get_default_settings()
        )
    );

    add_settings_section(
        'ml_display_settings',
        __('表示設定', 'moving-letter'),
        'ml_display_settings_callback',
        'ml_settings'
    );

    add_settings_section(
        'ml_responsive_settings',
        __('レスポンシブ設定', 'moving-letter'),
        'ml_responsive_settings_callback',
        'ml_settings'
    );

    add_settings_section(
        'ml_animation_settings',
        __('アニメーション設定', 'moving-letter'),
        'ml_animation_settings_callback',
        'ml_settings'
    );

    // 表示設定フィールド
    add_settings_field(
        'rows',
        __('表示行数', 'moving-letter'),
        'ml_settings_field_callback',
        'ml_settings',
        'ml_display_settings',
        array(
            'field' => 'rows',
            'type' => 'number',
            'min' => 1,
            'max' => 10,
            'description' => __('カードを表示する行数（1〜10）', 'moving-letter')
        )
    );

    add_settings_field(
        'gap',
        __('カード間隔', 'moving-letter'),
        'ml_settings_field_callback',
        'ml_settings',
        'ml_display_settings',
        array(
            'field' => 'gap',
            'type' => 'number',
            'min' => 0,
            'max' => 100,
            'unit' => 'px',
            'description' => __('カード間の間隔をピクセル単位で指定', 'moving-letter')
        )
    );

    // レスポンシブ設定フィールド
    $devices = array(
        'desktop' => __('デスクトップ', 'moving-letter'),
        'tablet' => __('タブレット', 'moving-letter'),
        'mobile' => __('モバイル', 'moving-letter')
    );

    foreach ($devices as $device => $label) {
        add_settings_field(
            'visible_' . $device,
            /* translators: %s: デバイス名(デスクトップ/タブレット/モバイル)。 */
            sprintf(__('%s 表示枚数', 'moving-letter'), $label),
            'ml_settings_field_callback',
            'ml_settings',
            'ml_responsive_settings',
            array(
                'field' => 'visible_' . $device,
                'type' => 'number',
                'min' => 1,
                'max' => 10,
                /* translators: %s: デバイス名(デスクトップ/タブレット/モバイル)。 */
                'description' => sprintf(__('%sで同時に表示するカード数', 'moving-letter'), $label)
            )
        );

        add_settings_field(
            'preload_' . $device,
            /* translators: %s: デバイス名(デスクトップ/タブレット/モバイル)。 */
            sprintf(__('%s 読み込み枚数', 'moving-letter'), $label),
            'ml_settings_field_callback',
            'ml_settings',
            'ml_responsive_settings',
            array(
                'field' => 'preload_' . $device,
                'type' => 'number',
                'min' => 1,
                'max' => 20,
                /* translators: %s: デバイス名(デスクトップ/タブレット/モバイル)。 */
                'description' => sprintf(__('%sで事前に読み込むカード数（表示枚数より多くしてください）', 'moving-letter'), $label)
            )
        );
    }

    // アニメーション設定フィールド
    add_settings_field(
        'speed',
        __('スクロール速度', 'moving-letter'),
        'ml_settings_field_callback',
        'ml_settings',
        'ml_animation_settings',
        array(
            'field' => 'speed',
            'type' => 'number',
            'min' => 10,
            'max' => 200,
            'unit' => '秒',
            'description' => __('1サイクルにかかる時間（秒）。小さいほど速くなります。', 'moving-letter')
        )
    );

    add_settings_field(
        'pause_on_hover',
        __('ホバー時停止', 'moving-letter'),
        'ml_settings_field_callback',
        'ml_settings',
        'ml_animation_settings',
        array(
            'field' => 'pause_on_hover',
            'type' => 'checkbox',
            'description' => __('マウスを乗せた時にアニメーションを一時停止する', 'moving-letter')
        )
    );
}

function ml_get_default_settings() {
    return array(
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
}

function ml_sanitize_settings($settings) {
    // CSRF保護とCapability確認
    if ( ! current_user_can( 'manage_options' ) ) {
        return get_option( 'ml_settings', ml_get_default_settings() );
    }
    
    // CSRF Nonce確認
    $nonce = isset($_POST['_wpnonce'])
        ? sanitize_text_field( wp_unslash($_POST['_wpnonce']) )
        : '';
    if ( empty($nonce) || !wp_verify_nonce($nonce, 'ml_settings_group-options') ) {
        return get_option( 'ml_settings', ml_get_default_settings() );
    }
    
    $defaults = ml_get_default_settings();
    $sanitized = array();

    // 数値フィールドのサニタイゼーション
    $numeric_fields = array(
        'visible_desktop' => array('min' => 1, 'max' => 10),
        'preload_desktop' => array('min' => 1, 'max' => 20),
        'visible_tablet' => array('min' => 1, 'max' => 10),
        'preload_tablet' => array('min' => 1, 'max' => 20),
        'visible_mobile' => array('min' => 1, 'max' => 10),
        'preload_mobile' => array('min' => 1, 'max' => 20),
        'rows' => array('min' => 1, 'max' => 10),
        'speed' => array('min' => 10, 'max' => 200),
        'gap' => array('min' => 0, 'max' => 100)
    );

    foreach ($numeric_fields as $field => $bounds) {
        $value = isset($settings[$field]) ? intval($settings[$field]) : $defaults[$field];
        $sanitized[$field] = max($bounds['min'], min($bounds['max'], $value));
    }

    // チェックボックスフィールド
    $sanitized['pause_on_hover'] = !empty($settings['pause_on_hover']);

    // 整合性チェック
    $devices = array('desktop', 'tablet', 'mobile');
    foreach ($devices as $device) {
        if ($sanitized['preload_' . $device] <= $sanitized['visible_' . $device]) {
            $sanitized['preload_' . $device] = $sanitized['visible_' . $device] + 2;
        }
    }

    return $sanitized;
}

function ml_display_settings_callback() {
    echo '<p>' . esc_html__('カードの表示に関する基本設定です。', 'moving-letter') . '</p>';
}

function ml_responsive_settings_callback() {
    echo '<p>' . esc_html__('デバイス別の表示設定です。画面サイズに応じて表示されるカード数を調整できます。', 'moving-letter') . '</p>';
}

function ml_animation_settings_callback() {
    echo '<p>' . esc_html__('スクロールアニメーションの動作を設定できます。', 'moving-letter') . '</p>';
}

function ml_settings_field_callback($args) {
    $settings = ml_get_settings();
    $field = $args['field'];
    $value = isset($settings[$field]) ? $settings[$field] : '';
    $type = $args['type'];
    $description = isset($args['description']) ? $args['description'] : '';
    $unit = isset($args['unit']) ? $args['unit'] : '';

    $field_id = 'ml_settings[' . $field . ']';
    $field_name = 'ml_settings[' . $field . ']';

    switch ($type) {
        case 'number':
            $min = isset($args['min']) ? $args['min'] : '';
            $max = isset($args['max']) ? $args['max'] : '';
            ?>
            <input type="number" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   value="<?php echo esc_attr($value); ?>"
                   <?php echo $min !== '' ? 'min="' . esc_attr($min) . '"' : ''; ?>
                   <?php echo $max !== '' ? 'max="' . esc_attr($max) . '"' : ''; ?>
                   class="small-text">
            <?php if ($unit): ?>
                <span class="ml-unit"><?php echo esc_html($unit); ?></span>
            <?php endif; ?>
            <?php
            break;

        case 'checkbox':
            ?>
            <input type="checkbox" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   value="1" 
                   <?php checked(1, $value); ?>>
            <?php
            break;

        case 'select':
            $options = isset($args['options']) ? $args['options'] : array();
            ?>
            <select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_name); ?>">
                <?php foreach ($options as $option_value => $option_label): ?>
                    <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
            break;

        default:
            ?>
            <input type="text" 
                   id="<?php echo esc_attr($field_id); ?>" 
                   name="<?php echo esc_attr($field_name); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text">
            <?php
    }

    if ($description) {
        echo '<p class="description">' . esc_html($description) . '</p>';
    }
}

function ml_settings_page_callback() {
    if (isset($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag set by Settings API after nonce verification
        add_settings_error(
            'ml_messages',
            'ml_message',
            __('設定が保存されました。', 'moving-letter'),
            'success'
        );
    }

    settings_errors('ml_messages');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Moving Letter 設定', 'moving-letter'); ?></h1>
        
        <div class="ml-settings-info">
            <p><?php esc_html_e('ここで設定した値は、ショートコードで属性が指定されなかった場合のデフォルト値として使用されます。', 'moving-letter'); ?></p>
            <p><strong><?php esc_html_e('ショートコード例:', 'moving-letter'); ?></strong> 
               <code>[moving_letter]</code> または 
               <code>[moving_letter tour_code="A-1,B-3" visible_desktop="4"]</code>
            </p>
        </div>

        <form action="options.php" method="post">
            <?php
            settings_fields('ml_settings_group');
            do_settings_sections('ml_settings');
            submit_button(esc_html__('設定を保存', 'moving-letter'));
            ?>
        </form>

        <div class="ml-settings-help">
            <h2><?php esc_html_e('ヘルプ', 'moving-letter'); ?></h2>
            <div class="ml-help-sections">
                <div class="ml-help-section">
                    <h3><?php esc_html_e('表示設定', 'moving-letter'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('表示行数:', 'moving-letter'); ?></strong> <?php esc_html_e('同時に表示するカードの行数を指定します。', 'moving-letter'); ?></li>
                        <li><strong><?php esc_html_e('カード間隔:', 'moving-letter'); ?></strong> <?php esc_html_e('カード同士の間隔をピクセル単位で指定します。', 'moving-letter'); ?></li>
                    </ul>
                </div>

                <div class="ml-help-section">
                    <h3><?php esc_html_e('レスポンシブ設定', 'moving-letter'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('表示枚数:', 'moving-letter'); ?></strong> <?php esc_html_e('各デバイスで同時に画面に表示されるカードの数です。', 'moving-letter'); ?></li>
                        <li><strong><?php esc_html_e('読み込み枚数:', 'moving-letter'); ?></strong> <?php esc_html_e('スムーズなアニメーションのために事前に読み込むカードの数です。表示枚数より多く設定してください。', 'moving-letter'); ?></li>
                    </ul>
                </div>

                <div class="ml-help-section">
                    <h3><?php esc_html_e('アニメーション設定', 'moving-letter'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('スクロール速度:', 'moving-letter'); ?></strong> <?php esc_html_e('カードが1サイクル流れる時間を秒単位で指定します。数値が小さいほど速くなります。', 'moving-letter'); ?></li>
                        <li><strong><?php esc_html_e('ホバー時停止:', 'moving-letter'); ?></strong> <?php esc_html_e('マウスを乗せた時にアニメーションを一時停止します。', 'moving-letter'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="ml-settings-preview">
            <h2><?php esc_html_e('プレビュー', 'moving-letter'); ?></h2>
            <p><?php esc_html_e('現在の設定でのショートコード:', 'moving-letter'); ?></p>
            <div class="ml-shortcode-preview">
                <code><?php echo esc_html(ml_generate_shortcode_preview()); ?></code>
                <button type="button" class="button button-small ml-copy-shortcode" data-clipboard-text="<?php echo esc_attr(ml_generate_shortcode_preview()); ?>">
                    <?php esc_html_e('コピー', 'moving-letter'); ?>
                </button>
            </div>
        </div>
    </div>

    <style>
    .ml-settings-info {
        background: #f1f1f1;
        padding: 15px;
        border-left: 4px solid #0073aa;
        margin: 20px 0;
    }
    
    .ml-unit {
        margin-left: 5px;
        color: #666;
        font-style: italic;
    }
    
    .ml-help-sections {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }
    
    .ml-help-section {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
    }
    
    .ml-help-section h3 {
        margin-top: 0;
        color: #0073aa;
    }
    
    .ml-shortcode-preview {
        background: #f9f9f9;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        position: relative;
    }
    
    .ml-shortcode-preview code {
        background: transparent;
        font-size: 14px;
        padding: 0;
    }
    
    .ml-copy-shortcode {
        margin-left: 10px;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyButton = document.querySelector('.ml-copy-shortcode');
        if (copyButton) {
            copyButton.addEventListener('click', function() {
                const text = this.getAttribute('data-clipboard-text');
                navigator.clipboard.writeText(text).then(function() {
                    const originalText = copyButton.textContent;
                    copyButton.textContent = <?php echo wp_json_encode( __( 'コピーしました！', 'moving-letter' ) ); ?>;
                    setTimeout(function() {
                        copyButton.textContent = originalText;
                    }, 2000);
                });
            });
        }
    });
    </script>
    <?php
}

function ml_generate_shortcode_preview() {
    $settings = ml_get_settings();
    $defaults = ml_get_default_settings();
    
    $attributes = array();
    
    foreach ($settings as $key => $value) {
        if ($value != $defaults[$key]) {
            if (is_bool($value)) {
                $attributes[] = $key . '="' . ($value ? 'true' : 'false') . '"';
            } else {
                $attributes[] = $key . '="' . $value . '"';
            }
        }
    }
    
    $shortcode = '[moving_letter';
    if (!empty($attributes)) {
        $shortcode .= ' ' . implode(' ', $attributes);
    }
    $shortcode .= ']';
    
    return $shortcode;
}

// 初期化はメインプラグインファイルから呼び出される