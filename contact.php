<?php
session_start();
$form = $_SESSION['form'] ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-X2D783MNRC"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-X2D783MNRC');
  </script>
  <meta charset="UTF-8">
  <title>お問い合わせ | エイタ国際企画</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="青梅市の一級建築士事務所・エイタ国際企画では建築設計や都市計画に関するご相談を承っております。お問い合わせはこちらから。">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Noto+Sans:wght@400;700&display=swap" rel="stylesheet">

</head>

<body>
<header>
  <p id="logo"><a href="index.html"><img src="img/logo.png" alt="一級建築士事務所 エイタ国際企画のロゴ"></a></p>
  <div id="menubar_hdr" role="button" aria-label="メニュー切替" tabindex="0"><span></span><span></span><span></span></div>
  <div id="menubar" class="d-n">
    <nav>
      <ul>
        <li><a href="projects.html" class="btn00"><span class="btn00__text">Projects</span><span class="btn00__text">施工実績</span></a></li>
        <li><a href="corporate.html" class="btn00"><span class="btn00__text">Profile</span><span class="btn00__text">企業情報</span></a></li>
        <li><a href="flow.html" class="btn00"><span class="btn00__text">Workflow</span><span class="btn00__text">設計の流れ</span></a></li>
        <li><a href="contact.php" class="btn00"><span class="btn00__text">Contact Us</span><span class="btn00__text">お問い合わせ</span></a></li>
      </ul>
    </nav>
  </div>
</header>

<div id="container">
  <div id="contents">
    <main>
      <section>
        <h1 id="section-title-contact">お問い合わせ</h1>
        <div role="alert" style="text-align: center; color: #b22222; font-weight: bold; margin-bottom: 1.5em;">
          ※本フォームは業務に関するお問い合わせ専用です。<br>営業・セールス目的の送信はご遠慮ください。
        </div>

        <form action="confirm.php" method="post" enctype="multipart/form-data" class="contact-form">

          <div class="form-group">
            <label for="name">お名前（必須）</label>
            <input type="text" name="name" id="name" required class="form-control"
                   value="<?= htmlspecialchars($form['name'] ?? '', ENT_QUOTES) ?>">
          </div>

          <div class="form-group">
            <label for="email">メールアドレス（必須）</label>
            <input type="email" name="email" id="email" required class="form-control"
                   value="<?= htmlspecialchars($form['email'] ?? '', ENT_QUOTES) ?>">
          </div>

          <div class="form-group">
            <label for="message">お問い合わせ内容（必須）</label>
            <textarea name="message" id="message" rows="5" required class="form-control"><?= htmlspecialchars($form['message'] ?? '', ENT_QUOTES) ?></textarea>
          </div>

          <div class="form-group">
            <label for="attachment">添付ファイル（PDF、JPEG形式のみ、最大3MBまで）</label>
            <input type="file" name="attachment" id="attachment" accept=".pdf,.jpeg,.jpg" class="form-control">
            <?php if (!empty($_SESSION['uploaded_file_name'])): ?>
              <p style="margin-top: .5em; font-size: .9em; color: #555;">
                前回選択されたファイル: <?= htmlspecialchars($_SESSION['uploaded_file_name'], ENT_QUOTES) ?>
              </p>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="agree">
              <input type="checkbox" id="agree" required>
              <a href="privacy.html" target="_blank">個人情報保護方針</a>に同意する
            </label>
            <p class="note">※個人情報保護方針にご同意いただいた後、『入力内容の確認』ボタンが押せるようになります。</p>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn" id="submit-btn" disabled>入力内容の確認</button>
          </div>
        </form>
      </section>
    </main>
  </div>
</div>

<footer>
<small>&copy; 2024-2025 <a href="index.html">ETA INTERNATIONAL PLANNING LLC.</a></small><br>
</footer></div>

<div id="cookie-banner" class="cookie-banner">
当サイトでは、クッキー（Cookie）を使用しています。
このウェブサイトを引き続き使用することにより、
お客様はクッキーの使用に同意するものとします。
<a href="cookie-policy.html">Learn more</a>
<span class="close-banner" id="closeBanner">×</span></div>


<div style="text-align: center; margin-top: 2em;">
  <a href="/secure-upload/frontend/index.html" class="btn"
     style="display: inline-block; padding: 0.8em 1.5em; background-color: #4a6572; color: #fff; 
            text-decoration: none; border-radius: 5px; font-size: 0.9em;">
    📁 大容量ファイル送信用ページはこちら
  </a>
</div>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vegas/2.5.4/vegas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/protonet-jquery.inview/1.1.2/jquery.inview.min.js"></script>
<script src="js/main.js"></script>

<div id="overlay"></div>
</body>
</html>
