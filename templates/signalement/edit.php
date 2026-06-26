<?php
/** @var \App\Entity\Signalement $signalement */
/** @var \App\Entity\Categorie[] $categories */
?>
<h1 style="margin:24px 0 24px;">Modifier le signalement</h1>

<form action="<?= url('/signalements/' . ($signalement->getId()) . '/editer') ?>" method="post" class="form-grid">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

  <div class="field">
    <label class="label">Titre</label>
    <input type="text" name="titre" class="input" value="<?= e($signalement->getTitre()) ?>" required>
  </div>

  <div class="field">
    <label class="label">Description</label>
    <textarea name="description" class="textarea" required><?= e($signalement->getDescription()) ?></textarea>
  </div>

  <div class="field">
    <label class="label">Adresse</label>
    <input type="text" name="adresse" class="input" value="<?= e($signalement->getAdresse()) ?>" required>
  </div>

  <div class="field">
    <label class="label">Catégorie</label>
    <select name="categorie_id" class="select">
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat->getId() ?>" <?= $cat->getId() === $signalement->getCategorie()->getId() ? 'selected' : '' ?>>
          <?= e($cat->getNom()) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;">
    <a href="<?= url('/signalements/' . ($signalement->getId())) ?>" class="btn btn-ghost">Annuler</a>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
  </div>
</form>
