<?php 
date_default_timezone_set('Asia/Tokyo');

// 🔐 鍵の読み込み
$keyPath = __DIR__ . '/secret.key';
$hmacPath = __DIR__ . '/hmac.secret.key';
$counterFile = __DIR__ . '/downloads.json';

if (!file_exists($keyPath) || !file_exists($hmacPath)) {
    http_response_code(500);
    exit('内部エラー：鍵ファイルが見つかりません。');
}

$key = hex2bin(trim(file_get_contents($keyPath)));
$hmacSecret = trim(file_get_contents($hmacPath));

// 🔢 ダウンロード回数の読み込み
$downloadCounts = file_exists($counterFile) ? json_decode(file_get_contents($counterFile), true) : [];

function saveDownloadCounts($counts, $path) {
    file_put_contents($path, json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $fileParam = $_GET['f'] ?? '';
    $displayName = $_GET['n'] ?? 'downloaded_file';
    $expire = (int) ($_GET['t'] ?? 0);
    $signature = $_GET['s'] ?? '';

    if (empty($fileParam) || empty($signature) || $expire === 0) {
        http_response_code(400);
        exit('無効なリクエストです。');
    }

    $escapedFile = htmlspecialchars($fileParam, ENT_QUOTES, 'UTF-8');
    $escapedName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');

    // 🔢 現在のダウンロード回数を確認
    $filename = basename($fileParam);
    $counterKey = $filename . '|' . $signature;
    $downloadCount = $downloadCounts[$counterKey] ?? 0;
    $remaining = max(0, 2 - $downloadCount);

    // 📄 UI改善＆残り回数表示付き
    echo <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ダウンロード確認</title>
  <style>
    body {
      font-family: 'Segoe UI', 'Helvetica Neue', sans-serif;
      background-color: #f9f9f9;
      color: #333;
      padding: 30px;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background-color: #fff;
      border-radius: 12px;
      padding: 25px 30px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #1a73e8;
    }

    ul {
      list-style: none;
      padding: 0;
      margin-bottom: 20px;
    }

    li {
      padding: 8px 0;
      border-bottom: 1px solid #eee;
    }

    .terms {
      border: 1px solid #ccc;
      background-color: #f7f7f7;
      padding: 15px;
      border-radius: 8px;
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 20px;
    }

    label {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      font-weight: bold;
    }

    input[type="checkbox"] {
      margin-right: 10px;
      transform: scale(1.2);
    }

    button {
      background-color: #1a73e8;
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
      width: 100%;
    }

    button:hover {
      background-color: #155fc1;
    }

    .file-info {
      font-weight: bold;
    }

    .count-info {
      margin-bottom: 20px;
      font-size: 14px;
      color: #666;
    }
        #logo img {
      height: 40px;
    }
  </style>
</head>
<body>
    <header>
    <div id="logo">
      <a href="https://eta-ip.com/index.html">
        <img src="https://eta-ip.com/img/logo.png" alt="一級建築士事務所 エイタ国際企画">
      </a>
    </div>
  </header>
  <div class="container">
    <h2>ファイルダウンロード確認</h2>
    <p>以下のファイルをダウンロードしますか？</p>
    <ul>
      <li><span class="file-info">ファイル名:</span> {$escapedName}</li>
    </ul>

    <div class="count-info">このファイルはあと <strong>{$remaining}</strong> 回ダウンロードできます。</div>

    <form method="POST">
      <input type="hidden" name="f" value="{$escapedFile}">
      <input type="hidden" name="n" value="{$escapedName}">
      <input type="hidden" name="t" value="{$expire}">
      <input type="hidden" name="s" value="{$signature}">

      <h3>■ 利用規約</h3>
      <div class="terms">
        <p>1. 本ファイルは、合同会社エイタ国際企画が提供する資料です。</p>
        <p>2. 許可された目的以外での利用は禁止します。</p>
        <p>3. 本ファイルの再配布、改変、転載を禁止します。</p>
        <p>4. 利用中に発生した損害について、当社は責任を負いません。</p>
        <p>5. 同意がない場合、ダウンロードはできません。</p>
      </div>

      <label>
        <input type="checkbox" name="agree" value="1" required>
        利用規約に同意する
      </label>

      <button type="submit">このファイルをダウンロード</button>
    </form>
  </div>
</body>
</html>
HTML;
    exit;
}

// ✅ POST → 実際のダウンロード処理
$fileParam = $_POST['f'] ?? '';
$displayName = $_POST['n'] ?? 'downloaded_file';
$expire = (int) ($_POST['t'] ?? 0);
$signature = $_POST['s'] ?? '';
$agreed = $_POST['agree'] ?? '';

if (!$agreed) {
    http_response_code(403);
    exit('利用規約に同意していません。');
}

if (empty($fileParam) || empty($signature) || $expire === 0) {
    http_response_code(400);
    exit('無効なリクエストです。');
}

$filename = basename($fileParam);
$storageDir = realpath(__DIR__ . '/../storage');
$storagePath = realpath($storageDir . DIRECTORY_SEPARATOR . $filename);

if (!$storagePath || strpos($storagePath, $storageDir) !== 0 || !file_exists($storagePath)) {
    http_response_code(404);
    exit('ファイルが存在しません。');
}

$expectedSignature = hash_hmac('sha256', "$fileParam|$expire", $hmacSecret);
if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    exit('無効な署名です。');
}

if (time() > $expire) {
    http_response_code(403);
    exit('リンクの有効期限が切れています。');
}

$counterKey = $filename . '|' . $signature;
$downloadCount = $downloadCounts[$counterKey] ?? 0;

if ($downloadCount >= 2) {
    http_response_code(429);
    exit('ダウンロード回数の上限に達しました。');
}

$rawData = file_get_contents($storagePath);
$ivLength = openssl_cipher_iv_length('aes-256-cbc');
$iv = substr($rawData, 0, $ivLength);
$encrypted = substr($rawData, $ivLength);

$decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
if ($decrypted === false) {
    http_response_code(500);
    exit('復号に失敗しました。');
}

// 📧 通知
$adminEmail = 'info@eta-ip.com';
$subject = '【ETA】ファイルがダウンロードされました';
$timestamp = date('Y-m-d H:i:s');
$userIP = $_SERVER['REMOTE_ADDR'] ?? '不明';

$message = <<<EOT
ファイル名（表示用）: $displayName
保存ファイル名: $filename
ダウンロード時刻: $timestamp
IPアドレス: $userIP
EOT;

$headers = implode("\r\n", [
    "From: no-reply@eta-ip.com",
    "Content-Type: text/plain; charset=UTF-8",
]);

mail($adminEmail, $subject, $message, $headers);

// 🔢 カウント記録
$downloadCounts[$counterKey] = $downloadCount + 1;
saveDownloadCounts($downloadCounts, $counterFile);

// 📤 出力
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($displayName) . '"; filename*=UTF-8\'\'' . rawurlencode($displayName));
header('Content-Length: ' . strlen($decrypted));
header('X-Content-Type-Options: nosniff');

echo $decrypted;
