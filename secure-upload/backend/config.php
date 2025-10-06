<?php
// ============================================
// secure-upload 設定ファイル（本番用）
// ============================================

// 1. AES-256 暗号化キー（32バイト／hex形式）
defined('ENCRYPTION_KEY') || define('ENCRYPTION_KEY', 'e9a7c3fbd2e4486c8732f6bd345ad901e2b2f1e5e9bc312ba94e6deffedc12345');

// 2. 保存先ディレクトリ（末尾スラッシュ必須）
defined('UPLOAD_DIR') || define('UPLOAD_DIR', rtrim(__DIR__ . '/../storage/', '/') . '/');

// 3. アップロードファイル情報保存先（JSON形式）
defined('DB_FILE') || define('DB_FILE', UPLOAD_DIR . 'files.json');

// 4. 最大アップロードサイズ（バイト）※現在は256MB
defined('MAX_FILE_SIZE') || define('MAX_FILE_SIZE', 256 * 1024 * 1024);

// 5. ダウンロード有効期限（秒）→ 24時間
defined('EXPIRE_SECONDS') || define('EXPIRE_SECONDS', 60 * 60 * 24);

// 6. 管理者のメールアドレス
defined('ADMIN_EMAIL') || define('ADMIN_EMAIL', 'info@eta-ip.com');

// 7. 通知メール設定（true/false）
defined('ENABLE_UPLOAD_NOTIFICATION')   || define('ENABLE_UPLOAD_NOTIFICATION', true);
defined('ENABLE_DOWNLOAD_NOTIFICATION') || define('ENABLE_DOWNLOAD_NOTIFICATION', true);

// 8. 管理ログ表示の最大件数
defined('MAX_LOG_ENTRIES') || define('MAX_LOG_ENTRIES', 100);

// ============================================
// ✅ 日本語対応の通知メール送信関数（完全対応版）
// ============================================
function sendNotification(string $subject, string $message): void {
    if (!defined('ADMIN_EMAIL') || empty(ADMIN_EMAIL)) {
        error_log('通知先メールアドレスが未設定です。');
        return;
    }

    // 差出人情報（名前はエンコード）
    $fromName = mb_encode_mimeheader("エイタ国際企画", "UTF-8");
    $fromEmail = 'no-reply@eta-ip.com';
    $from = "{$fromName} <{$fromEmail}>";

    // ヘッダー（UTF-8指定、8bitエンコード）
    $headers = [
        "From: {$from}",
        "Content-Type: text/plain; charset=UTF-8",
        "Content-Transfer-Encoding: 8bit"
    ];
    $headerStr = implode("\r\n", $headers);

    // 日本語メール設定
    if (function_exists('mb_send_mail')) {
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");

        // ✅ 件名はそのまま渡す（mb_send_mailが自動エンコード）
        mb_send_mail(ADMIN_EMAIL, $subject, $message, $headerStr);
    } else {
        // Fallback（非推奨環境用）
        mail(ADMIN_EMAIL, $subject, $message, $headerStr);
    }
}
