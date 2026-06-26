<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Entity\Signalement;
use App\Enum\Statut;
use App\Repository\CategorieRepository;
use App\Repository\CommentaireRepository;
use App\Repository\PhotoRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;

/**
 * ApiController — bonus du sujet : « petite API REST JSON (lecture seule) ».
 *
 * Endpoints :
 *   GET /api/signalements              → liste paginée (filtres : statut, categorie, q)
 *   GET /api/signalements/{id}         → détail (avec photos et commentaires)
 *   GET /api/categories                → liste des catégories
 *   GET /api/stats                     → statistiques globales
 *
 * Pas d'authentification requise (lecture publique). Pas d'écriture.
 */
final class ApiController extends AbstractController
{
    private SignalementRepository $signRepo;
    private CategorieRepository $catRepo;
    private PhotoRepository $photoRepo;
    private CommentaireRepository $commRepo;

    public function __construct()
    {
        $this->catRepo   = new CategorieRepository();
        $userRepo        = new UtilisateurRepository($this->catRepo);
        $this->signRepo  = new SignalementRepository($userRepo, $this->catRepo);
        $this->photoRepo = new PhotoRepository($this->signRepo);
        $this->commRepo  = new CommentaireRepository($userRepo, $this->signRepo);
    }

    public function signalements(Request $request): Response
    {
        $filtres = ['limit' => (int) ($request->get('limit') ?? 20)];
        if ($s = $request->get('statut')) {
            $filtres['statut'] = Statut::tryFrom((string) $s);
        }
        if ($c = $request->get('categorie')) {
            $filtres['categorieId'] = (int) $c;
        }
        if ($q = $request->get('q')) {
            $filtres['recherche'] = (string) $q;
        }

        $signalements = $this->signRepo->rechercher(array_filter($filtres));

        return Response::json([
            'data'  => array_map([$this, 'serialiserSignalement'], $signalements),
            'count' => count($signalements),
        ]);
    }

    public function signalement(Request $request, string $id): Response
    {
        $s = $this->signRepo->find((int) $id);
        if ($s === null) {
            return Response::json(['error' => 'Signalement introuvable'], 404);
        }
        $s->setPhotos($this->photoRepo->findParSignalement((int) $id));
        $s->setCommentaires($this->commRepo->findRacinesParSignalement((int) $id));

        return Response::json(['data' => $this->serialiserSignalementDetail($s)]);
    }

    public function categories(Request $request): Response
    {
        $list = array_map(fn($c) => [
            'id'       => $c->getId(),
            'nom'      => $c->getNom(),
            'type'     => $c->getTypeClass()->value,
            'priorite' => $c->getPriorite()->libelle(),
            'delai_j'  => $c->getDelaiTraitementJours(),
        ], $this->catRepo->findAll());

        return Response::json(['data' => $list]);
    }

    public function stats(Request $request): Response
    {
        return Response::json([
            'par_statut'    => $this->signRepo->statistiquesParStatut(),
            'par_categorie' => $this->signRepo->statistiquesParCategorie(),
        ]);
    }

    // ---------- Sérialiseurs ----------

    /** @return array<string, mixed> */
    private function serialiserSignalement(Signalement $s): array
    {
        return [
            'id'              => $s->getId(),
            'titre'           => $s->getTitre(),
            'description'     => mb_substr($s->getDescription(), 0, 200),
            'adresse'         => $s->getAdresse(),
            'statut'          => $s->getStatut()->value,
            'statut_libelle'  => $s->getStatut()->libelle(),
            'categorie'       => [
                'id'  => $s->getCategorie()->getId(),
                'nom' => $s->getCategorie()->getNom(),
            ],
            'auteur'          => $s->getAuteur()->getNom(),
            'latitude'        => $s->getLatitude(),
            'longitude'       => $s->getLongitude(),
            'nombre_soutiens' => $s->getNombreSoutiens(),
            'date_creation'   => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function serialiserSignalementDetail(Signalement $s): array
    {
        $base = $this->serialiserSignalement($s);
        $base['description'] = $s->getDescription();
        $base['photos']      = array_map(
            fn($p) => ['id' => $p->getId(), 'url' => $p->getChemin(), 'ordre' => $p->getOrdre()],
            $s->getPhotos()
        );
        $base['commentaires'] = array_map(
            fn($c) => [
                'id'          => $c->getId(),
                'auteur'      => $c->getAuteur()->getNom(),
                'contenu'     => $c->getContenu(),
                'likes'       => $c->getNombreLikes(),
                'date'        => $c->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'reponses'    => count($c->getReponses()),
            ],
            $s->getCommentaires()
        );
        return $base;
    }
}
