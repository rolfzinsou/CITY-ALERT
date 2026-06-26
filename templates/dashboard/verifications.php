<?php
/** @var array  $citoyens
 *  @var string $csrf
 */
?>
<div class="main-head" style="margin-top:24px;">
  <h1>Vérification des comptes citoyens</h1>
  <a href="<?= url('/dashboard') ?>" class="btn btn-ghost">← Dashboard</a>
</div>

<p style="color:var(--text-muted);margin-bottom:24px;">
  Liste des citoyens ayant fourni leurs pièces d'identité et en attente de validation.
</p>

<?php if (empty($citoyens)): ?>
  <div class="empty-state">
    <h3>Aucun citoyen en attente.</h3>
  </div>
<?php endif; ?>

<div class="feed">
  <?php foreach ($citoyens as $c): ?>
    <div class="card" style="padding:20px;">
      <div style="display:flex;gap:16px;align-items:center;margin-bottom:16px;">
        <span class="avatar lg"><?= e(mb_strtoupper(mb_substr($c['nom'], 0, 2))) ?></span>
        <div style="flex:1">
          <div style="font-weight:600;"><?= e($c['nom']) ?></div>
          <div style="font-size:.875rem;color:var(--text-muted);"><?= e($c['email']) ?> · <?= e($c['telephone']) ?></div>
          <div style="font-size:.8rem;color:var(--text-subtle);margin-top:4px;">
            Inscrit le <?= e(date('d/m/Y', strtotime($c['date_inscription']))) ?>
          </div>
        </div>
        <form action="<?= url('/admin/citoyens/' . ($c['id']) . '/verifier') ?>" method="post">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <button class="btn btn-primary">Valider</button>
        </form>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <div class="hint" style="margin-bottom:6px;">Recto</div>
          <img src="<?= e($c['piece_identite_recto']) ?>" alt="" style="width:100%;border-radius:8px;border:1px solid var(--border);">
        </div>
        <div>
          <div class="hint" style="margin-bottom:6px;">Verso</div>
          <?php if (!empty($c['piece_identite_verso'])): ?>
            <img src="<?= e($c['piece_identite_verso']) ?>" alt="" style="width:100%;border-radius:8px;border:1px solid var(--border);">
          <?php else: ?>
            <div style="padding:32px;background:var(--bg-elevated);border-radius:8px;text-align:center;color:var(--text-muted);">Non fourni</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
