<?php
if (!defined('ABSPATH')) {
    exit;
}

function ml_register_post_type() {
    $labels = array(
        'name'                  => __('お客様の声（Moving Letter）', 'moving-letter'),
        'singular_name'         => __('お客様の声', 'moving-letter'),
        'menu_name'             => __('お客様の声', 'moving-letter'),
        'name_admin_bar'        => __('お客様の声', 'moving-letter'),
        'archives'              => __('お客様の声 一覧', 'moving-letter'),
        'attributes'            => __('お客様の声 属性', 'moving-letter'),
        'parent_item_colon'     => __('親のお客様の声:', 'moving-letter'),
        'all_items'             => __('すべてのお客様の声', 'moving-letter'),
        'add_new_item'          => __('新しいお客様の声を追加', 'moving-letter'),
        'add_new'               => __('新規追加', 'moving-letter'),
        'new_item'              => __('新しいお客様の声', 'moving-letter'),
        'edit_item'             => __('お客様の声を編集', 'moving-letter'),
        'update_item'           => __('お客様の声を更新', 'moving-letter'),
        'view_item'             => __('お客様の声を表示', 'moving-letter'),
        'view_items'            => __('お客様の声を表示', 'moving-letter'),
        'search_items'          => __('お客様の声を検索', 'moving-letter'),
        'not_found'             => __('見つかりませんでした', 'moving-letter'),
        'not_found_in_trash'    => __('ゴミ箱にはありません', 'moving-letter'),
        'featured_image'        => __('アイキャッチ画像', 'moving-letter'),
        'set_featured_image'    => __('アイキャッチ画像を設定', 'moving-letter'),
        'remove_featured_image' => __('アイキャッチ画像を削除', 'moving-letter'),
        'use_featured_image'    => __('アイキャッチ画像として使用', 'moving-letter'),
        'insert_into_item'      => __('お客様の声に挿入', 'moving-letter'),
        'uploaded_to_this_item' => __('このお客様の声にアップロード', 'moving-letter'),
        'items_list'            => __('お客様の声リスト', 'moving-letter'),
        'items_list_navigation' => __('お客様の声リストナビゲーション', 'moving-letter'),
        'filter_items_list'     => __('お客様の声リストをフィルタ', 'moving-letter'),
    );
    
    $args = array(
        'label'                 => __('お客様の声', 'moving-letter'),
        'description'           => __('お客様からいただいた声を管理します', 'moving-letter'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-testimonial',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => 'voices',
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'show_in_rest'          => true,
        'rest_base'             => 'moving_letter',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'rewrite'               => array('slug' => 'voices'),
        'map_meta_cap'          => true,
        'capability_type'       => array( 'moving_letter', 'moving_letters' ),
        'capabilities'          => array(
            'read_post'             => 'read_moving_letter',
            'read'                  => 'read_moving_letters',
            'create_posts'          => 'create_moving_letters',
            'edit_post'             => 'edit_moving_letter',
            'edit_posts'            => 'edit_moving_letters',
            'edit_others_posts'     => 'edit_others_moving_letters',
            'publish_posts'         => 'publish_moving_letters',
            'delete_post'           => 'delete_moving_letter',
            'delete_posts'          => 'delete_moving_letters',
            'delete_others_posts'   => 'delete_others_moving_letters',
            'edit_published_posts'  => 'edit_published_moving_letters',
            'delete_published_posts'=> 'delete_published_moving_letters',
            'delete_private_posts'  => 'delete_private_moving_letters',
            'edit_private_posts'    => 'edit_private_moving_letters',
        ),
    );
    
    register_post_type('moving_letter', $args);
}

function ml_admin_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['ml_nickname'] = __('ニックネーム', 'moving-letter');
            $new_columns['ml_tour_code'] = __('ツアーコード', 'moving-letter');
            $new_columns['ml_plan_title'] = __('プラン名', 'moving-letter');
        }
    }
    
    return $new_columns;
}

function ml_admin_columns_content($column, $post_id) {
    switch ($column) {
        case 'ml_nickname':
            $nickname = get_post_meta($post_id, 'ml_nickname', true);
            echo $nickname ? esc_html($nickname) : '—';
            break;
            
        case 'ml_tour_code':
            $tour_code = get_post_meta($post_id, 'ml_tour_code', true);
            echo $tour_code ? esc_html($tour_code) : '—';
            break;
            
        case 'ml_plan_title':
            $plan_title = get_post_meta($post_id, 'ml_plan_title', true);
            $plan_url = get_post_meta($post_id, 'ml_plan_url', true);
            
            if ($plan_title) {
                if ($plan_url) {
                    echo '<a href="' . esc_url($plan_url) . '" target="_blank" rel="noopener">' . esc_html($plan_title) . '</a>';
                } else {
                    echo esc_html($plan_title);
                }
            } else {
                echo '—';
            }
            break;
    }
}

function ml_admin_sortable_columns($sortable_columns) {
    $sortable_columns['ml_nickname'] = 'ml_nickname';
    $sortable_columns['ml_tour_code'] = 'ml_tour_code';
    $sortable_columns['ml_plan_title'] = 'ml_plan_title';
    
    return $sortable_columns;
}

function ml_admin_posts_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ('ml_nickname' === $orderby) {
        $query->set('meta_key', 'ml_nickname');
        $query->set('orderby', 'meta_value');
    } elseif ('ml_tour_code' === $orderby) {
        $query->set('meta_key', 'ml_tour_code');
        $query->set('orderby', 'meta_value');
    } elseif ('ml_plan_title' === $orderby) {
        $query->set('meta_key', 'ml_plan_title');
        $query->set('orderby', 'meta_value');
    }
}