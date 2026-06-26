<?php
/** @var array<string,string> $erreurs */
/** @var array $old */
$erreurs = $erreurs ?? [];
$old     = $old     ?? [];
?>
<div class="auth-shell" style="min-height:calc(100vh - 200px);">
  <div class="auth-side">
    <div>
      <div class="auth-brand">
        <span class="logo-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 7v10l8 5 8-5V7l-8-5z"/><circle cx="12" cy="12" r="3"/></svg>
        </span>
        CityAlert
      </div>
    </div>
    <div>
      <h2 class="auth-tagline">Ensemble, rendons notre ville meilleure.</h2>
      <p class="auth-sub">Signalez les problèmes que vous observez et suivez leur traitement en direct.</p>
    </div>
    <div class="auth-stats">
      <div class="auth-stat"><div class="n">2 847</div><div class="l">Signalements traités</div></div>
      <div class="auth-stat"><div class="n">4.2j</div><div class="l">Délai moyen</div></div>
      <div class="auth-stat"><div class="n">94%</div><div class="l">Satisfaction</div></div>
    </div>
  </div>

  <div class="auth-main">
    <div class="auth-card">
      <h1 class="auth-title">Bienvenue 👋</h1>
      <p class="auth-subtitle">Connectez-vous par email ou téléphone (+221).</p>

      <form action="<?= url('/login') ?>" method="post">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

        <div class="field">
          <label class="label">Email ou téléphone</label>
          <input type="text" name="identifiant" class="input" placeholder="vous@exemple.sn ou +221XXXXXXXXX"
                 value="<?= e($old['identifiant'] ?? '') ?>" required>
          <?php if (!empty($erreurs['identifiant'])): ?>
            <div class="hint" style="color:#EF4444"><?= e($erreurs['identifiant']) ?></div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label class="label">Mot de passe</label>
          <input type="password" name="mot_de_passe" class="input" placeholder="••••••••" required>
        </div>

        <div style="display:flex;align-items:center;gap:8px;margin-bottom:24px;font-size:.875rem;">
          <input type="checkbox" name="se_souvenir" id="remember" value="1" style="accent-color:var(--primary);width:16px;height:16px;">
          <label for="remember" style="cursor:pointer;">Se souvenir de moi</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">Se connecter</button>
      </form>

      <p class="auth-foot">Pas encore de compte ? <a href="<?= url('/register') ?>">Créer un compte</a></p>
    </div>
  </div>
</div>
