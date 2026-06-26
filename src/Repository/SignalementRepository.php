<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\RepositoryInterface;
use App\Core\Database;
use App\Entity\Signalement;
use App\Enum\Statut;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * @implements RepositoryInterface<Signalement>
 *
 * CRUD + recherches métier sur les signalements.
 * Délègue à UtilisateurRepository et CategorieRepository pour reconstituer
 * l'auteur et la catégorie au moment de l'hydratation.
 */
class SignalementRepository implements RepositoryInterface
{
    private PDO $pdo;
    private UtilisateurRepository $userRepo;
    private CategorieRepository $catRepo;

    public function __construct(
        UtilisateurRepository $userRepo,
        CategorieRepository $catRepo,
    ) {
        $this->pdo      = Database::getInstance();
        $this->userRepo = $userRepo;
        $this->catRepo  = $catRepo;
    }

    public function find(int $id): ?Signalement
    {
        $stmt = $this->pdo->prepare('SELECT * FROM signalements WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Tri par défaut : du plus récent au plus ancien (exigence métier).
     *
     * @return Signalement[]
     */
    public function findAll(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM signalements ORDER BY date_creation DESC')
            ->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Recherche avec filtres combinables. Tous nullables.
     *
     * @param array{
     *   statut?: Statut|null,
     *   categorieId?: int|null,
     *   auteurId?: int|null,
     *   recherche?: string|null,
     *   depuis?: DateTimeImmutable|null,
     *   limit?: int|null,
     *   offset?: int|null
     * } $filtres
     * @return Signalement[]
     */
    public function rechercher(array $filtres = []): array
    {
        $sql      = 'SELECT * FROM signalements WHERE 1=1';
        $params   = [];

        if (!empty($filtres['statut'])) {
            $sql            .= ' AND statut = :statut';
            $params['statut'] = $filtres['statut']->value;
        }
        if (!empty($filtres['categorieId'])) {
            $sql              .= ' AND categorie_id = :cid';
            $params['cid']     = $filtres['categorieId'];
        }
        if (!empty($filtres['auteurId'])) {
            $sql              .= ' AND auteur_id = :aid';
            $params['aid']     = $filtres['auteurId'];
        }
        if (!empty($filtres['recherche'])) {
            $sql           .= ' AND (titre LIKE :q OR description LIKE :q OR adresse LIKE :q)';
            $params['q']    = '%' . $filtres['recherche'] . '%';
        }
        if (!empty($filtres['depuis'])) {
            $sql              .= ' AND date_creation >= :depuis';
            $params['depuis']  = $filtres['depuis']->format('Y-m-d H:i:s');
        }

        $sql .= ' ORDER BY date_creation DESC';

        if (!empty($filtres['limit'])) {
            $sql .= ' LIMIT ' . (int) $filtres['limit'];
            if (!empty($filtres['offset'])) {
                $sql .= ' OFFSET ' . (int) $filtres['offset'];
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof Signalement) {
            throw new InvalidArgumentException('Entité attendue : Signalement.');
        }
        $entity->getId() === null ? $this->insert($entity) : $this->update($entity);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM signalements WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ---------- Soutiens (table d'association) ----------

    public function ajouterSoutien(int $signalementId, int $utilisateurId): bool
    {
        try {
            $this->pdo->prepare(
                'INSERT IGNORE INTO soutiens (signalement_id, utilisateur_id, created_at)
                 VALUES (:s, :u, NOW())'
            )->execute(['s' => $signalementId, 'u' => $utilisateurId]);
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    public function retirerSoutien(int $signalementId, int $utilisateurId): bool
    {
        return $this->pdo->prepare(
            'DELETE FROM soutiens WHERE signalement_id = :s AND utilisateur_id = :u'
        )->execute(['s' => $signalementId, 'u' => $utilisateurId]);
    }

    public function aSoutenu(int $signalementId, int $utilisateurId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM soutiens WHERE signalement_id = :s AND utilisateur_id = :u'
        );
        $stmt->execute(['s' => $signalementId, 'u' => $utilisateurId]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return int[] IDs des signalements soutenus par cet utilisateur */
    public function soutiensParUtilisateur(int $utilisateurId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT signalement_id FROM soutiens WHERE utilisateur_id = :u'
        );
        $stmt->execute(['u' => $utilisateurId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function compterSoutiens(int $signalementId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM soutiens WHERE signalement_id = :s'
        );
        $stmt->execute(['s' => $signalementId]);
        return (int) $stmt->fetchColumn();
    }

    // ---------- Statistiques (pour le dashboard) ----------

    /** @return array<string, int> */
    public function statistiquesParStatut(): array
    {
        $rows = $this->pdo
            ->query('SELECT statut, COUNT(*) AS n FROM signalements GROUP BY statut')
            ->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['statut']] = (int) $r['n'];
        }
        return $out;
    }

    /** @return array<int, int> categorie_id => count */
    public function statistiquesParCategorie(): array
    {
        $rows = $this->pdo
            ->query('SELECT categorie_id, COUNT(*) AS n FROM signalements GROUP BY categorie_id')
            ->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['categorie_id']] = (int) $r['n'];
        }
        return $out;
    }

    // ---------- Helpers privés ----------

    private function insert(Signalement $s): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO signalements
             (titre, description, adresse, statut, latitude, longitude,
              date_creation, auteur_id, categorie_id, created_at, updated_at)
             VALUES
             (:titre, :desc, :adr, :stt, :lat, :lng,
              :datec, :aid, :cid, :crt, :upd)'
        );
        $stmt->execute([
            'titre' => $s->getTitre(),
            'desc'  => $s->getDescription(),
            'adr'   => $s->getAdresse(),
            'stt'   => $s->getStatut()->value,
            'lat'   => $s->getLatitude(),
            'lng'   => $s->getLongitude(),
            'datec' => $s->getCreatedAt()->format('Y-m-d H:i:s'),
            'aid'   => $s->getAuteur()->getId(),
            'cid'   => $s->getCategorie()->getId(),
            'crt'   => $s->getCreatedAt()->format('Y-m-d H:i:s'),
            'upd'   => $s->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
        $s->setId((int) $this->pdo->lastInsertId());
    }

    private function update(Signalement $s): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE signalements SET
             titre = :titre, description = :desc, adresse = :adr, statut = :stt,
             latitude = :lat, longitude = :lng,
             categorie_id = :cid, updated_at = :upd
             WHERE id = :id'
        );
        $stmt->execute([
            'titre' => $s->getTitre(),
            'desc'  => $s->getDescription(),
            'adr'   => $s->getAdresse(),
            'stt'   => $s->getStatut()->value,
            'lat'   => $s->getLatitude(),
            'lng'   => $s->getLongitude(),
            'cid'   => $s->getCategorie()->getId(),
            'upd'   => $s->getUpdatedAt()->format('Y-m-d H:i:s'),
            'id'    => $s->getId(),
        ]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Signalement
    {
        $auteur    = $this->userRepo->find((int) $row['auteur_id']);
        $categorie = $this->catRepo->find((int) $row['categorie_id']);

        if ($auteur === null || $categorie === null) {
            throw new \RuntimeException(
                "Signalement #{$row['id']} : auteur ou catégorie introuvable."
            );
        }

        $s = new Signalement(
            titre:       (string) $row['titre'],
            description: (string) $row['description'],
            adresse:     (string) $row['adresse'],
            auteur:      $auteur,
            categorie:   $categorie,
            statut:      Statut::from((string) $row['statut']),
            latitude:    $row['latitude']  !== null ? (float) $row['latitude']  : null,
            longitude:   $row['longitude'] !== null ? (float) $row['longitude'] : null,
        );
        $s->setId((int) $row['id']);
        $s->setNombreSoutiens($this->compterSoutiens((int) $row['id']));
        $s->setTimestamps(
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
        return $s;
    }
}
