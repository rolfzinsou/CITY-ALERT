<?php /** @var string $message */ ?>
<div style="text-align:center;padding:80px 24px;">
  <div style="font-size:6rem;font-weight:800;color:#EF4444;letter-spacing:-0.04em;line-height:1;">403</div>
  <h1 style="margin:16px 0 8px;">Accès refusé</h1>
  <p style="color:var(--text-muted);margin-bottom:32px;"><?= e($message ?? "Vous n'avez pas les droits pour accéder à cette page.") ?></p>
  <a href="<?= url('/') ?>" class="btn btn-primary btn-lg">Retour à l'accueil</a>
</div>
