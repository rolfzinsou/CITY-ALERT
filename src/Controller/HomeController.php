<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Enum\Statut;
use App\Repository\CategorieRepository;
use App\Repository\PhotoRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;
use DateTimeImmutable;

/**
 * HomeController — page d'accueil publique.
 *
 * Affiche :
 *  - La hero section avec stats animées (nb signalements, citoyens, résolus)
 *  - Le fil des signalements (tri par défaut : récent → ancien)
 *  - Les filtres (statut, catégorie, date)
 */
final class HomeController extends AbstractController
{
    public function index(Request $request): Response
    {
        $catRepo  = new CategorieRepository();
        $userRepo = new UtilisateurRepository($catRepo);
        $signRepo = new SignalementRepository($userRepo, $catRepo);

        // ---------- Filtres ----------
        $filtres = [];
        if ($s = $request->get('statut')) {
            $filtres['statut'] = Statut::tryFrom((string) $s);
        }
        if ($c = $request->get('categorie')) {
            $filtres['categorieId'] = (int) $c;
        }
        if ($q = $request->get('q')) {
            $filtres['recherche'] = (string) $q;
        }
        if ($periode = $request->get('periode')) {
            $depuis = match ((string) $periode) {
                'auj'      => new DateTimeImmutable('today'),
                'hier'     => new DateTimeImmutable('yesterday'),
                'semaine'  => new DateTimeImmutable('-7 days'),
                'mois'     => new DateTimeImmutable('-30 days'),
                default    => null,
            };
            if ($depuis !== null) {
                $filtres['depuis'] = $depuis;
            }
        }

        $filtres['limit'] = 20;
        $signalements     = $signRepo->rechercher(array_filter($filtres));

        $photoRepo = new PhotoRepository($signRepo);
        foreach ($signalements as $signalement) {
            $signalement->setPhotos($photoRepo->findParSignalement((int) $signalement->getId()));
        }

        // ---------- Stats pour la hero section ----------
        $statsStatut = $signRepo->statistiquesParStatut();
        $stats = [
            'total'      => array_sum($statsStatut),
            'resolus'    => $statsStatut['resolu']   ?? 0,
            'en_cours'   => $statsStatut['en_cours'] ?? 0,
            'citoyens'   => $this->compterCitoyens(),
        ];

        $userCourant   = $this->utilisateur();
        $soutiensUser  = $userCourant !== null
            ? array_map('intval', $signRepo->soutiensParUtilisateur((int) $userCourant->getId()))
            : [];

        return $this->vue('home/index', [
            'signalements' => $signalements,
            'categories'   => $catRepo->findAll(),
            'stats'        => $stats,
            'filtres'      => $filtres,
            'soutiensUser' => $soutiensUser,
        ]);
    }

    private function compterCitoyens(): int
    {
        // Petite optimisation : on évite findAll() pour ne pas charger toutes les entités
        $pdo  = \App\Core\Database::getInstance();
        $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'citoyen'");
        return (int) $stmt->fetchColumn();
    }
}
