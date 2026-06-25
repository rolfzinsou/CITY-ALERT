<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Contrat commun à tous les repositories.
 *
 * @template T of object
 *
 * Le type générique T est documenté via PHPDoc — PHP n'a pas encore de generics
 * natifs, mais les IDE (PHPStan, Psalm, PhpStorm) reconnaissent cette annotation.
 *
 * Exigence POO du sujet : RepositoryInterface est l'une des 2 interfaces imposées.
 */
interface RepositoryInterface
{
    /**
     * Recherche par identifiant primaire.
     *
     * @return T|null
     */
    public function find(int $id): ?object;

    /**
     * Récupère toutes les entités.
     *
     * @return T[]
     */
    public function findAll(): array;

    /**
     * Persiste une entité (INSERT si nouvelle, UPDATE sinon).
     *
     * @param T $entity
     */
    public function save(object $entity): void;

    /**
     * Supprime une entité par son identifiant.
     */
    public function delete(int $id): bool;
}
