<?php
/** @var \App\Entity\Signalement $signalement */
/** @var \App\Entity\HistoriqueStatut[] $historique */
/** @var bool $aSoutenu */

function rendreCommentaire(\App\Entity\Commentaire $c, string $csrf, int $profondeur = 0): void {
?>
<div id="commentaire-<?= $c->getId() ?>" class="comment" style="<?= $profondeur > 0 ? 'margin-left:48px;border-left:2px solid var(--border);padding-left:16px;' : '' ?>">
  <span class="avatar"><?= e(mb_strtoupper(mb_substr($c->getAuteur()->getNom(), 0, 2))) ?></span>
  <div class="comment-body">
    <div class="comment-head">
      <span class="comment-name"><?= e($c->getAuteur()->getNom()) ?></span>
      <?php if ($c->getAuteur()->getRole()->value === 'agent'): ?>
        <span class="comment-badge">Agent</span>
      <?php elseif ($c->getAuteur()->getRole()->value === 'administrateur'): ?>
        <span class="comment-badge">Admin</span>
      <?php endif; ?>
      <span class="comment-time"><?= e($c->getCreatedAt()->format('d/m/Y H:i')) ?></span>
    </div>
    <p class="comment-text"><?= nl2br(e($c->getContenu())) ?></p>
    <div style="display:flex;gap:12px;margin-top:8px;font-size:.85rem;">
      <button class="like-comment" data-id="<?= $c->getId() ?>" style="background:none;border:none;color:var(--text-muted);cursor:pointer;">
        ♥ <?= $c->getNombreLikes() ?>
      </button>
      <button class="reply-toggle" data-id="<?= $c->getId() ?>" style="background:none;border:none;color:var(--text-muted);cursor:pointer;">
        Répondre
      </button>
    </div>
    <form class="reply-form" data-parent="<?= $c->getId() ?>" action="<?= url('/signalements/' . ($c->getSignalement()->getId()) . '/commentaires') ?>" method="post" style="display:none;margin-top:8px;">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="parent_id" value="<?= $c->getId() ?>">
      <textarea name="contenu" class="comment-input" placeholder="Votre réponse..." style="min-height:60px;"></textarea>
      <button type="submit" class="btn btn-primary" style="margin-top:8px;">Publier</button>
    </form>
    <?php foreach ($c->getReponses() as $r): rendreCommentaire($r, $csrf, $profondeur + 1); endforeach; ?>
  </div>
</div>
<?php } ?>

<nav class="breadcrumb">
  <a href="<?= url('/') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="M3 9 12 2l9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></a>
  <span class="sep">/</span>
  <a href="<?= url('/') ?>">Fil</a>
  <span class="sep">/</span>
  <span><?= e(mb_substr($signalement->getTitre(), 0, 60)) ?></span>
</nav>

<div class="detail-grid">
  <div class="detail-main">
    <div class="detail-hero">
      <?php if (!empty($signalement->getPhotos())): ?>
        <img class="detail-photo" data-lightbox src="<?= e(uploadUrl($signalement->getPhotos()[0]->getChemin())) ?>" alt="">
      <?php endif; ?>
      <div class="detail-info">
        <div class="card-tags" style="border-top:none;padding:0 0 16px;">
          <span class="badge status-<?= str_replace('_', '-', $signalement->getStatut()->value) ?>">
            <span class="badge-dot"></span><?= e($signalement->getStatut()->libelle()) ?>
          </span>
          <span class="badge cat"><?= e($signalement->getCategorie()->getNom()) ?></span>
          <span class="priority">Priorité <?= e($signalement->getCategorie()->getPriorite()->libelle()) ?></span>
        </div>

        <h1 class="detail-title"><?= e($signalement->getTitre()) ?></h1>

        <div class="detail-meta">
          <span class="detail-meta-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e($signalement->getAdresse()) ?>
          </span>
          <span class="detail-meta-item"><?= e($signalement->getCreatedAt()->format('d/m/Y H:i')) ?></span>
          <span class="detail-meta-item"><?= $signalement->getNombreSoutiens() ?> soutiens</span>
        </div>

        <p class="detail-description"><?= nl2br(e($signalement->getDescription())) ?></p>

        <?php if (count($signalement->getPhotos()) > 1): ?>
          <div class="photo-gallery">
            <?php foreach (array_slice($signalement->getPhotos(), 1) as $photo): ?>
              <img data-lightbox src="<?= e(uploadUrl($photo->getChemin())) ?>" alt="">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($utilisateurCourant !== null): ?>
          <div style="display:flex;gap:8px;margin-top:24px;">
            <form action="<?= url('/signalements/' . ($signalement->getId()) . '/soutenir') ?>" method="post" style="display:inline" class="form-soutenir">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <button class="btn <?= $aSoutenu ? 'btn-primary' : 'btn-ghost' ?>">
                ♥ <?= $aSoutenu ? 'Soutenu' : 'Soutenir' ?> · <?= $signalement->getNombreSoutiens() ?>
              </button>
            </form>
            <?php if ($signalement->peutEtreModifiePar($utilisateurCourant)): ?>
              <a href="<?= url('/signalements/' . ($signalement->getId()) . '/editer') ?>" class="btn btn-ghost">Modifier</a>
              <form action="<?= url('/signalements/' . ($signalement->getId()) . '/supprimer') ?>" method="post" style="display:inline"
                    onsubmit="return confirm('Supprimer définitivement ce signalement ?')">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button class="btn btn-danger">Supprimer</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Commentaires -->
    <div class="comments-section">
      <h3 class="section-title">
        Discussion <span class="count"><?= count($signalement->getCommentaires()) ?> commentaires</span>
      </h3>

      <?php foreach ($signalement->getCommentaires() as $c): rendreCommentaire($c, $csrf); endforeach; ?>

      <?php if ($utilisateurCourant !== null): ?>
        <form action="<?= url('/signalements/' . ($signalement->getId()) . '/commentaires') ?>" method="post" class="comment-form">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <span class="avatar"><?= e(mb_strtoupper(mb_substr($utilisateurCourant->getNom(), 0, 2))) ?></span>
          <div style="flex:1">
            <textarea name="contenu" class="comment-input" placeholder="Ajouter un commentaire..." required></textarea>
            <div style="display:flex;justify-content:flex-end;margin-top:8px;">
              <button type="submit" class="btn btn-primary">Publier</button>
            </div>
          </div>
        </form>
      <?php else: ?>
        <p style="text-align:center;color:var(--text-muted);padding:20px;">
          <a href="<?= url('/login') ?>">Connectez-vous</a> pour commenter.
        </p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Side panel -->
  <aside>
    <div class="side-panel">
      <div class="panel-title">Signalé par</div>
      <div class="author-card">
        <span class="avatar lg"><?= e(mb_strtoupper(mb_substr($signalement->getAuteur()->getNom(), 0, 2))) ?></span>
        <div>
          <div class="name"><?= e($signalement->getAuteur()->getNom()) ?></div>
          <div class="role"><?= e($signalement->getAuteur()->getRole()->libelle()) ?></div>
        </div>
      </div>
    </div>

    <div class="side-panel">
      <div class="panel-title">Historique</div>
      <div class="timeline">
        <?php foreach (array_reverse($historique) as $h): ?>
          <div class="timeline-item">
            <div class="timeline-event"><?= e($h->getNouveau()->libelle()) ?></div>
            <div class="timeline-time"><?= e($h->getCreatedAt()->format('d/m/Y H:i')) ?></div>
            <?php if ($h->getRemarque()): ?>
              <div class="timeline-note"><?= e($h->getRemarque()) ?> — <?= e($h->getAgent()->getNom()) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <div class="timeline-item muted">
          <div class="timeline-event">Créé</div>
          <div class="timeline-time"><?= e($signalement->getCreatedAt()->format('d/m/Y H:i')) ?></div>
        </div>
      </div>
    </div>

    <div class="side-panel">
      <div class="panel-title">Localisation</div>
      <div style="font-size:.875rem;color:var(--text);margin-bottom:12px;display:flex;align-items:flex-start;gap:6px;">
        <svg viewBox="0 0 24 24" width="16" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink:0;margin-top:2px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <span><?= e($signalement->getAdresse()) ?></span>
      </div>
      <?php if ($signalement->getLatitude() && $signalement->getLongitude()): ?>
        <div id="map" class="leaflet-map"
             data-lat="<?= e((string)$signalement->getLatitude()) ?>"
             data-lng="<?= e((string)$signalement->getLongitude()) ?>"
             data-titre="<?= e($signalement->getTitre()) ?>"></div>
        <a href="https://www.google.com/maps?q=<?= e((string)$signalement->getLatitude()) ?>,<?= e((string)$signalement->getLongitude()) ?>"
           target="_blank" rel="noopener noreferrer"
           class="btn btn-ghost" style="display:flex;align-items:center;gap:6px;margin-top:10px;width:100%;justify-content:center;font-size:.875rem;">
          <svg viewBox="0 0 24 24" width="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Ouvrir dans Google Maps
        </a>
      <?php endif; ?>
    </div>
  </aside>
</div>

<script>
// Toggle réponse
document.querySelectorAll('.reply-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const form = document.querySelector(`.reply-form[data-parent="${btn.dataset.id}"]`);
    if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
  });
});

// Like commentaire
document.querySelectorAll('.like-comment').forEach(btn => {
  btn.addEventListener('click', async () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const res = await fetch(`${window.BASE_PATH}/commentaires/${btn.dataset.id}/like`, {
      method: 'POST',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf},
    });
    if (res.ok) {
      const data = await res.json();
      btn.textContent = `♥ ${data.total}`;
    }
  });
});

// Soutenir
const soutenirForm = document.querySelector('.form-soutenir');
if (soutenirForm) {
  soutenirForm.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = soutenirForm.querySelector('button');
    const res = await fetch(soutenirForm.action, {
      method: 'POST',
      body: new FormData(soutenirForm),
      headers: {'X-Requested-With': 'XMLHttpRequest'},
    });
    if (!res.ok) return;
    const json = await res.json();
    const liked = json.action === 'ajoute';
    btn.className = 'btn ' + (liked ? 'btn-primary' : 'btn-ghost');
    btn.innerHTML = `♥ ${liked ? 'Soutenu' : 'Soutenir'} · ${json.total}`;
  });
}

// Carte Leaflet
const mapEl = document.getElementById('map');
if (mapEl && typeof L !== 'undefined') {
  const lat = parseFloat(mapEl.dataset.lat);
  const lng = parseFloat(mapEl.dataset.lng);
  const map = L.map('map').setView([lat, lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19,
  }).addTo(map);
  L.marker([lat, lng]).addTo(map).bindPopup(mapEl.dataset.titre).openPopup();
}
</script>
