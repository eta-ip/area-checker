<?php
date_default_timezone_set('Asia/Tokyo');

require_once __DIR__ . '/config.php';

// 鍵の読み込み
$key = hex2bin(trim(file_get_contents(__DIR__ . '/secret.key')));
$hmacSecret = trim(file_get_contents(__DIR__ . '/hmac.secret.key'));

// パス設定
$uploadDir   = UPLOAD_DIR;
$historyFile = __DIR__ . '/history.json';
$dbFile      = DB_FILE;
$maxSize     = MAX_FILE_SIZE;

// 禁止拡張子
$forbiddenExtensions = ['php', 'exe', 'bat', 'sh'];

// ファイル確認
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ファイルが送信されていません。']);
    exit;
}

$file = $_FILES['file'];

// アップロードエラー
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'アップロードエラー: ' . $file['error']]);
    exit;
}

// サイズチェック
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ファイルサイズが大きすぎます（最大256MB）']);
    exit;
}

// ファイル名と拡張子の検証
$originalName = basename($file['name']);
$safeName = preg_replace('/[^a-zA-Z0-9._-]/u', '_', $originalName);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (in_array($ext, $forbiddenExtensions, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "このファイル形式（.$ext）はアップロードできません。"]);
    exit;
}

// ファイル暗号化
$tmpPath = $file['tmp_name'];
$data = file_get_contents($tmpPath);
$ivLength = openssl_cipher_iv_length('aes-256-cbc');
$iv = random_bytes($ivLength);
$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

// 保存処理
$saveName = uniqid('', true) . '.enc';
$fullPath = $uploadDir . $saveName;

if (file_put_contents($fullPath, $iv . $encrypted, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'ファイルの保存に失敗しました。']);
    exit;
}

// 有効期限と署名付きリンク
$expire = time() + EXPIRE_SECONDS;
$tokenData = "$saveName|$expire";
$token = hash_hmac('sha256', $tokenData, $hmacSecret);
$link = "https://eta-ip.com/secure-upload/backend/download.php?f=$saveName&n=" . urlencode($originalName) . "&t=$expire&s=$token";

// コメント取得
$comment = trim($_POST['comment'] ?? '（コメントなし）');

// ============================
// ✅ history.json に記録
// ============================
$entry = [
    'name'   => $originalName,
    'link'   => $link,
    'expire' => $expire,
];
$history = [];
if (file_exists($historyFile)) {
    $json = file_get_contents($historyFile);
    $history = json_decode($json, true) ?? [];
}
$history[] = $entry;
file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

// ============================
// ✅ files.json（= DB_FILE）に記録（cleanup.php 用）
// ============================
$tokenKey = bin2hex(random_bytes(8));
$dbEntry = [
    'original_name' => $originalName,
    'stored_name'   => $saveName,
    'expires_at'    => $expire
];
$db = [];
if (file_exists($dbFile)) {
    $db = json_decode(file_get_contents($dbFile), true) ?? [];
}
$db[$tokenKey] = $dbEntry;
file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

// ============================
// ✅ 通知メール送信（オプション）
// ============================
if (ENABLE_UPLOAD_NOTIFICATION) {
    try {
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");

        $formattedExpire = date('Y-m-d H:i:s', $expire);
        $subject = '【ETA】ファイルがアップロードされました'; // 生の件名
        $body = <<<EOT
以下のファイルがアップロードされました。

ファイル名: {$originalName}
ダウンロードリンク: {$link}
有効期限:   {$formattedExpire}

送信者コメント:
{$comment}
EOT;

        sendNotification($subject, $body);
    } catch (Throwable $e) {
        error_log("通知メールの送信に失敗: " . $e->getMessage());
    }
}

// ✅ 応答（JSON）
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'url'    => $link
]);
