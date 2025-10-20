<?php
if (!defined('ABSPATH')) {
    exit;
}

class Andw_CSV_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_andw_csv_import', array($this, 'handle_csv_import'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=andw_moving_letter',
            'CSV一括追加',
            'CSV一括追加',
            'edit_andw_moving_letters',
            'andw-csv-import',
            array($this, 'admin_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ('andw_moving_letter_page_andw-csv-import' !== $hook) {
            return;
        }

        wp_enqueue_style('andw-csv-admin', ANDW_MOVING_LETTER_PLUGIN_URL . 'assets/css/admin.css', array(), ANDW_MOVING_LETTER_VERSION);
    }

    public function admin_page() {
        if (!current_user_can('edit_andw_moving_letters')) {
            wp_die(__('このページにアクセスする権限がありません。'));
        }

        $messages = $this->get_admin_messages();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('お客様の声 - CSV一括追加', 'andw-moving-letter'); ?></h1>

            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="notice notice-<?php echo esc_attr($message['type']); ?> is-dismissible">
                        <p><?php echo wp_kses_post($message['text']); ?></p>
                        <?php if (!empty($message['details'])): ?>
                            <details>
                                <summary>詳細を表示</summary>
                                <ul>
                                    <?php foreach ($message['details'] as $detail): ?>
                                        <li><?php echo esc_html($detail); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="andw-csv-import-container">
                <div class="card">
                    <h2>CSVファイルをアップロード</h2>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('andw_csv_import', 'andw_csv_nonce'); ?>
                        <input type="hidden" name="action" value="andw_csv_import">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="csv_file"><?php esc_html_e('CSVファイル', 'andw-moving-letter'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                                    <p class="description">
                                        拡張子が .csv のファイルを選択してください。最大ファイルサイズ: <?php echo esc_html(wp_max_upload_size() / 1024 / 1024); ?>MB
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="dry_run"><?php esc_html_e('プレビューモード', 'andw-moving-letter'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="dry_run" name="dry_run" value="1">
                                        実際の投稿作成を行わず、プレビューのみ実行する
                                    </label>
                                    <p class="description">
                                        チェックすると、インポート内容の確認のみ行い、実際の投稿は作成されません。
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button('CSVをインポート', 'primary', 'submit', false); ?>
                    </form>
                </div>

                <div class="card">
                    <h2>CSVフォーマット</h2>
                    <p>以下の形式でCSVファイルを作成してください：</p>

                    <pre class="andw-csv-format">title,nickname,plan_title,plan_url,body,tour_code
"素晴らしい旅行でした","Y.S.","沖縄3日間ツアー","https://example.com/tour/okinawa","とても楽しい旅行でした。","A-1"
"最高の思い出","田中太郎","北海道ツアー","","雪景色が美しく、食事も最高でした。","C-5"</pre>

                    <h3>フィールド説明</h3>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>フィールド名</th>
                                <th>必須/任意</th>
                                <th>説明</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>title</code></td>
                                <td><strong>必須</strong></td>
                                <td>投稿のタイトル</td>
                            </tr>
                            <tr>
                                <td><code>nickname</code></td>
                                <td>任意</td>
                                <td>お客様のニックネーム</td>
                            </tr>
                            <tr>
                                <td><code>plan_title</code></td>
                                <td>任意</td>
                                <td>ツアープラン名</td>
                            </tr>
                            <tr>
                                <td><code>plan_url</code></td>
                                <td>任意</td>
                                <td>プランページのURL</td>
                            </tr>
                            <tr>
                                <td><code>body</code></td>
                                <td><strong>必須</strong></td>
                                <td>お便りの本文</td>
                            </tr>
                            <tr>
                                <td><code>tour_code</code></td>
                                <td>任意</td>
                                <td>ツアーコード（複数の場合はカンマ区切り）</td>
                            </tr>
                        </tbody>
                    </table>

                    <p>
                        <a href="<?php echo esc_url($this->get_sample_download_url()); ?>" class="button button-secondary">
                            サンプルCSVをダウンロード
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <style>
        .andw-csv-import-container {
            max-width: 1000px;
        }
        .andw-csv-import-container .card {
            margin-bottom: 20px;
        }
        .andw-csv-format {
            background: #f6f7f7;
            border: 1px solid #ddd;
            padding: 15px;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre;
        }
        </style>
        <?php
    }

    public function handle_csv_import() {
        if (!current_user_can('edit_andw_moving_letters')) {
            wp_die(__('このページにアクセスする権限がありません。'));
        }

        if (!wp_verify_nonce($_POST['andw_csv_nonce'], 'andw_csv_import')) {
            wp_die(__('セキュリティチェックに失敗しました。'));
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->set_admin_message('error', 'ファイルのアップロードに失敗しました。');
            wp_redirect(admin_url('edit.php?post_type=andw_moving_letter&page=andw-csv-import'));
            exit;
        }

        $file = $_FILES['csv_file'];
        if ($file['type'] !== 'text/csv' && !str_ends_with($file['name'], '.csv')) {
            $this->set_admin_message('error', 'CSVファイルを選択してください。');
            wp_redirect(admin_url('edit.php?post_type=andw_moving_letter&page=andw-csv-import'));
            exit;
        }

        $dry_run = !empty($_POST['dry_run']);
        $importer = new Andw_CSV_Import();
        $result = $importer->import_from_file($file['tmp_name'], $dry_run);

        if ($result['success']) {
            $message_type = 'success';
            $message_text = $result['message'];
            $details = array();

            if ($dry_run && isset($result['preview'])) {
                $details[] = '=== プレビュー（最初の5件） ===';
                foreach ($result['preview'] as $index => $row) {
                    $details[] = sprintf('%d. %s (%s)',
                        $index + 1,
                        $row['title'],
                        isset($row['nickname']) ? $row['nickname'] : '匿名'
                    );
                }
            }

            if (!$dry_run && isset($result['created_count'])) {
                $details[] = sprintf('作成された投稿数: %d件', $result['created_count']);
                if ($result['error_count'] > 0) {
                    $details[] = sprintf('エラー件数: %d件', $result['error_count']);
                    if (!empty($result['errors'])) {
                        $details = array_merge($details, array_slice($result['errors'], 0, 10));
                    }
                }
            }
        } else {
            $message_type = 'error';
            $message_text = $result['message'];
            $details = isset($result['errors']) ? array_slice($result['errors'], 0, 10) : array();
        }

        $this->set_admin_message($message_type, $message_text, $details);
        wp_redirect(admin_url('edit.php?post_type=andw_moving_letter&page=andw-csv-import'));
        exit;
    }

    private function set_admin_message($type, $text, $details = array()) {
        set_transient('andw_csv_admin_message', array(
            'type' => $type,
            'text' => $text,
            'details' => $details
        ), 30);
    }

    private function get_admin_messages() {
        $message = get_transient('andw_csv_admin_message');
        if ($message) {
            delete_transient('andw_csv_admin_message');
            return array($message);
        }
        return array();
    }

    private function get_sample_download_url() {
        return add_query_arg(array(
            'action' => 'andw_download_sample_csv',
            'nonce' => wp_create_nonce('andw_sample_csv')
        ), admin_url('admin-post.php'));
    }
}

add_action('admin_post_andw_download_sample_csv', function() {
    if (!current_user_can('edit_andw_moving_letters')) {
        wp_die(__('このページにアクセスする権限がありません。'));
    }

    if (!wp_verify_nonce($_GET['nonce'], 'andw_sample_csv')) {
        wp_die(__('セキュリティチェックに失敗しました。'));
    }

    $importer = new Andw_CSV_Import();
    $sample_content = $importer->get_sample_csv_content();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="sample-voices.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    echo $sample_content;
    exit;
});

new Andw_CSV_Admin();