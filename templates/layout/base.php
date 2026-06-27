<?php
/** @var string $content */
/** @var ?\App\Entity\Utilisateur $utilisateurCourant */
/** @var array<string, string[]> $flash */
/** @var string $csrf */
/** @var string $appName */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e($csrf ?? '') ?>">
  <script>window.BASE_PATH = '<?= defined('BASE_PATH') ? BASE_PATH : '' ?>';</script>
  <title><?= e($pageTitle ?? $appName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?php if (!empty($useLeaflet)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <?php endif; ?>
  <link rel="stylesheet" href="<?= asset('css/styles.css') ?>?v=<?= filemtime(PUBLIC_PATH . '/assets/css/styles.css') ?>">
</head>
<body>

<div id="page-loader"></div>

<?php include TEMPLATE_PATH . '/partials/header.php'; ?>

<?php if (!empty($flash)): ?>
  <div class="flash-zone">
    <?php foreach ($flash as $type => $messages): ?>
      <?php foreach ($messages as $msg): ?>
        <div class="flash flash-<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<main class="container">
  <?= $content ?? '' ?>
</main>

<!-- Carrousel de photos -->
<section class="carousel-section">
  <div class="carousel-track-wrapper">
    <div class="carousel-track" id="carouselTrack">
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo1.jpeg') ?>" alt="Photo 1"></div>
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo2.jpeg') ?>" alt="Photo 2"></div>
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo3.jpeg') ?>" alt="Photo 3"></div>
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo4.jpeg') ?>" alt="Photo 4"></div>
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo5.jpeg') ?>" alt="Photo 5"></div>
      <!-- Duplicatas pour boucle infinie -->
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo1.jpg') ?>" alt="Photo 1"></div>
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo2.jpg') ?>" alt="Photo 2"></div>
      <div class="carousel-slide"><img src="<?= asset('img/carousel/photo3.jpg') ?>" alt="Photo 3"></div>
    </div>
  </div>
  <button class="carousel-btn prev" id="carouselPrev" aria-label="Précédent">
    <svg viewBox="0 0 24 24" width="20" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <button class="carousel-btn next" id="carouselNext" aria-label="Suivant">
    <svg viewBox="0 0 24 24" width="20" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  <div class="carousel-dots" id="carouselDots">
    <span class="carousel-dot active" data-index="0"></span>
    <span class="carousel-dot" data-index="1"></span>
    <span class="carousel-dot" data-index="2"></span>
    <span class="carousel-dot" data-index="3"></span>
    <span class="carousel-dot" data-index="4"></span>
  </div>
</section>

<footer class="footer">
  <div class="footer-grid">

    <div class="footer-col footer-col--brand">
      <div class="footer-brand">
        <span class="logo-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 7v10l8 5 8-5V7l-8-5z"/><circle cx="12" cy="12" r="3"/></svg>
        </span>
        <strong>CityAlert</strong>
      </div>
      <p class="footer-tagline">Signalez les problèmes de votre ville, suivez leur résolution en temps réel.</p>
      <p class="footer-copy">© <?= date('Y') ?> L2IAGE-A — Projet pédagogique POO PHP</p>
    </div>

    <div class="footer-col">
      <h4 class="footer-col-title">Catégories</h4>
      <ul class="footer-links">
        <?php foreach ($footerCategories ?? [] as $cat): ?>
          <li><a href="<?= url('/?categorie=' . $cat->getId()) ?>"><?= e($cat->getNom()) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="footer-col">
      <h4 class="footer-col-title">Navigation</h4>
      <ul class="footer-links">
        <li><a href="<?= url('/') ?>">Accueil</a></li>
        <?php if ($utilisateurCourant === null): ?>
          <li><a href="<?= url('/login') ?>">Se connecter</a></li>
          <li><a href="<?= url('/register') ?>">S'inscrire</a></li>
        <?php else: ?>
          <li><a href="<?= url('/profil') ?>">Mon profil</a></li>
          <li><a href="<?= url('/signalements/nouveau') ?>">Nouveau signalement</a></li>
        <?php endif; ?>
      </ul>
    </div>

  </div>
</footer>

<!-- Lightbox global -->
<div class="lightbox" id="lightbox">
  <button class="lightbox-close" id="lightboxClose">
    <svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
  <div class="lightbox-nav prev" id="lightboxPrev">
    <svg viewBox="0 0 24 24" width="24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
  </div>
  <img id="lightboxImg" src="" alt="">
  <div class="lightbox-nav next" id="lightboxNext">
    <svg viewBox="0 0 24 24" width="24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
  </div>
</div>

<?php if (!empty($useLeaflet)): ?>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
<script>
(function () {
  const bar = document.getElementById('page-loader');
  let timer;

  function start() {
    bar.style.transition = 'none';
    bar.style.opacity    = '1';
    bar.style.width      = '0%';
    bar.offsetWidth; // force reflow
    bar.style.transition = 'width 0.3s ease, opacity 0.4s ease';
    bar.style.width      = '70%';
    clearTimeout(timer);
  }

  function finish() {
    bar.style.width = '100%';
    timer = setTimeout(() => { bar.style.opacity = '0'; }, 300);
  }

  // Démarre au clic sur un lien interne
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript') || a.target === '_blank') return;
    start();
  });

  // Démarre à la soumission d'un formulaire (sauf les forms AJAX like/soutenir)
  document.addEventListener('submit', e => {
    if (e.target.classList.contains('like')) return;
    start();
  });

  // Termine quand la page est chargée (retour arrière, etc.)
  window.addEventListener('pageshow', finish);
})();
</script>
<?php if (!empty($inlineScript)): ?><script><?= $inlineScript ?></script><?php endif; ?>
<script>
(function () {
  const track  = document.getElementById('carouselTrack');
  const dots   = document.querySelectorAll('.carousel-dot');
  if (!track) return;

  const total  = 5;
  let current  = 0;
  let autoTimer;

  function goTo(index, animate) {
    if (animate !== false) track.style.transition = 'transform 0.5s ease';
    else track.style.transition = 'none';
    track.style.transform = `translateX(-${index * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === index % total));
    current = index;
  }

  function next() {
    const nextIndex = current + 1;
    goTo(nextIndex);
    if (nextIndex >= total) {
      setTimeout(() => goTo(0, false), 510);
    }
  }

  function resetTimer() {
    clearInterval(autoTimer);
    autoTimer = setInterval(next, 3500);
  }

  document.getElementById('carouselNext')?.addEventListener('click', () => { next(); resetTimer(); });
  document.getElementById('carouselPrev')?.addEventListener('click', () => {
    const prevIndex = current <= 0 ? total - 1 : current - 1;
    goTo(prevIndex);
    resetTimer();
  });
  dots.forEach(dot => {
    dot.addEventListener('click', () => { goTo(+dot.dataset.index); resetTimer(); });
  });

  resetTimer();
})();
</script>
</body>
</html>
