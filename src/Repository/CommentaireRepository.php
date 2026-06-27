<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\RepositoryInterface;
use App\Core\Database;
use App\Entity\Commentaire;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * @implements RepositoryInterface<Commentaire>
 *
 * Gère les commentaires plats et l'arborescence (réponses).
 * Les likes sont gérés via la table d'association `likes_commentaires`.
 */
class CommentaireRepository implements RepositoryInterface
{
    private PDO $pdo;
    private UtilisateurRepository $userRepo;
    private SignalementRepository $signRepo;

    public function __construct(UtilisateurRepository $userRepo, SignalementRepository $signRepo)
    {
        $this->pdo      = Database::getInstance();
        $this->userRepo = $userRepo;
        $this->signRepo = $signRepo;
    }

    public function find(int $id): ?Commentaire
    {
        $stmt = $this->pdo->prepare('SELECT * FROM commentaires WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return Commentaire[] */
    public function findAll(): array
    {
        $rows = $this->pdo
            ->query('SELECT * FROM commentaires ORDER BY date_commentaire DESC')
            ->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Récupère uniquement les commentaires racines d'un signalement
     * (les réponses sont peuplées via ::peuplerReponses()).
     *
     * @return Commentaire[]
     */
    public function findRacinesParSignalement(int $signalementId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM commentaires
             WHERE signalement_id = :s AND parent_id IS NULL
             ORDER BY date_commentaire ASC'
        );
        $stmt->execute(['s' => $signalementId]);
        $racines = array_map([$this, 'hydrate'], $stmt->fetchAll());

        // Pour chaque racine, on peuple ses réponses récursivement.
        foreach ($racines as $c) {
            $this->peuplerReponses($c);
        }
        return $racines;
    }

    /** @return Commentaire[] */
    private function findReponses(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM commentaires
             WHERE parent_id = :p
             ORDER BY date_commentaire ASC'
        );
        $stmt->execute(['p' => $parentId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    private function peuplerReponses(Commentaire $c): void
    {
        if ($c->getId() === null) return;
        $reponses = $this->findReponses($c->getId());
        foreach ($reponses as $r) {
            $r->setParent($c);
            $this->peuplerReponses($r); // récursif si on veut des réponses de réponses
            $c->ajouterReponse($r);
        }
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof Commentaire) {
            throw new InvalidArgumentException('Entité attendue : Commentaire.');
        }

        if ($entity->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO commentaires
                 (contenu, date_commentaire, auteur_id, signalement_id, parent_id, nombre_likes, created_at, updated_at)
                 VALUES (:c, :d, :a, :s, :p, :nl, :crt, :upd)'
            );
            $stmt->execute([
                'c'   => $entity->getContenu(),
                'd'   => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                'a'   => $entity->getAuteur()->getId(),
                's'   => $entity->getSignalement()->getId(),
                'p'   => $entity->getParent()?->getId(),
                'nl'  => $entity->getNombreLikes(),
                'crt' => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                'upd' => $entity->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);
            $entity->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE commentaires SET contenu = :c, nombre_likes = :nl, updated_at = :upd
                 WHERE id = :id'
            );
            $stmt->execute([
                'c'   => $entity->getContenu(),
                'nl'  => $entity->getNombreLikes(),
                'upd' => $entity->getUpdatedAt()->format('Y-m-d H:i:s'),
                'id'  => $entity->getId(),
            ]);
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM commentaires WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ---------- Likes ----------

    public function liker(int $commentaireId, int $utilisateurId): bool
    {
        try {
            $this->pdo->prepare(
                'INSERT IGNORE INTO likes_commentaires (commentaire_id, utilisateur_id, created_at)
                 VALUES (:c, :u, NOW())'
            )->execute(['c' => $commentaireId, 'u' => $utilisateurId]);
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    public function unliker(int $commentaireId, int $utilisateurId): bool
    {
        return $this->pdo->prepare(
            'DELETE FROM likes_commentaires WHERE commentaire_id = :c AND utilisateur_id = :u'
        )->execute(['c' => $commentaireId, 'u' => $utilisateurId]);
    }

    public function aLike(int $commentaireId, int $utilisateurId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM likes_commentaires WHERE commentaire_id = :c AND utilisateur_id = :u'
        );
        $stmt->execute(['c' => $commentaireId, 'u' => $utilisateurId]);
        return (bool) $stmt->fetchColumn();
    }

    public function compterLikes(int $commentaireId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM likes_commentaires WHERE commentaire_id = :c'
        );
        $stmt->execute(['c' => $commentaireId]);
        return (int) $stmt->fetchColumn();
    }

    // ---------- Helpers ----------

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Commentaire
    {
        $auteur      = $this->userRepo->find((int) $row['auteur_id']);
        $signalement = $this->signRepo->find((int) $row['signalement_id']);

        if ($auteur === null || $signalement === null) {
            throw new \RuntimeException("Commentaire #{$row['id']} : auteur ou signalement introuvable.");
        }

        $c = new Commentaire(
            contenu:     (string) $row['contenu'],
            auteur:      $auteur,
            signalement: $signalement,
        );
        $c->setId((int) $row['id']);
        $c->setNombreLikes((int) ($row['nombre_likes'] ?? 0));
        $c->setTimestamps(
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
        return $c;
    }
}
