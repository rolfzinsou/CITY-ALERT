<?php
/** @var \App\Entity\Agent $agent */
/** @var \App\Entity\Categorie $categorie */
/** @var \App\Entity\Signalement[] $signalements */
/** @var \App\Enum\Statut[] $statuts */
?>
<div class="main-head" style="margin-top:24px;">
  <div>
    <h1>Mes signalements <span class="count"><?= e($categorie->getNom()) ?></span></h1>
    <p style="color:var(--text-muted);margin-top:4px;">Service : <?= e($agent->getService()) ?></p>
  </div>
</div>

<div class="filters" data-exclusive="true">
  <a href="<?= url('/agent/signalements') ?>" class="chip <?= empty($_GET['statut']) ? 'active' : '' ?>">Tous</a>
  <a href="<?= url('/agent/signalements?statut=nouveau') ?>"  class="chip">Nouveaux</a>
  <a href="<?= url('/agent/signalements?statut=en_cours') ?>" class="chip">En cours</a>
  <a href="<?= url('/agent/signalements?statut=resolu') ?>"   class="chip">Résolus</a>
</div>

<?php if (empty($signalements)): ?>
  <div class="empty-state">
    <h3>Aucun signalement à traiter.</h3>
    <p>Tout est à jour dans votre catégorie 👌</p>
  </div>
<?php endif; ?>

<div class="feed">
  <?php foreach ($signalements as $s): ?>
    <article class="card">
      <div class="card-head">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($s->getAuteur()->getNom(), 0, 2))) ?></span>
        <div class="meta">
          <div class="author-name"><?= e($s->getAuteur()->getNom()) ?></div>
          <div class="author-role"><?= e($s->getAdresse()) ?></div>
        </div>
        <span class="time-ago"><?= e($s->getCreatedAt()->format('d/m/Y H:i')) ?></span>
      </div>
      <div class="card-body">
        <a href="<?= url('/signalements/' . ($s->getId())) ?>" style="color:inherit;text-decoration:none">
          <h3 class="card-title"><?= e($s->getTitre()) ?></h3>
        </a>
        <p class="card-desc"><?= e(mb_substr($s->getDescription(), 0, 200)) ?>…</p>
      </div>
      <div class="card-tags">
        <span class="badge status-<?= str_replace('_', '-', $s->getStatut()->value) ?>">
          <span class="badge-dot"></span><?= e($s->getStatut()->libelle()) ?>
        </span>
      </div>

      <?php if (!$s->getStatut()->estFinal()): ?>
        <form action="<?= url('/agent/signalements/' . ($s->getId()) . '/statut') ?>" method="post"
              style="padding:16px 20px;border-top:1px solid var(--border);display:grid;gap:8px;">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <div style="display:flex;gap:8px;">
            <select name="nouveau_statut" class="select" required style="flex:1">
              <option value="">Changer le statut...</option>
              <?php foreach ($s->getStatut()->transitionsAutorisees() as $cible): ?>
                <option value="<?= $cible->value ?>"><?= e($cible->libelle()) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Appliquer</button>
          </div>
          <textarea name="remarque" class="textarea" placeholder="Remarque pour le citoyen (facultatif)..." style="min-height:60px;"></textarea>
        </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
