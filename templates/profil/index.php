<?php
/** @var \App\Entity\Utilisateur $user */
/** @var \App\Entity\Signalement[] $mesSignalements */
/** @var array{total:int,resolus:int,en_cours:int} $statsProfil */
$estCitoyen = $user instanceof \App\Entity\Citoyen;
?>
<div style="margin-top:24px;">

  <!-- En-tête profil -->
  <div class="profile-header">
    <span class="avatar xl"><?= e(mb_strtoupper(mb_substr($user->getNom(), 0, 2))) ?></span>
    <div class="info">
      <h1><?= e($user->getNom()) ?>
        <?php if ($estCitoyen && $user->estVerifie()): ?>
          <span class="verified-badge">
            <svg viewBox="0 0 24 24" width="12" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            Vérifié
          </span>
        <?php endif; ?>
      </h1>
      <div style="color:var(--text-muted);font-size:.9rem;"><?= e($user->getRole()->libelle()) ?></div>
      <div class="profile-stats">
        <div class="profile-stat"><div class="n"><?= $statsProfil['total'] ?></div><div class="l">Signalements</div></div>
        <div class="profile-stat"><div class="n"><?= $statsProfil['resolus'] ?></div><div class="l">Résolus</div></div>
        <div class="profile-stat"><div class="n"><?= $statsProfil['en_cours'] ?></div><div class="l">En cours</div></div>
      </div>
    </div>
  </div>

  <div class="detail-grid">
    <!-- Mes signalements -->
    <div class="detail-main">
      <h2 style="margin-bottom:16px;">Mes signalements</h2>
      <?php if (empty($mesSignalements)): ?>
        <div class="empty-state">
          <h3>Vous n'avez pas encore de signalement.</h3>
          <p><a href="<?= url('/signalements/nouveau') ?>" style="color:var(--primary)">Créez votre premier signalement</a></p>
        </div>
      <?php endif; ?>
      <div class="feed">
        <?php foreach ($mesSignalements as $s): ?>
          <article class="card">
            <div class="card-body" style="padding-top:16px;">
              <div class="card-tags" style="border:none;padding:0 0 12px;">
                <span class="badge status-<?= str_replace('_', '-', $s->getStatut()->value) ?>">
                  <span class="badge-dot"></span><?= e($s->getStatut()->libelle()) ?>
                </span>
                <span class="badge cat"><?= e($s->getCategorie()->getNom()) ?></span>
              </div>
              <a href="<?= url('/signalements/' . ($s->getId())) ?>" style="color:inherit;text-decoration:none">
                <h3 class="card-title"><?= e($s->getTitre()) ?></h3>
              </a>
              <p class="card-desc" style="margin:8px 0;"><?= e(mb_substr($s->getDescription(), 0, 150)) ?>…</p>
              <div style="display:flex;gap:12px;align-items:center;font-size:.85rem;color:var(--text-muted);">
                <span><?= e($s->getCreatedAt()->format('d/m/Y')) ?></span>
                <span>· <?= $s->getNombreSoutiens() ?> soutiens</span>
                <?php if ($s->peutEtreModifiePar($user)): ?>
                  <a href="<?= url('/signalements/' . ($s->getId()) . '/editer') ?>" style="color:var(--primary);margin-left:auto;">Modifier</a>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Paramètres -->
    <aside>
      <div class="side-panel">
        <div class="panel-title">Mes informations</div>
        <form action="<?= url('/profil') ?>" method="post">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <div class="field">
            <label class="label">Nom</label>
            <input type="text" name="nom" class="input" value="<?= e($user->getNom()) ?>" required>
          </div>
          <div class="field">
            <label class="label">Email</label>
            <input type="email" class="input" value="<?= e($user->getEmail()) ?>" disabled style="opacity:.6">
            <div class="hint">L'email ne peut pas être modifié</div>
          </div>
          <div class="field">
            <label class="label">Téléphone</label>
            <input type="tel" name="telephone" class="input" value="<?= e($user->getTelephone()) ?>" required>
          </div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <input type="checkbox" name="notifications" id="notif" value="1" <?= $user->accepteNotifications() ? 'checked' : '' ?> style="accent-color:var(--primary);width:16px;height:16px;">
            <label for="notif" style="font-size:.875rem;cursor:pointer;">Recevoir les notifications email</label>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Enregistrer</button>
        </form>
      </div>

      <div class="side-panel">
        <div class="panel-title">Changer le mot de passe</div>
        <form action="<?= url('/profil/mot-de-passe') ?>" method="post">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <div class="field">
            <label class="label">Ancien mot de passe</label>
            <input type="password" name="ancien_mot_de_passe" class="input" required>
          </div>
          <div class="field">
            <label class="label">Nouveau mot de passe</label>
            <input type="password" name="nouveau_mot_de_passe" class="input" minlength="8" required>
          </div>
          <div class="field">
            <label class="label">Confirmer</label>
            <input type="password" name="confirmation" class="input" required>
          </div>
          <button type="submit" class="btn btn-ghost btn-block">Modifier le mot de passe</button>
        </form>
      </div>
    </aside>
  </div>
</div>
