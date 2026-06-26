<?php
/** @var \App\Entity\Categorie[] $categories */
/** @var array<string,string> $erreurs */
/** @var array $old */
$erreurs = $erreurs ?? [];
$old     = $old     ?? [];
?>
<div style="text-align:center;max-width:720px;margin:24px auto 32px;">
  <h1 style="font-size:2rem;margin-bottom:8px;">Signaler un problème dans votre ville</h1>
  <p style="color:var(--text-muted);">Aidez votre commune à améliorer le cadre de vie.</p>
</div>

<form action="<?= url('/signalements') ?>" method="post" enctype="multipart/form-data" class="form-grid">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

  <div class="field">
    <label class="label">Catégorie <span class="req">*</span></label>
    <div class="cat-picker">
      <?php foreach ($categories as $cat): ?>
        <label class="cat-option <?= (string)($old['categorie_id'] ?? '') === (string)$cat->getId() ? 'selected' : '' ?>">
          <input type="radio" name="categorie_id" value="<?= $cat->getId() ?>" style="display:none"
                 <?= (string)($old['categorie_id'] ?? '') === (string)$cat->getId() ? 'checked' : '' ?>>
          <div class="cat-option-icon <?= e($cat->getTypeClass()->value === 'eau_assainissement' ? 'eau' : $cat->getTypeClass()->value) ?>"></div>
          <span class="cat-option-name"><?= e($cat->getNom()) ?></span>
          <span class="hint"><?= $cat->getDelaiTraitementJours() ?> jours</span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="field">
    <label class="label">Titre <span class="req">*</span></label>
    <input type="text" name="titre" class="input" placeholder="Ex : Nid-de-poule dangereux sur la VDN"
           value="<?= e($old['titre'] ?? '') ?>" required>
    <?php if (!empty($erreurs['titre'])): ?><div class="hint" style="color:#EF4444"><?= e($erreurs['titre']) ?></div><?php endif; ?>
  </div>

  <div class="field">
    <label class="label">Description détaillée <span class="req">*</span></label>
    <textarea name="description" class="textarea" required placeholder="Nature du problème, ampleur, dangerosité..."><?= e($old['description'] ?? '') ?></textarea>
  </div>

  <div class="field">
    <label class="label">Adresse <span class="req">*</span></label>
    <input type="text" name="adresse" class="input" placeholder="Quartier, rue, point de repère..."
           value="<?= e($old['adresse'] ?? '') ?>" required>
  </div>

  <input type="hidden" name="latitude"  id="lat">
  <input type="hidden" name="longitude" id="lng">

  <div class="field">
    <button type="button" id="geoloc" class="btn btn-ghost" style="width:100%;justify-content:center;">
      <svg viewBox="0 0 24 24" width="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Utiliser ma position actuelle
    </button>
    <div class="hint" id="geolocStatus"></div>
  </div>

  <div class="field">
    <label class="label">Photos (facultatif)</label>
    <input type="file" name="photos[]" id="photosInput" multiple accept="image/jpeg,image/png,image/webp" class="input">
    <div class="hint">Plusieurs photos possibles (JPG, PNG ou WEBP — 5 Mo max par photo)</div>
    <div class="upload-preview" id="uploadPreview"></div>
  </div>

  <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;padding-top:24px;border-top:1px solid var(--border);">
    <a href="<?= url('/') ?>" class="btn btn-ghost">Annuler</a>
    <button type="submit" class="btn btn-primary btn-lg">Publier le signalement</button>
  </div>
</form>

<script>
// Sélecteur de catégorie
document.querySelectorAll('.cat-picker .cat-option').forEach(opt => {
  opt.addEventListener('click', () => {
    document.querySelectorAll('.cat-picker .cat-option').forEach(o => o.classList.remove('selected'));
    opt.classList.add('selected');
    opt.querySelector('input').checked = true;
  });
});

// Géolocalisation
document.getElementById('geoloc')?.addEventListener('click', () => {
  const status = document.getElementById('geolocStatus');
  if (!navigator.geolocation) {
    status.textContent = "La géolocalisation n'est pas disponible sur votre appareil.";
    return;
  }
  status.textContent = 'Localisation en cours...';
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      document.getElementById('lat').value = pos.coords.latitude.toFixed(7);
      document.getElementById('lng').value = pos.coords.longitude.toFixed(7);
      status.textContent = '✓ Position enregistrée (' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4) + ')';
      status.style.color = 'var(--primary)';
    },
    () => { status.textContent = "Impossible d'obtenir votre position. Saisissez l'adresse manuellement."; }
  );
});
</script>
