<?php
/**
 * Template part for displaying a single moving letter card
 * 
 * @param WP_Post $post The post object
 * @param array $args Additional arguments
 */

if (!defined('ABSPATH')) {
    exit;
}

// デフォルト引数
$args = wp_parse_args($args, array(
    'show_excerpt' => false,
    'excerpt_length' => 100,
    'show_date' => false,
    'show_link' => false,
    'css_class' => 'ml-card'
));

$post = get_post($post);
if (!$post) {
    return;
}

// メタデータ取得
$nickname = get_post_meta($post->ID, 'andw_nickname', true);
$plan_title = get_post_meta($post->ID, 'andw_plan_title', true);
$plan_url = get_post_meta($post->ID, 'andw_plan_url', true);
$body = get_post_meta($post->ID, 'andw_body', true);
$tour_code = get_post_meta($post->ID, 'andw_tour_code', true);

// 本文の処理
$content = '';
if ($body) {
    if ($args['show_excerpt']) {
        $content = andw_excerpt($body, $args['excerpt_length']);
    } else {
        $content = $body;
    }
}
?>

<div class="<?php echo esc_attr($args['css_class']); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>">
    <div class="ml-card-content">
        
        <?php if ($args['show_date']): ?>
            <div class="ml-card-date">
                <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>">
                    <?php echo esc_html(get_the_date('Y年m月d日', $post)); ?>
                </time>
            </div>
        <?php endif; ?>

        <?php if ($content): ?>
            <div class="ml-card-body">
                <?php if ($args['show_excerpt']): ?>
                    <?php echo esc_html($content); ?>
                <?php else: ?>
                    <?php echo wp_kses_post(wpautop($content)); ?>
                <?php endif; ?>
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
                    <a href="<?php echo esc_url($plan_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       title="<?php 
                       /* translators: %s: プランタイトル */
                       printf(esc_attr__('%s のページを開く', 'andw-moving-letter'), esc_attr($plan_title)); ?>">
                        <?php echo esc_html($plan_title); ?>
                        <span class="ml-external-link-icon" aria-hidden="true">↗</span>
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

        <?php if ($args['show_link']): ?>
            <div class="ml-card-link">
                <a href="<?php echo esc_url(get_permalink($post)); ?>" 
                   class="ml-card-read-more">
                    <?php esc_html_e('詳細を見る', 'andw-moving-letter'); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>