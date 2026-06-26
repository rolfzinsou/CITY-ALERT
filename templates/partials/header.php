<?php
/** @var ?\App\Entity\Utilisateur $utilisateurCourant */
/** @var string $csrf */
?>
<header class="header">
  <div class="header-inner">

    <!-- Gauche : hamburger + logo -->
    <div class="header-left">
      <button class="hamburger" onclick="toggleMobileMenu()" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <a href="<?= url('/') ?>" class="logo">
        <span class="logo-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 7v10l8 5 8-5V7l-8-5z"/><circle cx="12" cy="12" r="3"/></svg>
        </span>
        <span class="logo-text">City<span>Alert</span></span>
      </a>
    </div>

    <!-- Centre : barre de recherche -->
    <form action="<?= url('/') ?>" method="get" class="search-wrap">
      <span class="search-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </span>
      <input type="text" name="q" class="search" placeholder="Rechercher un signalement, un lieu..." value="<?= e($_GET['q'] ?? '') ?>">
      <span class="search-shortcut">/</span>
    </form>

    <!-- Droite : actions -->
    <div class="header-right">
      <button class="icon-btn theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Changer le thème"></button>

      <?php if ($utilisateurCourant !== null): ?>
        <a href="<?= url(match ($utilisateurCourant->getRole()->value) {
          'administrateur' => '/dashboard',
          'agent'          => '/agent/signalements',
          default          => '/profil',
        }) ?>" class="profile-btn">
          <span class="avatar"><?= e(mb_strtoupper(mb_substr($utilisateurCourant->getNom(), 0, 2))) ?></span>
          <span class="profile-name"><?= e($utilisateurCourant->getNom()) ?></span>
        </a>
        <form action="<?= url('/logout') ?>" method="post" style="display:inline">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <button class="icon-btn" title="Déconnexion">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          </button>
        </form>
      <?php else: ?>
        <a href="<?= url('/login') ?>" class="btn btn-ghost header-auth-btn">Se connecter</a>
        <a href="<?= url('/register') ?>" class="btn btn-primary header-auth-btn">S'inscrire</a>
      <?php endif; ?>
    </div>

  </div>

  <!-- Recherche mobile (sous le header) -->
  <div class="search-mobile">
    <form action="<?= url('/') ?>" method="get" class="search-wrap">
      <span class="search-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </span>
      <input type="text" name="q" class="search" placeholder="Rechercher...">
    </form>
  </div>
</header>

<!-- Menu mobile -->
<div class="mobile-menu" id="mobileMenu">
  <div class="mobile-menu-panel">
    <button class="mobile-menu-close" onclick="toggleMobileMenu()">
      <svg viewBox="0 0 24 24" width="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="side-title">Navigation</div>
    <ul class="side-nav">
      <li><a href="<?= url('/') ?>" class="side-link">Fil d'actualité</a></li>
      <?php if ($utilisateurCourant !== null): ?>
        <li><a href="<?= url('/signalements/nouveau') ?>" class="side-link">Nouveau signalement</a></li>
        <li><a href="<?= url('/profil') ?>" class="side-link">Mon profil</a></li>
        <?php if ($utilisateurCourant->getRole()->value === 'administrateur'): ?>
          <li><a href="<?= url('/dashboard') ?>" class="side-link">Tableau de bord</a></li>
          <li><a href="<?= url('/admin/agents') ?>" class="side-link">Agents</a></li>
          <li><a href="<?= url('/admin/verifications') ?>" class="side-link">Vérifications</a></li>
        <?php elseif ($utilisateurCourant->getRole()->value === 'agent'): ?>
          <li><a href="<?= url('/agent/signalements') ?>" class="side-link">Mes signalements</a></li>
        <?php endif; ?>
        <li>
          <form action="<?= url('/logout') ?>" method="post">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <button class="side-link" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;color:var(--status-rejected-text);">
              Déconnexion
            </button>
          </form>
        </li>
      <?php else: ?>
        <li><a href="<?= url('/login') ?>" class="side-link">Se connecter</a></li>
        <li><a href="<?= url('/register') ?>" class="side-link">S'inscrire</a></li>
      <?php endif; ?>
    </ul>
    <div class="side-title">Catégories</div>
    <ul class="side-nav">
      <li><a href="<?= url('/?categorie=1') ?>" class="side-link"><span class="cat-icon voirie"></span> Voirie</a></li>
      <li><a href="<?= url('/?categorie=2') ?>" class="side-link"><span class="cat-icon eclairage"></span> Éclairage</a></li>
      <li><a href="<?= url('/?categorie=3') ?>" class="side-link"><span class="cat-icon dechets"></span> Déchets</a></li>
      <li><a href="<?= url('/?categorie=4') ?>" class="side-link"><span class="cat-icon eau"></span> Eau &amp; assainissement</a></li>
    </ul>
  </div>
</div>
