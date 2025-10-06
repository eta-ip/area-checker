<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $_SESSION['form'] = $_POST;
} elseif (!isset($_SESSION['form'])) {
    header("Location: contact.php");
    exit;
}

$form = $_SESSION['form'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>お問い合わせ内容の確認</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- reCAPTCHA v2 Invisible 読み込み（右下バッジ表示） -->
    <script src="https://www.google.com/recaptcha/api.js?hl=ja" async defer></script>
    <style>
        .grecaptcha-badge {
            visibility: visible !important;
            opacity: 1 !important;
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body>
<header></header>

<div id="container" style="max-width: 800px; margin: 0 auto; padding: 1em;">
<div id="contents">
<main><section>
    <h1>お問い合わせ内容の確認</h1>

    <p><strong>お名前:</strong> <?= htmlspecialchars($form['name'], ENT_QUOTES); ?></p>
    <p><strong>メールアドレス:</strong> <?= htmlspecialchars($form['email'], ENT_QUOTES); ?></p>
    <p><strong>内容:</strong><br><?= nl2br(htmlspecialchars($form['message'], ENT_QUOTES)); ?></p>

    <?php if (!empty($_FILES['attachment']['name'])): ?>
        <p><strong>添付ファイル:</strong> <?= htmlspecialchars($_FILES['attachment']['name'], ENT_QUOTES); ?></p>
    <?php endif; ?>

    <!-- ボタンエリア -->
    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 1em; margin-top: 1em;">

        <!-- 送信フォーム -->
        <form action="send.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="name" value="<?= htmlspecialchars($form['name'], ENT_QUOTES); ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($form['email'], ENT_QUOTES); ?>">
            <input type="hidden" name="message" value="<?= htmlspecialchars($form['message'], ENT_QUOTES); ?>">

            <?php
            if (isset($_FILES['attachment']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
                $tmp_path = tempnam(sys_get_temp_dir(), 'upload_');
                move_uploaded_file($_FILES['attachment']['tmp_name'], $tmp_path);
                $_SESSION['uploaded_file_path'] = $tmp_path;
                $_SESSION['uploaded_file_name'] = $_FILES['attachment']['name'];
                echo '<input type="hidden" name="has_attachment" value="1">';
            }
            ?>

            <!-- reCAPTCHA ウィジェット -->
            <div class="g-recaptcha"
                data-sitekey="6Le9USsrAAAAAOI_pKHXQmu4YCpQ7e-o9rPvfqqU"
                data-callback="onRecaptchaSuccess"
                data-expired-callback="onRecaptchaExpired">
            </div>
            <br>

            <button type="submit" id="submitBtn"
                    style="padding: 10px 20px; font-size: 16px; cursor: not-allowed;"
                    disabled>送信する</button>
        </form>

        <!-- 戻るボタン -->
        <form action="contact.php" method="post">
            <button type="submit"
                    style="padding: 10px 20px; font-size: 16px; cursor: pointer;">戻る</button>
        </form>
    </div>

</section></main>
</div>

<footer>
    <small>&copy; <a href="index.html">2024</a> ETA LLC.</small>
</footer>
</div>

<!-- reCAPTCHA 成功/失効時に送信ボタンを制御 -->
<script>
function onRecaptchaSuccess() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = false;
    btn.style.cursor = 'pointer';
}
function onRecaptchaExpired() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.style.cursor = 'not-allowed';
}
</script>

</body>
</html>
