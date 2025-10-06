<?php
session_start();

// reCAPTCHA チェック
if (!isset($_POST['g-recaptcha-response'])) {
    echo "reCAPTCHA の認証に失敗しました（POSTなし）。戻って再試行してください。";
    exit;
}

$recaptchaSecret = '6Le9USsrAAAAAEZI-R1EHlib7DYtsS6NZAwmPoo4';
$recaptchaResponse = $_POST['g-recaptcha-response'];

// reCAPTCHA: curl を使って送信
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => $recaptchaSecret,
    'response' => $recaptchaResponse
]));
$verifyResponse = curl_exec($ch);
curl_close($ch);
$responseData = json_decode($verifyResponse);

if (!$responseData->success) {
    echo "reCAPTCHA の認証に失敗しました。戻って再試行してください。";
    exit;
}

// フォームデータ
$name = htmlspecialchars($_POST['name'], ENT_QUOTES);
$email = htmlspecialchars($_POST['email'], ENT_QUOTES);
$message = htmlspecialchars($_POST['message'], ENT_QUOTES);

// 添付ファイル情報
$attachmentPath = '';
$attachmentName = '';
$mimeType = '';
$fileBinary = '';
$fileContent = '';
if (isset($_SESSION['uploaded_file_path']) && isset($_SESSION['uploaded_file_name'])) {
    $attachmentPath = $_SESSION['uploaded_file_path'];
    $attachmentName = basename($_SESSION['uploaded_file_name']);
    $attachmentName = mb_convert_encoding($attachmentName, 'UTF-8', 'auto');
    $attachmentName = preg_replace('/[^\w\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}0-9a-zA-Z._-]/u', '_', $attachmentName);

    $mimeType = mime_content_type($attachmentPath);

    // 許可されたファイル形式かチェック（PDFとJPEGのみ）
    $allowedTypes = ['application/pdf', 'image/jpeg'];
    if (!in_array($mimeType, $allowedTypes)) {
        echo "許可されていないファイル形式です。PDFまたはJPEGのみ対応しています。";
        exit;
    }

    // 一時ファイルを読み込む
    $fileBinary = file_get_contents($attachmentPath);
    $fileContent = chunk_split(base64_encode($fileBinary));
}

// 管理者宛メール
$to = "info@eta-ip.com";
$subject = "【Web問い合わせ】{$name}様より";
$plainBody = "以下の内容でお問い合わせがありました。\n\n";
$plainBody .= "お名前: $name\nメールアドレス: $email\n内容:\n$message\n";
if ($attachmentName) {
    $plainBody .= "添付ファイル名: {$attachmentName}\n";
}

if ($attachmentPath && file_exists($attachmentPath)) {
    $boundary = md5(uniqid(rand(), true));
    $headers = "From: info@eta-ip.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n\r\n";
    $body .= $plainBody . "\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: {$mimeType}; name=\"{$attachmentName}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
    $body .= $fileContent . "\r\n";
    $body .= "--{$boundary}--";

    mail($to, $subject, $body, $headers);
} else {
    $headers = "From: info@eta-ip.com\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
    mail($to, $subject, $plainBody, $headers);
}

// 送信者への自動返信メール（添付ファイルは送らない）
$confirmSubject = "【自動返信】お問い合わせありがとうございます。";

// 自動送信注意文（先頭へ移動）
$confirmBodyText = "------------------------------------------------------------------------
■このメールは、自動送信メールです。
　このメールに返信された場合、返信内容の確認およびご返答ができかねます。
　あらかじめご了承ください。
■このメールは、当社のホームページからお問い合わせをいただいた方に
　お送りしています。 お心当たりのない方は、ご面倒をおかけしますが
　メールを削除してくださいますようお願いいたします。
------------------------------------------------------------------------

";

$confirmBodyText .= "{$name} 様\n\nお問い合わせいただきありがとうございます。\n以下の内容で受け付けました。\n\n";
$confirmBodyText .= "お名前: $name\nメールアドレス: $email\n内容:\n$message\n";
if ($attachmentName) {
    $confirmBodyText .= "添付ファイル名: {$attachmentName}\n";
}
$confirmBodyText .= "

━━━━━━━━━━━━━━━━━━━━━━━━━━
合同会社エイタ国際企画

Office   〒198-0046
東京都青梅市日向和田2-928
Web     eta-ip.com";

// テキストメールとして送信
$headers = "From: info@eta-ip.com\r\nContent-Type: text/plain; charset=UTF-8";
mail($email, $confirmSubject, $confirmBodyText, $headers);

// セッション＆一時ファイル削除
if ($attachmentPath && file_exists($attachmentPath)) {
    unlink($attachmentPath);
}
session_destroy();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>送信完了</title>
    <meta http-equiv="refresh" content="3;URL=index.html">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <h2>お問い合わせありがとうございました。</h2>
  <p>ご入力いただいたメールアドレス宛に確認メールを送信しました。</p>
  <p>3秒後にトップページへ移動します。</p>
</body>
</html>
