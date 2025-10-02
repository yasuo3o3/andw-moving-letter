<?php
/**
 * Archive template for Moving Letter posts
 * Template Name: Moving Letter Archive
 */

get_header(); ?>

<div class="ml-archive-container">
    <header class="ml-archive-header">
        <h1 class="ml-archive-title"><?php esc_htandw_e('お客様の声', 'andw-moving-letter'); ?></h1>
        <p class="ml-archive-description">
            <?php esc_htandw_e('お客様からいただいた貴重なお声をご紹介いたします。', 'andw-moving-letter'); ?>
        </p>
    </header>

    <?php andw_render_search_form(); ?>

    <?php
    // 検索・フィルターパラメータの取得
    $nonce_ok = ( isset( $_GET['andw_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['andw_nonce'] ) ), 'andw_search' ) );
    $search_query = ( $nonce_ok && isset($_GET['s']) ) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $tour_code_filter = ( $nonce_ok && isset($_GET['tour_code']) ) ? sanitize_text_field(wp_unslash($_GET['tour_code'])) : '';
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    // カスタムクエリの構築
    $args = array(
        'post_type' => 'andw-moving-letter',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    // 検索クエリの追加
    if (!empty($search_query)) {
        $args['s'] = $search_query;
        
        // メタフィールドも検索対象に含める
        add_filter('posts_search', 'andw_extend_search_to_meta', 10, 2);
    }

    // ツアーコードフィルターの追加
    if (!empty($tour_code_filter)) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta_queryは仕様上必要
        $args['meta_query'] = array(
            array(
                'key' => 'andw_tour_code',
                'value' => $tour_code_filter,
                'compare' => 'LIKE'
            )
        );
    }

    $query = new WP_Query($args);
    ?>

    <?php if (!empty($search_query) || !empty($tour_code_filter)): ?>
        <div class="ml-results-info">
            <span class="ml-results-count">
                <?php 
                echo esc_html( sprintf(
                    /* translators: %d: 検索結果の投稿数(件数)。 */
                    _n('%d件のお客様の声が見つかりました', '%d件のお客様の声が見つかりました', $query->found_posts, 'andw-moving-letter'),
                    $query->found_posts
                ) );
                ?>
            </span>
            
            <?php if (!empty($search_query)): ?>
                <span class="ml-search-term">
                    <?php 
                    /* translators: %s: 検索キーワード文字列。 */
                    echo esc_html( sprintf(esc_htandw__('検索: "%s"', 'andw-moving-letter'), $search_query) ); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($tour_code_filter)): ?>
                <span class="ml-filter-term">
                    <?php 
                    /* translators: %s: ツアーコード文字列。 */
                    echo esc_html( sprintf(esc_htandw__('ツアーコード: "%s"', 'andw-moving-letter'), $tour_code_filter) ); ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($query->have_posts()): ?>
        <div class="ml-archive-grid">
            <?php while ($query->have_posts()): $query->the_post(); ?>
                <?php andw_render_archive_card(get_post()); ?>
            <?php endwhile; ?>
        </div>

        <?php andw_render_pagination($query); ?>
        
    <?php else: ?>
        <div class="ml-no-results">
            <div class="ml-no-results-icon">📝</div>
            <h2 class="ml-no-results-title"><?php esc_htandw_e('お客様の声が見つかりませんでした', 'andw-moving-letter'); ?></h2>
            <p class="ml-no-results-message">
                <?php if (!empty($search_query) || !empty($tour_code_filter)): ?>
                    <?php esc_htandw_e('検索条件を変更して再度お試しください。', 'andw-moving-letter'); ?>
                <?php else: ?>
                    <?php esc_htandw_e('現在、表示できるお客様の声がございません。', 'andw-moving-letter'); ?>
                <?php endif; ?>
            </p>
            <?php if (!empty($search_query) || !empty($tour_code_filter)): ?>
                <p class="ml-no-results-suggestion">
                    <a href="<?php echo esc_url(get_post_type_archive_link('andw-moving-letter')); ?>">
                        <?php esc_htandw_e('すべてのお客様の声を見る', 'andw-moving-letter'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>

<?php
// 検索フィルターを元に戻す
if (!empty($search_query)) {
    remove_filter('posts_search', 'andw_extend_search_to_meta', 10, 2);
}
?>

<?php get_footer(); ?>

<?php
/**
 * 検索フォームの表示
 */
function andw_render_search_form() {
    $nonce_ok = ( isset( $_GET['andw_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['andw_nonce'] ) ), 'andw_search' ) );
    $search_query = ( $nonce_ok && isset($_GET['s']) ) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $tour_code_filter = ( $nonce_ok && isset($_GET['tour_code']) ) ? sanitize_text_field(wp_unslash($_GET['tour_code'])) : '';
    
    // 利用可能なツアーコードを取得
    $tour_codes = andw_get_available_tour_codes();
    ?>
    <div class="ml-search-filters">
        <form class="ml-search-form" method="get" action="<?php echo esc_url(get_post_type_archive_link('andw-moving-letter')); ?>">
            <div class="ml-search-field">
                <label for="ml-search-input"><?php esc_htandw_e('キーワード検索', 'andw-moving-letter'); ?></label>
                <input type="text" 
                       id="ml-search-input" 
                       name="s" 
                       value="<?php echo esc_attr($search_query); ?>" 
                       placeholder="<?php esc_attr_e('ニックネーム、プラン名、お便り内容から検索...', 'andw-moving-letter'); ?>">
            </div>
            
            <div class="ml-search-field">
                <label for="ml-tour-code-select"><?php esc_htandw_e('ツアーコード', 'andw-moving-letter'); ?></label>
                <select id="ml-tour-code-select" name="tour_code">
                    <option value=""><?php esc_htandw_e('すべて', 'andw-moving-letter'); ?></option>
                    <?php foreach ($tour_codes as $code): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($tour_code_filter, $code); ?>>
                            <?php echo esc_html($code); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php wp_nonce_field( 'andw_search', 'andw_nonce', false ); ?>
            
            <div class="ml-search-buttons">
                <button type="submit" class="ml-search-submit">
                    <?php esc_htandw_e('検索', 'andw-moving-letter'); ?>
                </button>
                
                <?php if (!empty($search_query) || !empty($tour_code_filter)): ?>
                    <a href="<?php echo esc_url(get_post_type_archive_link('andw-moving-letter')); ?>" class="ml-search-reset">
                        <?php esc_htandw_e('リセット', 'andw-moving-letter'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php
}

/**
 * アーカイブカードの表示
 */
function andw_render_archive_card($post) {
    $nickname = get_post_meta($post->ID, 'andw_nickname', true);
    $plan_title = get_post_meta($post->ID, 'andw_plan_title', true);
    $plan_url = get_post_meta($post->ID, 'andw_plan_url', true);
    $body = get_post_meta($post->ID, 'andw_body', true);
    $tour_code = get_post_meta($post->ID, 'andw_tour_code', true);
    ?>
    <article class="ml-archive-card">
        <div class="ml-archive-card-header">
            <h2 class="ml-archive-card-title">
                <?php echo $post->post_title ? esc_html($post->post_title) : esc_htandw__('お客様の声', 'andw-moving-letter'); ?>
            </h2>
            <time class="ml-archive-card-date" datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>">
                <?php echo esc_html(get_the_date('Y年m月d日', $post)); ?>
            </time>
        </div>

        <?php if ($nickname || $tour_code): ?>
            <div class="ml-archive-card-meta">
                <?php if ($nickname): ?>
                    <span class="ml-archive-card-meta-item is-nickname">
                        👤 <?php echo esc_html($nickname); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($tour_code): ?>
                    <span class="ml-archive-card-meta-item is-tour-code">
                        🏷️ <?php echo esc_html($tour_code); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($body): ?>
            <div class="ml-archive-card-body">
                <?php echo wp_kses_post(wpautop($body)); ?>
            </div>
        <?php endif; ?>

        <?php if ($plan_title): ?>
            <footer class="ml-archive-card-footer">
                <div class="ml-archive-card-plan">
                    <strong><?php esc_htandw_e('ツアープラン:', 'andw-moving-letter'); ?></strong>
                    <?php if ($plan_url): ?>
                        <a href="<?php echo esc_url($plan_url); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($plan_title); ?>
                            <span aria-hidden="true">↗</span>
                        </a>
                    <?php else: ?>
                        <?php echo esc_html($plan_title); ?>
                    <?php endif; ?>
                </div>
            </footer>
        <?php endif; ?>
    </article>
    <?php
}

/**
 * ページネーションの表示
 */
function andw_render_pagination($query) {
    $big = 999999999;
    
    $pagination_args = array(
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages,
        'prev_text' => esc_htandw__('« 前へ', 'andw-moving-letter'),
        'next_text' => esc_htandw__('次へ »', 'andw-moving-letter'),
        'type' => 'list',
        'end_size' => 3,
        'mid_size' => 3,
    );
    
    $pagination = paginate_links($pagination_args);
    
    if ($pagination) {
        echo '<nav class="ml-pagination" aria-label="' . esc_attr__('ページナビゲーション', 'andw-moving-letter') . '">';
        echo wp_kses_post($pagination);
        echo '</nav>';
    }
}

/**
 * 利用可能なツアーコードを取得
 */
function andw_get_available_tour_codes() {
    // キャッシュキーの生成
    $cache_key = 'andw_available_tour_codes';
    $tour_codes = wp_cache_get( $cache_key, 'andw-moving-letter' );
    
    if ( false === $tour_codes ) {
        global $wpdb;
        
        // WP APIでは複雑な集約処理（DISTINCT + カンマ区切り値の展開）が困難なため直クエリを使用
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WP APIでは代替不可（DISTINCT meta_value + カンマ区切り値の集約処理）
        $results = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = %s 
            AND p.post_type = %s 
            AND p.post_status = 'publish'
            AND pm.meta_value != ''
            ORDER BY pm.meta_value
        ", 'andw_tour_code', 'andw-moving-letter'));
        
        $tour_codes = array();
        foreach ($results as $codes) {
            $codes_array = explode(',', $codes);
            foreach ($codes_array as $code) {
                $code = trim($code);
                if (!empty($code) && !in_array($code, $tour_codes)) {
                    $tour_codes[] = $code;
                }
            }
        }
        
        sort($tour_codes);
        
        // 5分間キャッシュ
        wp_cache_set( $cache_key, $tour_codes, 'andw-moving-letter', 5 * MINUTE_IN_SECONDS );
    }
    
    return $tour_codes;
}

/**
 * メタフィールドを検索対象に含める
 */
function andw_extend_search_to_meta($search, $wp_query) {
    global $wpdb;
    
    if (empty($search) || !$wp_query->is_main_query()) {
        return $search;
    }
    
    $q = $wp_query->query_vars;
    $n = !empty($q['exact']) ? '' : '%';
    
    $search = '';
    $searchand = '';
    
    foreach ((array) $q['search_terms'] as $term) {
        $term = esc_sql($wpdb->esc_like($term));
        $search .= "{$searchand}(";
        $search .= "({$wpdb->posts}.post_title LIKE '{$n}{$term}{$n}')";
        $search .= " OR ({$wpdb->posts}.post_content LIKE '{$n}{$term}{$n}')";
        $search .= " OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm 
            WHERE pm.post_id = {$wpdb->posts}.ID 
            AND pm.meta_key IN ('andw_nickname', 'andw_plan_title', 'andw_body', 'andw_tour_code')
            AND pm.meta_value LIKE '{$n}{$term}{$n}'
        )";
        $search .= ")";
        $searchand = ' AND ';
    }
    
    if (!empty($search)) {
        $search = " AND ({$search}) ";
        if (!is_user_logged_in()) {
            $search .= " AND ({$wpdb->posts}.post_password = '') ";
        }
    }
    
    return $search;
}
?>