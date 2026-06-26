<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\RepositoryInterface;
use App\Core\Database;
use App\Entity\Administrateur;
use App\Entity\Agent;
use App\Entity\Citoyen;
use App\Entity\Utilisateur;
use App\Enum\Role;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * @implements RepositoryInterface<Utilisateur>
 *
 * Gère la persistance des trois sous-classes via Single Table Inheritance.
 * Le rôle stocké en colonne `role` détermine quelle sous-classe instancier.
 */
class UtilisateurRepository implements RepositoryInterface
{
    private PDO $pdo;
    private ?CategorieRepository $categorieRepo = null;

    public function __construct(?CategorieRepository $categorieRepo = null)
    {
        $this->pdo           = Database::getInstance();
        $this->categorieRepo = $categorieRepo;
    }

    public function find(int $id): ?Utilisateur
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?Utilisateur
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findByTelephone(string $telephone): ?Utilisateur
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE telephone = :tel');
        $stmt->execute(['tel' => $telephone]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Connexion par identifiant unique : email OU téléphone (+221XXXXXXXXX).
     * Utilisé par AuthService.
     */
    public function findByEmailOuTelephone(string $identifiant): ?Utilisateur
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM utilisateurs WHERE email = ? OR telephone = ? LIMIT 1'
        );
        $stmt->execute([$identifiant, $identifiant]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return Utilisateur[] */
    public function findAll(): array
    {
        $rows = $this->pdo->query('SELECT * FROM utilisateurs ORDER BY created_at DESC')->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    /** @return Agent[] */
    public function findAgentsParCategorie(int $categorieId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.* FROM utilisateurs u
             JOIN agent_categorie ac ON ac.agent_id = u.id
             WHERE u.role = 'agent' AND ac.categorie_id = :cid"
        );
        $stmt->execute(['cid' => $categorieId]);
        $agents = [];
        foreach ($stmt->fetchAll() as $row) {
            $u = $this->hydrate($row);
            if ($u instanceof Agent) {
                $agents[] = $u;
            }
        }
        return $agents;
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof Utilisateur) {
            throw new InvalidArgumentException('Entité attendue : Utilisateur.');
        }
        $entity->getId() === null ? $this->insert($entity) : $this->update($entity);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ---------- Helpers privés ----------

    private function insert(Utilisateur $u): void
    {
        $sql = "INSERT INTO utilisateurs
                (nom, email, mot_de_passe, telephone, role, notifications_actives,
                 date_inscription, piece_identite_recto, piece_identite_verso, verifie,
                 service, niveau_acces, created_at, updated_at)
                VALUES
                (:nom, :email, :mdp, :tel, :role, :notif,
                 :date_insc, :piece_r, :piece_v, :verifie,
                 :service, :niveau, :created, :updated)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->buildParams($u));
        $u->setId((int) $this->pdo->lastInsertId());

        // Si c'est un agent et qu'il a une catégorie : on insère le lien
        if ($u instanceof Agent && $u->getCategorie()?->getId() !== null) {
            $stmt2 = $this->pdo->prepare(
                'INSERT INTO agent_categorie (agent_id, categorie_id) VALUES (:a, :c)'
            );
            $stmt2->execute(['a' => $u->getId(), 'c' => $u->getCategorie()->getId()]);
        }
    }

    private function update(Utilisateur $u): void
    {
        $sql = "UPDATE utilisateurs SET
                nom = :nom, email = :email, mot_de_passe = :mdp, telephone = :tel,
                role = :role, notifications_actives = :notif,
                date_inscription = :date_insc,
                piece_identite_recto = :piece_r, piece_identite_verso = :piece_v,
                verifie = :verifie, service = :service, niveau_acces = :niveau,
                updated_at = :updated
                WHERE id = :id";

        $params       = $this->buildParams($u);
        $params['id'] = $u->getId();
        $stmt         = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // Pour un agent : mise à jour de l'affectation à une catégorie
        if ($u instanceof Agent) {
            $this->pdo->prepare('DELETE FROM agent_categorie WHERE agent_id = :a')
                      ->execute(['a' => $u->getId()]);
            if ($u->getCategorie()?->getId() !== null) {
                $this->pdo->prepare(
                    'INSERT INTO agent_categorie (agent_id, categorie_id) VALUES (:a, :c)'
                )->execute(['a' => $u->getId(), 'c' => $u->getCategorie()->getId()]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function buildParams(Utilisateur $u): array
    {
        $isCitoyen = $u instanceof Citoyen;
        $isAgent   = $u instanceof Agent;
        $isAdmin   = $u instanceof Administrateur;

        return [
            'nom'       => $u->getNom(),
            'email'     => $u->getEmail(),
            'mdp'       => $u->getMotDePasseHash(),
            'tel'       => $u->getTelephone(),
            'role'      => $u->getRole()->value,
            'notif'     => $u->accepteNotifications() ? 1 : 0,
            'date_insc' => $isCitoyen ? $u->getDateInscription()->format('Y-m-d H:i:s') : null,
            'piece_r'   => $isCitoyen ? $u->getPieceIdentiteRecto() : null,
            'piece_v'   => $isCitoyen ? $u->getPieceIdentiteVerso() : null,
            'verifie'   => $isCitoyen ? ($u->estVerifie() ? 1 : 0) : null,
            'service'   => $isAgent ? $u->getService() : null,
            'niveau'    => $isAdmin ? $u->getNiveauAcces() : null,
            'created'   => $u->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated'   => $u->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Mapping polymorphe ligne SQL → instance concrète selon le rôle.
     *
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Utilisateur
    {
        $role = Role::from((string) $row['role']);

        $user = match ($role) {
            Role::CITOYEN        => $this->hydrateCitoyen($row),
            Role::AGENT          => $this->hydrateAgent($row),
            Role::ADMINISTRATEUR => $this->hydrateAdmin($row),
        };

        $user->setId((int) $row['id']);
        $user->setTimestamps(
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
        $user->activerNotifications((bool) ($row['notifications_actives'] ?? true));

        return $user;
    }

    /** @param array<string, mixed> $row */
    private function hydrateCitoyen(array $row): Citoyen
    {
        $c = new Citoyen(
            nom:                (string) $row['nom'],
            email:              (string) $row['email'],
            motDePasse:         (string) $row['mot_de_passe'],
            telephone:          (string) $row['telephone'],
            pieceIdentiteRecto: $row['piece_identite_recto'] ?? null,
            pieceIdentiteVerso: $row['piece_identite_verso'] ?? null,
        );
        // Le constructeur a hashé le mot de passe via password_hash si on l'avait passé
        // en clair ; ici on remplace par le hash déjà stocké.
        $c->setMotDePasseHash((string) $row['mot_de_passe']);

        if (!empty($row['date_inscription'])) {
            $c->setDateInscription(new DateTimeImmutable((string) $row['date_inscription']));
        }
        $c->setVerifie((bool) ($row['verifie'] ?? false));
        return $c;
    }

    /** @param array<string, mixed> $row */
    private function hydrateAgent(array $row): Agent
    {
        $categorie = null;
        if ($this->categorieRepo !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT categorie_id FROM agent_categorie WHERE agent_id = :id LIMIT 1'
            );
            $stmt->execute(['id' => (int) $row['id']]);
            $catId = $stmt->fetchColumn();
            if ($catId !== false) {
                $categorie = $this->categorieRepo->find((int) $catId);
            }
        }

        $a = new Agent(
            nom:        (string) $row['nom'],
            email:      (string) $row['email'],
            motDePasse: (string) $row['mot_de_passe'],
            telephone:  (string) $row['telephone'],
            service:    (string) ($row['service'] ?? ''),
            categorie:  $categorie,
        );
        $a->setMotDePasseHash((string) $row['mot_de_passe']);
        return $a;
    }

    /** @param array<string, mixed> $row */
    private function hydrateAdmin(array $row): Administrateur
    {
        $admin = new Administrateur(
            nom:         (string) $row['nom'],
            email:       (string) $row['email'],
            motDePasse:  (string) $row['mot_de_passe'],
            telephone:   (string) $row['telephone'],
            niveauAcces: (int) ($row['niveau_acces'] ?? 1),
        );
        $admin->setMotDePasseHash((string) $row['mot_de_passe']);
        return $admin;
    }
}
