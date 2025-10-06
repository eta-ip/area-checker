// ===============================
//  アニメーション（inview）
// ===============================
$('.up').on('inview', function() { $(this).addClass('upstyle'); });
$('.down').on('inview', function() { $(this).addClass('downstyle'); });
$('.transform1').on('inview', function() { $(this).addClass('transform1style'); });
$('.transform2').on('inview', function() { $(this).addClass('transform2style'); });
$('.transform3').on('inview', function() { $(this).addClass('transform3style'); });
$('.blur').on('inview', function() { $(this).addClass('blurstyle'); });

// ===============================
//  メイン画像スライドショー（Vegas）
// ===============================
$(function () {
  $('#mainimg').vegas({
    slides: [
      { src: './img/1.PNG' },
      { src: './img/2.PNG' },
      { src: './img/3.PNG' },
      { src: './img/4.PNG' },
      { src: './img/5.PNG' },
      { src: './img/5.PNG' },
      { src: './img/5.PNG' },
      { src: './img/5.PNG' }
    ],
    transition: 'fade',
    delay: 3000,
    cover: false,
    valign: 'top',
    align: 'center'
  });
});

// ===============================
//  汎用ユーティリティ
// ===============================
const debounce = (func, wait) => {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
};

// ===============================
//  レイアウト調整
// ===============================
const handleResponsive = () => {
  const isMobile = window.innerWidth < 1370;
  $('body').toggleClass('s', isMobile).toggleClass('p', !isMobile);
  $('#menubar').toggleClass('d-n', isMobile).toggleClass('d-b', !isMobile);
  $('#menubar_hdr').toggleClass('d-n', !isMobile).toggleClass('d-b', isMobile)
    .removeClass('open').text('≡');
  $('#overlay').removeClass('active');
};

// ===============================
//  ページ内リンクスクロール
// ===============================
const scrollToHash = () => {
  const hash = location.hash;
  if (hash) {
    $('body,html').scrollTop(0);
    setTimeout(() => {
      const target = $(hash);
      if (target.length) $('body,html').animate({ scrollTop: target.offset().top }, 500);
    }, 100);
  }
};

// ===============================
//  サムネイル初期化
// ===============================
const initThumbnail = () => {
  $(".thumbnail-view").each(function () {
    const firstSrc = $(this).next(".thumbnail").find("img:first").attr("src");
    if (firstSrc) $(this).append($("<img>").attr("src", firstSrc));
  });
  $(".thumbnail img").click(function () {
    const src = $(this).attr("src"),
          newImg = $("<img>").attr("src", src).hide(),
          viewer = $(this).closest(".thumbnail").prev(".thumbnail-view");
    viewer.find("img").fadeOut(400, function () {
      viewer.empty().append(newImg);
      newImg.fadeIn(400);
    });
  });
};

// ===============================
//  メニュー開閉
// ===============================
const closeMenu = () => {
  $('#menubar').removeClass('d-b').addClass('d-n');
  $('#menubar_hdr').removeClass('open').text('≡').attr('aria-expanded', 'false');
  $('#overlay').removeClass('active');
};
const openMenu = () => {
  $('#menubar').removeClass('d-n').addClass('d-b');
  $('#menubar_hdr').addClass('open').text('×').attr('aria-expanded', 'true');
  $('#overlay').addClass('active');
};
const toggleMenu = () => {
  $('#menubar_hdr').hasClass('open') ? closeMenu() : openMenu();
};

// ===============================
//  同意チェック → ボタン有効化
// ===============================
function updateAgreeButtonState() {
  const agree = document.getElementById('agree');
  const submit = document.getElementById('submit-btn');
  if (agree && submit) {
    submit.disabled = !agree.checked;
  }
}
function initAgreeButton() {
  const agree = document.getElementById('agree');
  if (agree) {
    agree.addEventListener('change', updateAgreeButtonState);
  }
  updateAgreeButtonState();
}

// ===============================
//  初期化処理
// ===============================
$(function () {
  handleResponsive();
  scrollToHash();
  initThumbnail();

  $('#menubar_hdr').on('click', toggleMenu).on('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleMenu();
    }
  });
  $('#overlay').on('click', closeMenu);
  $('#menubar a[href^="#"]').click(() => { closeMenu(); });
  $('#menubar a[href=""]').on('click', e => e.preventDefault());

  $('#menubar li:has(ul)').addClass('ddmenu_parent');
  $('.ddmenu_parent > a').addClass('ddmenu');
  $('.ddmenu').on('touchstart', function () {
    $(this).next('ul').stop().slideToggle();
    $('.ddmenu').not(this).next('ul').slideUp();
    return false;
  });
  $('.ddmenu_parent').hover(
    function () { $(this).children('ul').stop().slideDown(); },
    function () { $(this).children('ul').stop().slideUp(); }
  );
  $('.ddmenu_parent ul a').click(() => $('.ddmenu_parent ul').slideUp());

  const scrollBtn = $('.pagetop'), showClass = 'pagetop-show';
  scrollBtn.hide();
  $(window).on('scroll', () => {
    $(window).scrollTop() >= 300 ? scrollBtn.fadeIn().addClass(showClass) : scrollBtn.fadeOut().removeClass(showClass);
  });
  $('a[href^="#"]').click(function () {
    const href = $(this).attr('href'),
          target = href === '#' ? 0 : $(href).offset()?.top || 0;
    $('body,html').stop().animate({ scrollTop: target }, 500);
    return false;
  });

  const $flow = $('.flow');
  $flow.find('dd').hide();
  $flow.on('click keyup', '.openclose', function (e) {
    if (e.type === 'keyup' && !['Enter', ' ', 'Spacebar'].includes(e.key)) return;
    e.preventDefault();
    const $dt = $(this),
          $dd = $dt.nextUntil('dt'),
          $icon = $dt.find('.toggle-icon'),
          isOpen = $dd.is(':visible');
    if ($dd.is(':animated')) return;
    $flow.find('dd').slideUp();
    $flow.find('.openclose').attr('aria-expanded', 'false').find('.toggle-icon').text('＋');
    if (!isOpen) {
      $dd.slideDown();
      $dt.attr('aria-expanded', 'true');
      $icon.text('－');
    }
  });

  initAgreeButton(); // ✅ 同意チェックの初期化
});

$(window).on("load resize", debounce(() => { handleResponsive(); scrollToHash(); }, 100));
$(window).on('hashchange', scrollToHash);
window.addEventListener('pageshow', initAgreeButton); // ✅ 戻るボタン対応

// ===============================
//  クッキー
// ===============================
  // JSTの日付（yyyy-mm-dd）を取得する関数
  function getJSTDateString() {
    const now = new Date();
    const jst = new Date(now.getTime() + 9 * 60 * 60 * 1000); // UTC→JST (+9h)
    return jst.toISOString().slice(0, 10); // yyyy-mm-dd
  }

  document.addEventListener("DOMContentLoaded", function() {
    const banner = document.getElementById("cookie-banner");
    const closeBtn = document.getElementById("closeBanner");

    const closedDate = localStorage.getItem("cookieBannerClosedDate");
    const todayJST = getJSTDateString();

    if (closedDate === todayJST) {
      // 今日(JST)すでに閉じている → 非表示
      banner.style.display = "none";
      document.body.style.paddingBottom = "0";
    }

    closeBtn.addEventListener("click", function() {
      const todayJST = getJSTDateString();
      banner.style.display = "none";
      document.body.style.paddingBottom = "0";
      localStorage.setItem("cookieBannerClosedDate", todayJST);
    });
  });