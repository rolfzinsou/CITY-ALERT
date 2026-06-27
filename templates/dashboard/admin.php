<?php
/** @var array<string,int> $statsStatut */
/** @var array<int,int> $statsCategorie */
/** @var \App\Entity\Categorie[] $categories */
/** @var \App\Entity\Signalement[] $derniersSignalements */
/** @var ?float $delaiMoyenJours */
/** @var int $totalCitoyens */
/** @var int $totalAgents */
$total = array_sum($statsStatut);
?>
<div class="main-head" style="margin-top:24px;">
  <h1>Tableau de bord <span class="count">Administration</span></h1>
  <div style="display:flex;gap:8px;">
    <a href="<?= url('/admin/agents') ?>" class="btn btn-ghost">Gérer les agents</a>
    <a href="<?= url('/admin/verifications') ?>" class="btn btn-ghost">Vérifications</a>
    <a href="<?= url('/admin/export.csv') ?>" class="btn btn-primary">Exporter CSV</a>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card highlight">
    <div class="stat-label">Total signalements</div>
    <div class="stat-value"><?= $total ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">En attente</div>
    <div class="stat-value"><?= ($statsStatut['nouveau'] ?? 0) + ($statsStatut['en_cours'] ?? 0) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Résolus</div>
    <div class="stat-value"><?= $statsStatut['resolu'] ?? 0 ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Délai moyen</div>
    <div class="stat-value"><?= $delaiMoyenJours !== null ? $delaiMoyenJours . 'j' : '—' ?></div>
  </div>
</div>

<div class="chart-row">
  <div class="chart-card">
    <div class="chart-head">
      <div class="chart-title">Répartition par statut</div>
    </div>
    <ul class="legend" style="margin-top:24px;">
      <?php foreach ($statsStatut as $statut => $count): ?>
        <li>
          <span class="dot" style="background:<?= $statut === 'nouveau' ? '#3B82F6' : ($statut === 'en_cours' ? '#F59E0B' : ($statut === 'resolu' ? '#10B981' : '#EF4444')) ?>"></span>
          <?= e(ucfirst(str_replace('_', ' ', $statut))) ?> · <strong><?= $count ?></strong>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="chart-card">
    <div class="chart-head"><div class="chart-title">Par catégorie</div></div>
    <ul class="legend" style="margin-top:16px;">
      <?php foreach ($categories as $cat): ?>
        <li>
          <span class="dot" style="background:var(--cat-<?= $cat->getTypeClass()->value === 'eau_assainissement' ? 'eau' : $cat->getTypeClass()->value ?>)"></span>
          <?= e($cat->getNom()) ?> · <strong><?= $statsCategorie[$cat->getId()] ?? 0 ?></strong>
          <a href="<?= url('/admin/export.csv?categorie=' . ($cat->getId())) ?>" style="margin-left:auto;font-size:.75rem;color:var(--primary);">CSV</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="chart-card">
  <div class="chart-head"><div class="chart-title">Derniers signalements</div></div>
  <table style="width:100%;border-collapse:collapse;font-size:.9rem;margin-top:12px;">
    <thead>
      <tr style="text-align:left;color:var(--text-muted);font-size:.8rem;text-transform:uppercase;">
        <th style="padding:12px 8px;">Titre</th>
        <th style="padding:12px 8px;">Catégorie</th>
        <th style="padding:12px 8px;">Statut</th>
        <th style="padding:12px 8px;">Auteur</th>
        <th style="padding:12px 8px;">Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($derniersSignalements as $s): ?>
        <tr style="border-top:1px solid var(--border);">
          <td style="padding:14px 8px;font-weight:500;">
            <a href="<?= url('/signalements/' . ($s->getId())) ?>" style="color:inherit;text-decoration:none;"><?= e($s->getTitre()) ?></a>
          </td>
          <td style="padding:14px 8px;"><?= e($s->getCategorie()->getNom()) ?></td>
          <td style="padding:14px 8px;">
            <span class="badge status-<?= str_replace('_', '-', $s->getStatut()->value) ?>">
              <span class="badge-dot"></span><?= e($s->getStatut()->libelle()) ?>
            </span>
          </td>
          <td style="padding:14px 8px;"><?= e($s->getAuteur()->getNom()) ?></td>
          <td style="padding:14px 8px;color:var(--text-muted);"><?= e($s->getCreatedAt()->format('d/m H:i')) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
