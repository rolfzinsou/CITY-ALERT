<?php
/** @var array<string,string> $erreurs */
/** @var array $old */
$erreurs = $erreurs ?? [];
$old     = $old     ?? [];
?>
<form action="<?= url('/register') ?>" method="post" enctype="multipart/form-data" class="form-grid" style="margin-top:32px;">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

  <h2 style="margin-bottom:8px;">Créer un compte citoyen</h2>
  <p style="color:var(--text-muted);margin-bottom:24px;">Vous pourrez signaler des problèmes et suivre leur traitement.</p>

  <div class="field">
    <label class="label">Nom complet <span class="req">*</span></label>
    <input type="text" name="nom" class="input" value="<?= e($old['nom'] ?? '') ?>" required>
    <?php if (!empty($erreurs['nom'])): ?><div class="hint" style="color:#EF4444"><?= e($erreurs['nom']) ?></div><?php endif; ?>
  </div>

  <div class="field">
    <label class="label">Email <span class="req">*</span></label>
    <input type="email" name="email" class="input" value="<?= e($old['email'] ?? '') ?>" required>
    <?php if (!empty($erreurs['email'])): ?><div class="hint" style="color:#EF4444"><?= e($erreurs['email']) ?></div><?php endif; ?>
  </div>

  <div class="field">
    <label class="label">Téléphone <span class="req">*</span></label>
    <input type="tel" name="telephone" class="input" placeholder="+221XXXXXXXXX"
           value="<?= e($old['telephone'] ?? '+221') ?>" required>
    <div class="hint">Format : +221 suivi de 9 chiffres</div>
    <?php if (!empty($erreurs['telephone'])): ?><div class="hint" style="color:#EF4444"><?= e($erreurs['telephone']) ?></div><?php endif; ?>
  </div>

  <div class="field">
    <label class="label">Mot de passe <span class="req">*</span></label>
    <input type="password" name="mot_de_passe" class="input" minlength="8" required>
    <div class="hint">Minimum 8 caractères</div>
  </div>

  <div class="field">
    <label class="label">Confirmer le mot de passe <span class="req">*</span></label>
    <input type="password" name="mot_de_passe_confirmation" class="input" required>
  </div>

  <div class="field">
    <label class="label">Pièce d'identité (recto) <span style="color:var(--text-subtle);font-weight:400">— facultatif</span></label>
    <input type="file" name="piece_recto" accept="image/*" class="input">
    <div class="hint">Sera vérifié par un administrateur pour valider votre compte.</div>
  </div>

  <div class="field">
    <label class="label">Pièce d'identité (verso) <span style="color:var(--text-subtle);font-weight:400">— facultatif</span></label>
    <input type="file" name="piece_verso" accept="image/*" class="input">
  </div>

  <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px;">
    <a href="<?= url('/login') ?>" class="btn btn-ghost">Déjà inscrit ?</a>
    <button type="submit" class="btn btn-primary btn-lg">Créer mon compte</button>
  </div>
</form>
