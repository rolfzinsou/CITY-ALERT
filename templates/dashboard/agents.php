<?php
/** @var array $agents */
/** @var \App\Entity\Categorie[] $categories */
/** @var array $erreurs */
/** @var array $old */
/** @var string $csrf */
$erreurs = $erreurs ?? [];
$old     = $old     ?? [];
?>
<div class="main-head" style="margin-top:24px;">
  <h1>Gestion des agents</h1>
  <a href="<?= url('/dashboard') ?>" class="btn btn-ghost">← Dashboard</a>
</div>

<div class="chart-row">
  <div class="chart-card">
    <div class="chart-head"><div class="chart-title">Agents en service</div></div>
    <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
      <thead>
        <tr style="text-align:left;color:var(--text-muted);font-size:.8rem;text-transform:uppercase;">
          <th style="padding:12px 8px;">Nom</th>
          <th style="padding:12px 8px;">Catégorie</th>
          <th style="padding:12px 8px;">Téléphone</th>
          <th style="padding:12px 8px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($agents as $a): ?>
          <tr style="border-top:1px solid var(--border);">
            <td style="padding:14px 8px;">
              <div style="font-weight:500;"><?= e($a['nom']) ?></div>
              <div style="font-size:.8rem;color:var(--text-muted);"><?= e($a['email']) ?></div>
            </td>
            <td style="padding:14px 8px;"><span class="badge cat"><?= e($a['cat_nom'] ?? '—') ?></span></td>
            <td style="padding:14px 8px;color:var(--text-muted);"><?= e($a['telephone']) ?></td>
            <td style="padding:14px 8px;text-align:right;white-space:nowrap;">
              <a href="<?= url('/admin/agents/' . $a['id'] . '/editer') ?>"
                 class="btn btn-ghost" style="padding:6px 12px;font-size:.8rem;">Modifier</a>
              <form action="<?= url('/admin/agents/' . $a['id'] . '/supprimer') ?>" method="post"
                    style="display:inline"
                    onsubmit="return confirm('Supprimer l\'agent <?= e($a['nom']) ?> ?')">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button class="btn btn-danger" style="padding:6px 12px;font-size:.8rem;">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <form action="<?= url('/admin/agents') ?>" method="post" class="chart-card">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <h3 style="margin-bottom:16px;">Créer un agent</h3>

    <div class="field">
      <label class="label">Nom complet</label>
      <input type="text" name="nom" class="input" required>
    </div>
    <div class="field">
      <label class="label">Email</label>
      <input type="email" name="email" class="input" required>
    </div>
    <div class="field">
      <label class="label">Téléphone</label>
      <input type="tel" name="telephone" class="input" placeholder="+221XXXXXXXXX" required>
    </div>
    <div class="field">
      <label class="label">Mot de passe initial</label>
      <input type="password" name="mot_de_passe" class="input" minlength="8" required>
    </div>
    <div class="field">
      <label class="label">Service</label>
      <input type="text" name="service" class="input" placeholder="Ex : Voirie" required>
    </div>
    <div class="field">
      <label class="label">Catégorie affectée</label>
      <select name="categorie_id" class="select" required>
        <option value="">Choisir...</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat->getId() ?>"><?= e($cat->getNom()) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Créer l'agent</button>
  </form>
</div>
