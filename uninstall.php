<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Purge site-specific data for andw_moving_letter plugin
 *
 * @param int|null $blog_id Blog ID for multisite context
 */
function andw_purge_site_data( $blog_id = null ) {
    // 設定オプション削除
    delete_option( 'andw_settings' );

    // CPT投稿削除（このプラグイン専用の投稿という前提）
    $posts = get_posts( array(
        'post_type'      => 'andw_moving_letter',
        'numberposts'    => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ) );

    if ( $posts ) {
        foreach ( $posts as $post_id ) {
            wp_delete_post( $post_id, true );
        }
    }

    // 関連メタ削除
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup, prepared
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
        $wpdb->esc_like( 'andw_' ) . '%'
    ) );
}

// マルチサイト対応
if ( is_multisite() ) {
    $sites = get_sites( array( 'number' => 0 ) );
    foreach ( $sites as $site ) {
        switch_to_blog( (int) $site->blog_id );
        andw_purge_site_data( $site->blog_id );
        restore_current_blog();
    }
} else {
    andw_purge_site_data();
}