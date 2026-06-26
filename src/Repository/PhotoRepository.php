<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\RepositoryInterface;
use App\Core\Database;
use App\Entity\Photo;
use App\Entity\Signalement;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * @implements RepositoryInterface<Photo>
 */
class PhotoRepository implements RepositoryInterface
{
    private PDO $pdo;
    private SignalementRepository $signRepo;

    public function __construct(SignalementRepository $signRepo)
    {
        $this->pdo      = Database::getInstance();
        $this->signRepo = $signRepo;
    }

    public function find(int $id): ?Photo
    {
        $stmt = $this->pdo->prepare('SELECT * FROM photos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return Photo[] */
    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM photos ORDER BY signalement_id, ordre')->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    /** @return Photo[] */
    public function findParSignalement(int $signalementId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM photos WHERE signalement_id = :s ORDER BY ordre ASC'
        );
        $stmt->execute(['s' => $signalementId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof Photo) {
            throw new InvalidArgumentException('Entité attendue : Photo.');
        }

        if ($entity->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO photos (chemin, signalement_id, ordre, created_at, updated_at)
                 VALUES (:c, :s, :o, :crt, :upd)'
            );
            $stmt->execute([
                'c'   => $entity->getChemin(),
                's'   => $entity->getSignalement()->getId(),
                'o'   => $entity->getOrdre(),
                'crt' => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                'upd' => $entity->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);
            $entity->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE photos SET chemin = :c, ordre = :o, updated_at = :upd WHERE id = :id'
            );
            $stmt->execute([
                'c'   => $entity->getChemin(),
                'o'   => $entity->getOrdre(),
                'upd' => $entity->getUpdatedAt()->format('Y-m-d H:i:s'),
                'id'  => $entity->getId(),
            ]);
        }
    }

    public function delete(int $id): bool
    {
        // On supprime aussi le fichier physique (si présent)
        $photo = $this->find($id);
        if ($photo !== null) {
            $abs = UPLOAD_PATH . '/signalements/' . basename($photo->getChemin());
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
        $stmt = $this->pdo->prepare('DELETE FROM photos WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Photo
    {
        $signalement = $this->signRepo->find((int) $row['signalement_id']);
        if ($signalement === null) {
            throw new \RuntimeException("Photo #{$row['id']} : signalement introuvable.");
        }

        $p = new Photo(
            chemin:      (string) $row['chemin'],
            signalement: $signalement,
            ordre:       (int) ($row['ordre'] ?? 0),
        );
        $p->setId((int) $row['id']);
        $p->setTimestamps(
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
        return $p;
    }
}
