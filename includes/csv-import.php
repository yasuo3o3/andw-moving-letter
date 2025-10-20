<?php
if (!defined('ABSPATH')) {
    exit;
}

class Andw_CSV_Import {

    private $required_fields = array('title', 'body');
    private $valid_fields = array(
        'title',
        'nickname',
        'plan_title',
        'plan_url',
        'body',
        'tour_code'
    );

    public function import_from_file($file_path, $dry_run = false) {
        if (!file_exists($file_path)) {
            return array(
                'success' => false,
                'message' => 'CSVファイルが見つかりません: ' . $file_path
            );
        }

        $csv_data = $this->parse_csv($file_path);
        if (!$csv_data['success']) {
            return $csv_data;
        }

        $validation_result = $this->validate_csv_data($csv_data['data']);
        if (!$validation_result['success']) {
            return $validation_result;
        }

        if ($dry_run) {
            return array(
                'success' => true,
                'message' => sprintf('%d件のデータが見つかりました。--dry-runオプションのため実際の投稿作成は行いませんでした。', count($csv_data['data'])),
                'preview' => array_slice($csv_data['data'], 0, 5)
            );
        }

        return $this->create_posts($csv_data['data']);
    }

    private function parse_csv($file_path) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return array(
                'success' => false,
                'message' => 'CSVファイルが読み込めませんでした。'
            );
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return array(
                'success' => false,
                'message' => 'CSVヘッダーが読み込めませんでした。'
            );
        }

        $header = array_map('trim', $header);
        $data = array();
        $line_number = 2;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                fclose($handle);
                return array(
                    'success' => false,
                    'message' => sprintf('行%d: カラム数が一致しません。ヘッダー: %d, データ: %d',
                        $line_number, count($header), count($row))
                );
            }

            $row_data = array_combine($header, $row);
            $row_data = array_map('trim', $row_data);
            $data[] = $row_data;
            $line_number++;
        }

        fclose($handle);

        return array(
            'success' => true,
            'data' => $data,
            'headers' => $header
        );
    }

    private function validate_csv_data($data) {
        $errors = array();

        if (empty($data)) {
            return array(
                'success' => false,
                'message' => 'CSVファイルにデータが含まれていません。'
            );
        }

        $first_row = $data[0];
        $missing_required = array_diff($this->required_fields, array_keys($first_row));
        if (!empty($missing_required)) {
            return array(
                'success' => false,
                'message' => '必須フィールドが不足しています: ' . implode(', ', $missing_required)
            );
        }

        foreach ($data as $index => $row) {
            $line_number = $index + 2;

            foreach ($this->required_fields as $field) {
                if (empty($row[$field])) {
                    $errors[] = sprintf('行%d: %sが空です。', $line_number, $field);
                }
            }

            if (!empty($row['plan_url']) && !filter_var($row['plan_url'], FILTER_VALIDATE_URL)) {
                $errors[] = sprintf('行%d: plan_urlの形式が正しくありません。', $line_number);
            }
        }

        if (!empty($errors)) {
            return array(
                'success' => false,
                'message' => 'データ検証エラー:',
                'errors' => $errors
            );
        }

        return array('success' => true);
    }

    private function create_posts($data) {
        $created_count = 0;
        $error_count = 0;
        $errors = array();

        foreach ($data as $index => $row) {
            $line_number = $index + 2;
            $result = $this->create_single_post($row);

            if ($result['success']) {
                $created_count++;
            } else {
                $error_count++;
                $errors[] = sprintf('行%d: %s', $line_number, $result['message']);
            }
        }

        $message = sprintf('インポート完了: 成功 %d件, エラー %d件', $created_count, $error_count);

        return array(
            'success' => $error_count === 0,
            'message' => $message,
            'created_count' => $created_count,
            'error_count' => $error_count,
            'errors' => $errors
        );
    }

    private function create_single_post($row) {
        $post_data = array(
            'post_type' => 'andw_moving_letter',
            'post_title' => sanitize_text_field($row['title']),
            'post_status' => 'publish',
            'post_content' => '',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'message' => $post_id->get_error_message()
            );
        }

        $meta_fields = array(
            'andw_nickname' => isset($row['nickname']) ? sanitize_text_field($row['nickname']) : '',
            'andw_plan_title' => isset($row['plan_title']) ? sanitize_text_field($row['plan_title']) : '',
            'andw_plan_url' => isset($row['plan_url']) ? esc_url_raw($row['plan_url']) : '',
            'andw_body' => isset($row['body']) ? wp_kses_post($row['body']) : '',
            'andw_tour_code' => isset($row['tour_code']) ? sanitize_text_field($row['tour_code']) : ''
        );

        foreach ($meta_fields as $key => $value) {
            if (!empty($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => '投稿を作成しました (ID: ' . $post_id . ')'
        );
    }

    public function get_sample_csv_content() {
        return "title,nickname,plan_title,plan_url,body,tour_code\n" .
               '"素晴らしい旅行でした","Y.S.","沖縄3日間ツアー","https://example.com/tour/okinawa","とても楽しい旅行でした。ガイドさんも親切で、観光地もすべて回ることができました。","A-1"' . "\n" .
               '"最高の思い出","田中太郎","北海道ツアー","","雪景色が美しく、食事も最高でした。特に海鮮丼は絶品でした。","C-5"' . "\n" .
               '"また行きたいです","佐藤花子","京都日帰りツアー","https://example.com/tour/kyoto","歴史を感じる素晴らしい旅でした。","B-2,B-3"';
    }
}