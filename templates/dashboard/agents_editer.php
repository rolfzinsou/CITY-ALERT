<?php
/** @var \App\Entity\Agent $agent */
/** @var \App\Entity\Categorie[] $categories */
/** @var array $erreurs */
/** @var array $old */
/** @var string $csrf */
$erreurs = $erreurs ?? [];
$old     = $old     ?? [];
?>
<div class="main-head" style="margin-top:24px;">
  <h1>Modifier l'agent</h1>
  <a href="<?= url('/admin/agents') ?>" class="btn btn-ghost">← Retour</a>
</div>

<?php if (!empty($erreurs)): ?>
  <div class="flash flash-error" style="position:static;margin-bottom:20px;max-width:none;">
    <?php foreach ($erreurs as $champ => $msg): ?>
      <div><?= e($msg) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form action="<?= url('/admin/agents/' . $agent->getId() . '/editer') ?>" method="post"
      class="form-grid" style="max-width:600px;margin:0;">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

  <div class="field">
    <label class="label">Nom complet</label>
    <input type="text" name="nom" class="input"
           value="<?= old('nom', $old, $agent->getNom()) ?>" required>
  </div>

  <div class="field">
    <label class="label">Email</label>
    <input type="email" name="email" class="input"
           value="<?= old('email', $old, $agent->getEmail()) ?>" required>
  </div>

  <div class="field">
    <label class="label">Téléphone</label>
    <input type="tel" name="telephone" class="input" placeholder="+221XXXXXXXXX"
           value="<?= old('telephone', $old, $agent->getTelephone()) ?>" required>
  </div>

  <div class="field">
    <label class="label">Service</label>
    <input type="text" name="service" class="input"
           value="<?= old('service', $old, $agent->getService()) ?>" required>
  </div>

  <div class="field">
    <label class="label">Catégorie affectée</label>
    <select name="categorie_id" class="select" required>
      <option value="">Choisir...</option>
      <?php foreach ($categories as $cat): ?>
        <?php $selected = ($old['categorie_id'] ?? $agent->getCategorie()?->getId()) == $cat->getId() ? 'selected' : '' ?>
        <option value="<?= $cat->getId() ?>" <?= $selected ?>><?= e($cat->getNom()) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="field">
    <label class="label">Nouveau mot de passe <span style="color:var(--text-muted);font-weight:400;">(laisser vide pour ne pas changer)</span></label>
    <input type="password" name="mot_de_passe" class="input" minlength="8" autocomplete="new-password">
  </div>

  <button type="submit" class="btn btn-primary btn-block">Enregistrer les modifications</button>
</form>
