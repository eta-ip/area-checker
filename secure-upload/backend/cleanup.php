<?php
require_once __DIR__ . '/config.php';

$now = time();

// DBファイルが存在しない場合は処理終了
if (!file_exists(DB_FILE) || !is_readable(DB_FILE)) {
    exit;
}

$json = file_get_contents(DB_FILE);
$data = json_decode($json, true);

// JSONが無効または配列でない場合は終了
if (!is_array($data)) {
    exit;
}

$updated = [];
$deleted = [];

foreach ($data as $token => $info) {
    $expires = (int) ($info['expires_at'] ?? 0);
    $filename = $info['stored_name'] ?? '';
    $original = $info['original_name'] ?? '不明';

    if ($expires < $now) {
        $filePath = UPLOAD_DIR . $filename;

        if ($filename && file_exists($filePath)) {
            if (@unlink($filePath)) {
                $deleted[] = "{$original}（{$filename}）";
            } else {
                // 削除失敗もログ
                $deleted[] = "{$original}（{$filename}） ➜ 削除失敗";
            }
        }
    } else {
        $updated[$token] = $info;
    }
}

// DBファイルの更新
file_put_contents(DB_FILE, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 削除通知メール（もし削除されたファイルがあれば）
if (!empty($deleted)) {
    $subject = "【ETA】期限切れファイルの自動削除報告";
    $body = "以下のファイルが自動削除されました（" . date('Y-m-d H:i:s') . " 時点）:\n\n";
    foreach ($deleted as $file) {
        $body .= "- " . $file . "\n";
    }
    $body .= "\n※このメールは自動送信されています。";

    $headers = implode("\r\n", [
        "From: ETAファイル管理 <no-reply@eta-ip.com>",
        "Content-Type: text/plain; charset=UTF-8"
    ]);

    mail(ADMIN_EMAIL, $subject, $body, $headers);
}

// CLIまたはログ用出力
if (php_sapi_name() === 'cli') {
    if ($deleted) {
        echo "✅ 削除されたファイル一覧:\n" . implode("\n", $deleted) . "\n";
    } else {
        echo "📁 削除対象はありません（" . date('Y-m-d H:i:s') . "）\n";
    }
}
