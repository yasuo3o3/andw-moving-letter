<?php
if (!defined('ABSPATH')) {
    exit;
}

function andw_register_post_type() {
    $labels = array(
        'name'                  => __('お客様の声（Moving Letter）', 'andw-moving-letter'),
        'singular_name'         => __('お客様の声', 'andw-moving-letter'),
        'menu_name'             => __('お客様の声', 'andw-moving-letter'),
        'name_admin_bar'        => __('お客様の声', 'andw-moving-letter'),
        'archives'              => __('お客様の声 一覧', 'andw-moving-letter'),
        'attributes'            => __('お客様の声 属性', 'andw-moving-letter'),
        'parent_item_colon'     => __('親のお客様の声:', 'andw-moving-letter'),
        'all_items'             => __('すべてのお客様の声', 'andw-moving-letter'),
        'add_new_item'          => __('新しいお客様の声を追加', 'andw-moving-letter'),
        'add_new'               => __('新規追加', 'andw-moving-letter'),
        'new_item'              => __('新しいお客様の声', 'andw-moving-letter'),
        'edit_item'             => __('お客様の声を編集', 'andw-moving-letter'),
        'update_item'           => __('お客様の声を更新', 'andw-moving-letter'),
        'view_item'             => __('お客様の声を表示', 'andw-moving-letter'),
        'view_items'            => __('お客様の声を表示', 'andw-moving-letter'),
        'search_items'          => __('お客様の声を検索', 'andw-moving-letter'),
        'not_found'             => __('見つかりませんでした', 'andw-moving-letter'),
        'not_found_in_trash'    => __('ゴミ箱にはありません', 'andw-moving-letter'),
        'featured_image'        => __('アイキャッチ画像', 'andw-moving-letter'),
        'set_featured_image'    => __('アイキャッチ画像を設定', 'andw-moving-letter'),
        'remove_featured_image' => __('アイキャッチ画像を削除', 'andw-moving-letter'),
        'use_featured_image'    => __('アイキャッチ画像として使用', 'andw-moving-letter'),
        'insert_into_item'      => __('お客様の声に挿入', 'andw-moving-letter'),
        'uploaded_to_this_item' => __('このお客様の声にアップロード', 'andw-moving-letter'),
        'items_list'            => __('お客様の声リスト', 'andw-moving-letter'),
        'items_list_navigation' => __('お客様の声リストナビゲーション', 'andw-moving-letter'),
        'filter_items_list'     => __('お客様の声リストをフィルタ', 'andw-moving-letter'),
    );
    
    $args = array(
        'label'                 => __('お客様の声', 'andw-moving-letter'),
        'description'           => __('お客様からいただいた声を管理します', 'andw-moving-letter'),
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
        'rest_base'             => 'andw_moving_letter',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'rewrite'               => array('slug' => 'voices'),
        'map_meta_cap'          => true,
        'capability_type'       => array( 'andw_moving_letter', 'andw_moving_letters' ),
        'capabilities'          => array(
            'read_post'             => 'read_andw_moving_letter',
            'read'                  => 'read_andw_moving_letters',
            'create_posts'          => 'create_andw_moving_letters',
            'edit_post'             => 'edit_andw_moving_letter',
            'edit_posts'            => 'edit_andw_moving_letters',
            'edit_others_posts'     => 'edit_others_andw_moving_letters',
            'publish_posts'         => 'publish_andw_moving_letters',
            'delete_post'           => 'delete_andw_moving_letter',
            'delete_posts'          => 'delete_andw_moving_letters',
            'delete_others_posts'   => 'delete_others_andw_moving_letters',
            'edit_published_posts'  => 'edit_published_andw_moving_letters',
            'delete_published_posts'=> 'delete_published_andw_moving_letters',
            'delete_private_posts'  => 'delete_private_andw_moving_letters',
            'edit_private_posts'    => 'edit_private_andw_moving_letters',
        ),
    );
    
    register_post_type('andw_moving_letter', $args);
}

function andw_admin_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['andw_nickname'] = __('ニックネーム', 'andw-moving-letter');
            $new_columns['andw_tour_code'] = __('ツアーコード', 'andw-moving-letter');
            $new_columns['andw_plan_title'] = __('プラン名', 'andw-moving-letter');
        }
    }
    
    return $new_columns;
}

function andw_admin_columns_content($column, $post_id) {
    switch ($column) {
        case 'andw_nickname':
            $nickname = get_post_meta($post_id, 'andw_nickname', true);
            echo $nickname ? esc_html($nickname) : '—';
            break;
            
        case 'andw_tour_code':
            $tour_code = get_post_meta($post_id, 'andw_tour_code', true);
            echo $tour_code ? esc_html($tour_code) : '—';
            break;
            
        case 'andw_plan_title':
            $plan_title = get_post_meta($post_id, 'andw_plan_title', true);
            $plan_url = get_post_meta($post_id, 'andw_plan_url', true);
            
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

function andw_admin_sortable_columns($sortable_columns) {
    $sortable_columns['andw_nickname'] = 'andw_nickname';
    $sortable_columns['andw_tour_code'] = 'andw_tour_code';
    $sortable_columns['andw_plan_title'] = 'andw_plan_title';
    
    return $sortable_columns;
}

function andw_admin_posts_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    if ('andw_nickname' === $orderby) {
        $query->set('meta_key', 'andw_nickname');
        $query->set('orderby', 'meta_value');
    } elseif ('andw_tour_code' === $orderby) {
        $query->set('meta_key', 'andw_tour_code');
        $query->set('orderby', 'meta_value');
    } elseif ('andw_plan_title' === $orderby) {
        $query->set('meta_key', 'andw_plan_title');
        $query->set('orderby', 'meta_value');
    }
}