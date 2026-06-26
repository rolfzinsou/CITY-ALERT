<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\RepositoryInterface;
use App\Core\Database;
use App\Entity\Categorie;
use App\Entity\Dechets;
use App\Entity\EauAssainissement;
use App\Entity\Eclairage;
use App\Entity\Voirie;
use App\Enum\TypeCategorie;
use InvalidArgumentException;
use PDO;

/**
 * @implements RepositoryInterface<Categorie>
 *
 * Les catégories sont fixes (les 4 imposées par le sujet). On les LIT
 * essentiellement ; les méthodes save/delete sont fournies pour
 * complétude mais ne servent qu'à l'initialisation/maintenance.
 */
class CategorieRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function find(int $id): ?Categorie
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findByType(TypeCategorie $type): ?Categorie
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE type_class = :t');
        $stmt->execute(['t' => $type->value]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return Categorie[] */
    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof Categorie) {
            throw new InvalidArgumentException('Entité attendue : Categorie.');
        }

        if ($entity->getId() === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO categories (nom, type_class) VALUES (:n, :t)'
            );
            $stmt->execute([
                'n' => $entity->getNom(),
                't' => $entity->getTypeClass()->value,
            ]);
            $entity->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE categories SET nom = :n WHERE id = :id'
            );
            $stmt->execute(['n' => $entity->getNom(), 'id' => $entity->getId()]);
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Mapping polymorphe vers la sous-classe correspondante.
     *
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Categorie
    {
        $type = TypeCategorie::from((string) $row['type_class']);
        $nom  = (string) $row['nom'];

        $cat = match ($type) {
            TypeCategorie::VOIRIE             => new Voirie($nom),
            TypeCategorie::ECLAIRAGE          => new Eclairage($nom),
            TypeCategorie::DECHETS            => new Dechets($nom),
            TypeCategorie::EAU_ASSAINISSEMENT => new EauAssainissement($nom),
        };

        $cat->setId((int) $row['id']);
        return $cat;
    }
}
