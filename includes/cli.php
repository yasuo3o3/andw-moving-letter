<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP-CLI command for Moving Letter plugin
 */
class ML_CLI_Command {

    /**
     * Purge all Moving Letter data (posts, meta, settings)
     *
     * WARNING: This command will permanently delete all Moving Letter data.
     * Use with caution in production environments.
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip confirmation prompt
     *
     * ## EXAMPLES
     *
     *     wp ml purge --yes
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function purge( $args, $assoc_args ) {
        if ( ! isset( $assoc_args['yes'] ) ) {
            WP_CLI::confirm( '警告: この操作はすべてのMoving Letterデータを永久に削除します。続行しますか?' );
        }

        $deleted_posts = 0;
        $deleted_meta = 0;

        // 投稿削除
        $posts = get_posts( array(
            'post_type'      => 'moving_letter',
            'numberposts'    => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'suppress_filters' => true, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- CLI用途のため
        ) );

        if ( $posts ) {
            foreach ( $posts as $post_id ) {
                wp_delete_post( $post_id, true );
                $deleted_posts++;
            }
        }

        // メタデータ削除
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- CLI cleanup operation, prepared & sanitized
        $meta_result = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            $wpdb->esc_like( 'andw_' ) . '%'
        ) );
        $deleted_meta = $meta_result ? $meta_result : 0;

        // 設定削除
        $option_deleted = delete_option( 'andw_settings' );

        WP_CLI::success( sprintf( 
            '削除完了: 投稿 %d件, メタデータ %d件, 設定 %s',
            $deleted_posts,
            $deleted_meta,
            $option_deleted ? '1件' : '0件'
        ) );
    }

    /**
     * Show Moving Letter statistics
     *
     * ## EXAMPLES
     *
     *     wp ml stats
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function stats( $args, $assoc_args ) {
        // 投稿数
        $posts_count = wp_count_posts( 'moving_letter' );
        $total_posts = $posts_count->publish + $posts_count->draft + $posts_count->private + $posts_count->pending;

        // メタデータ数
        global $wpdb;
        $cache_key = 'andw_cli_meta_count_' . md5( 'andw_' );
        $meta_count = wp_cache_get( $cache_key, 'andw-moving-letter' );
        
        if ( false === $meta_count ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- CLIでの集計用途なので直SQL
            $meta_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
                $wpdb->esc_like( 'andw_' ) . '%'
            ) );
            wp_cache_set( $cache_key, $meta_count, 'andw-moving-letter', 5 * MINUTE_IN_SECONDS );
        }

        // 設定の存在確認
        $settings_exist = get_option( 'andw_settings' ) !== false;

        WP_CLI::line( '=== Moving Letter 統計 ===' );
        WP_CLI::line( sprintf( '投稿数: %d件', $total_posts ) );
        WP_CLI::line( sprintf( '  公開済み: %d件', $posts_count->publish ) );
        WP_CLI::line( sprintf( '  下書き: %d件', $posts_count->draft ) );
        WP_CLI::line( sprintf( '  非公開: %d件', $posts_count->private ) );
        WP_CLI::line( sprintf( 'メタデータ: %d件', $meta_count ) );
        WP_CLI::line( sprintf( '設定: %s', $settings_exist ? '存在' : '未設定' ) );
    }
}

// Register WP-CLI command if available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'ml', 'ML_CLI_Command' );
}