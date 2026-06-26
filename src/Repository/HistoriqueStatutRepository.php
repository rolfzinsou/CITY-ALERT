<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\RepositoryInterface;
use App\Core\Database;
use App\Entity\Agent;
use App\Entity\HistoriqueStatut;
use App\Enum\Statut;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * @implements RepositoryInterface<HistoriqueStatut>
 */
class HistoriqueStatutRepository implements RepositoryInterface
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

    public function find(int $id): ?HistoriqueStatut
    {
        $stmt = $this->pdo->prepare('SELECT * FROM historiques_statut WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return HistoriqueStatut[] */
    public function findAll(): array
    {
        $rows = $this->pdo->query(
            'SELECT * FROM historiques_statut ORDER BY date_modification DESC'
        )->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    /** @return HistoriqueStatut[] */
    public function findParSignalement(int $signalementId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM historiques_statut
             WHERE signalement_id = :s ORDER BY date_modification ASC'
        );
        $stmt->execute(['s' => $signalementId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof HistoriqueStatut) {
            throw new InvalidArgumentException('Entité attendue : HistoriqueStatut.');
        }
        if ($entity->getId() !== null) {
            // L'historique est immutable par design : on ne le met pas à jour.
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO historiques_statut
             (ancien_statut, nouveau_statut, remarque, date_modification,
              signalement_id, agent_id, created_at, updated_at)
             VALUES
             (:anc, :nv, :rem, :dt, :sid, :aid, :crt, :upd)'
        );
        $stmt->execute([
            'anc' => $entity->getAncien()->value,
            'nv'  => $entity->getNouveau()->value,
            'rem' => $entity->getRemarque(),
            'dt'  => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
            'sid' => $entity->getSignalement()->getId(),
            'aid' => $entity->getAgent()->getId(),
            'crt' => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
            'upd' => $entity->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
        $entity->setId((int) $this->pdo->lastInsertId());
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM historiques_statut WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): HistoriqueStatut
    {
        $signalement = $this->signRepo->find((int) $row['signalement_id']);
        $agent       = $this->userRepo->find((int) $row['agent_id']);

        if ($signalement === null || !$agent instanceof Agent) {
            throw new \RuntimeException(
                "HistoriqueStatut #{$row['id']} : signalement ou agent introuvable."
            );
        }

        $h = new HistoriqueStatut(
            signalement: $signalement,
            agent:       $agent,
            ancien:      Statut::from((string) $row['ancien_statut']),
            nouveau:     Statut::from((string) $row['nouveau_statut']),
            remarque:    $row['remarque'] ?? null,
        );
        $h->setId((int) $row['id']);
        $h->setTimestamps(
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
        return $h;
    }
}
