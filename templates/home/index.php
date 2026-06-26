<?php
/** @var \App\Entity\Signalement[] $signalements */
/** @var \App\Entity\Categorie[]   $categories */
/** @var array{total:int,resolus:int,en_cours:int,citoyens:int} $stats */
/** @var array $filtres */
/** @var int[] $soutiensUser */
$statutActif = $filtres['statut']->value ?? null;
?>

<!-- HERO -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-content">
    <h1 class="hero-title">CityAlert :<br><span class="hero-accent">le site pour régler vos problèmes dans vos villes</span></h1>
    <p class="hero-sub">Signalez en quelques secondes les soucis de votre quartier — voirie, éclairage, déchets, eau —
       et suivez en direct leur résolution par les agents municipaux.</p>
    <div class="hero-actions">
      <?php if ($utilisateurCourant !== null): ?>
        <a href="<?= url('/signalements/nouveau') ?>" class="btn btn-primary btn-lg">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Signaler un problème
        </a>
      <?php else: ?>
        <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg">Rejoindre la communauté</a>
        <a href="<?= url('/login') ?>"    class="btn btn-ghost btn-lg">Se connecter</a>
      <?php endif; ?>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><div class="n" data-target="<?= $stats['total'] ?>">0</div><div class="l">Signalements créés</div></div>
      <div class="hero-stat"><div class="n" data-target="<?= $stats['resolus'] ?>">0</div><div class="l">Résolus</div></div>
      <div class="hero-stat"><div class="n" data-target="<?= $stats['en_cours'] ?>">0</div><div class="l">En cours</div></div>
      <div class="hero-stat"><div class="n" data-target="<?= $stats['citoyens'] ?>">0</div><div class="l">Citoyens inscrits</div></div>
    </div>
  </div>
</section>

<div class="layout">
  <!-- SIDEBAR (desktop) -->
  <aside class="sidebar">
    <div class="side-title">Navigation</div>
    <ul class="side-nav">
      <li><a href="<?= url('/') ?>" class="side-link active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9 12 2l9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Fil d'actualité <span class="badge"><?= $stats['total'] ?></span>
      </a></li>
      <?php if ($utilisateurCourant !== null): ?>
        <li><a href="<?= url('/signalements/nouveau') ?>" class="side-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          Nouveau signalement
        </a></li>
        <li><a href="<?= url('/profil') ?>" class="side-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Mon profil
        </a></li>
      <?php endif; ?>
    </ul>

    <div class="side-title">Catégories</div>
    <ul class="side-nav">
      <?php
        $catIcons = ['voirie' => 'voirie', 'eclairage' => 'eclairage', 'dechets' => 'dechets', 'eau_assainissement' => 'eau'];
        foreach ($categories as $cat):
          $icon = $catIcons[$cat->getTypeClass()->value] ?? 'voirie';
      ?>
        <li><a href="<?= url('/?categorie=' . ($cat->getId())) ?>" class="side-link">
          <span class="cat-icon <?= $icon ?>"></span> <?= e($cat->getNom()) ?>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </aside>

  <!-- CONTENU -->
  <main>
    <div class="main-head">
      <h1>Fil des signalements <span class="count"><?= count($signalements) ?> affichés</span></h1>
      <?php if ($utilisateurCourant !== null): ?>
        <a href="<?= url('/signalements/nouveau') ?>" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Signaler
        </a>
      <?php endif; ?>
    </div>

    <!-- FILTRES -->
    <div class="filters" data-exclusive="true">
      <a href="<?= url('/') ?>"                 class="chip <?= $statutActif === null ? 'active' : '' ?>">Tous</a>
      <a href="<?= url('/?statut=nouveau') ?>"  class="chip <?= $statutActif === 'nouveau' ? 'active' : '' ?>">Nouveaux</a>
      <a href="<?= url('/?statut=en_cours') ?>" class="chip <?= $statutActif === 'en_cours' ? 'active' : '' ?>">En cours</a>
      <a href="<?= url('/?statut=resolu') ?>"   class="chip <?= $statutActif === 'resolu' ? 'active' : '' ?>">Résolus</a>
      <a href="<?= url('/?statut=rejete') ?>"   class="chip <?= $statutActif === 'rejete' ? 'active' : '' ?>">Rejetés</a>
      <span style="margin-left:auto"></span>
      <a href="<?= url('/?periode=auj') ?>"     class="chip">Aujourd'hui</a>
      <a href="<?= url('/?periode=semaine') ?>" class="chip">7 derniers jours</a>
    </div>

    <!-- FEED -->
    <section class="feed">
      <?php if (empty($signalements)): ?>
        <div class="empty-state">
          <h3>Aucun signalement pour l'instant.</h3>
          <p>Soyez le premier à contribuer.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($signalements as $s):
        $photos = $s->getPhotos();
        $statutClass = str_replace('_', '-', $s->getStatut()->value);
      ?>
        <article class="card" data-href="<?= url('/signalements/' . ($s->getId())) ?>" style="cursor:pointer">
          <div class="card-head">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr($s->getAuteur()->getNom(), 0, 2))) ?></span>
            <div class="meta">
              <div class="author-name"><?= e($s->getAuteur()->getNom()) ?></div>
              <div class="author-role"><?= e($s->getAuteur()->getRole()->libelle()) ?></div>
            </div>
            <span class="time-ago"><?= e($s->getCreatedAt()->format('d/m/Y H:i')) ?></span>
          </div>
          <div class="card-body">
            <a href="<?= url('/signalements/' . ($s->getId())) ?>" style="color:inherit;text-decoration:none">
              <h3 class="card-title"><?= e($s->getTitre()) ?></h3>
            </a>
            <div class="card-address">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= e($s->getAdresse()) ?>
            </div>
            <p class="card-desc"><?= e(mb_substr($s->getDescription(), 0, 220)) ?><?= mb_strlen($s->getDescription()) > 220 ? '…' : '' ?></p>
          </div>
          <?php if (!empty($photos)): ?>
            <a href="<?= url('/signalements/' . ($s->getId())) ?>">
              <img class="card-photo" src="<?= e(uploadUrl($photos[0]->getChemin())) ?>" alt="">
            </a>
          <?php endif; ?>
          <div class="card-tags">
            <span class="badge status-<?= $statutClass ?>"><span class="badge-dot"></span><?= e($s->getStatut()->libelle()) ?></span>
            <span class="badge cat"><?= e($s->getCategorie()->getNom()) ?></span>
            <span class="priority">Priorité <?= e($s->getCategorie()->getPriorite()->libelle()) ?></span>
          </div>
          <div class="card-actions">
            <?php if ($utilisateurCourant !== null): ?>
              <form action="<?= url('/signalements/' . ($s->getId()) . '/soutenir') ?>" method="post" class="card-action like <?= in_array((int)$s->getId(), $soutiensUser, true) ? 'liked' : '' ?>" style="border:none;border-right:1px solid var(--border)">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button type="submit" style="display:flex;align-items:center;gap:8px;width:100%;justify-content:center;background:none;border:none;color:inherit;cursor:pointer">
                  <svg viewBox="0 0 24 24" fill="currentColor" width="16"><path d="M2 21h4V9H2v12zm20-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L13.17 1 7.59 6.59C7.22 6.95 7 7.45 7 8v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>
                  Soutenir · <?= $s->getNombreSoutiens() ?>
                </button>
              </form>
            <?php else: ?>
              <a class="card-action" href="<?= url('/login') ?>">
                <svg viewBox="0 0 24 24" fill="currentColor" width="16"><path d="M2 21h4V9H2v12zm20-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L13.17 1 7.59 6.59C7.22 6.95 7 7.45 7 8v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>
                Soutenir · <?= $s->getNombreSoutiens() ?>
              </a>
            <?php endif; ?>
            <a class="card-action" href="<?= url('/signalements/' . ($s->getId())) ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              Commenter
            </a>
            <button class="card-action share-btn" data-url="<?= e(url('/signalements/' . $s->getId())) ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              Partager
            </button>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  </main>
</div>

<script>
document.querySelectorAll('.share-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const url = location.origin + btn.dataset.url;
    await navigator.clipboard.writeText(url);
    const label = btn.lastChild;
    label.textContent = ' Lien copié !';
    setTimeout(() => { label.textContent = ' Partager'; }, 2000);
  });
});

document.querySelectorAll('.card[data-href]').forEach(card => {
  card.addEventListener('click', e => {
    if (e.target.closest('a, button, form')) return;
    window.location.href = card.dataset.href;
  });
});

document.querySelectorAll('form.like').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = form.querySelector('button');
    const data = new FormData(form);
    const res = await fetch(form.action, {
      method: 'POST',
      body: data,
      headers: {'X-Requested-With': 'XMLHttpRequest'},
    });
    if (!res.ok) return;
    const json = await res.json();
    btn.innerHTML = btn.innerHTML.replace(/·\s*\d+/, `· ${json.total}`);
    form.classList.toggle('liked', json.action === 'ajoute');
  });
});
</script>
