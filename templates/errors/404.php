<?php /** @var string $message */ ?>
<div style="text-align:center;padding:80px 24px;">
  <div style="font-size:6rem;font-weight:800;color:var(--primary);letter-spacing:-0.04em;line-height:1;">404</div>
  <h1 style="margin:16px 0 8px;">Page introuvable</h1>
  <p style="color:var(--text-muted);margin-bottom:32px;"><?= e($message ?? "La page que vous cherchez n'existe pas ou a été déplacée.") ?></p>
  <a href="<?= url('/') ?>" class="btn btn-primary btn-lg">Retour à l'accueil</a>
</div>
