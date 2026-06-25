-- =============================================================
--  CityAlert — Schéma de base de données (v2)
--  Stratégies :
--   - Single Table Inheritance pour Utilisateur (table unique)
--   - Single Table avec discriminateur pour Categorie
--   - Table d'association `agent_categorie` (UNIQUE sur agent_id)
--   - Table 1-N `photos` pour photos multiples par signalement
--   - `commentaires.parent_id` pour les réponses récursives
--   - Tables d'association `soutiens` et `likes_commentaires`
-- =============================================================

DROP DATABASE IF EXISTS cityalert;
CREATE DATABASE cityalert
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE cityalert;

-- ---------- utilisateurs (STI) ----------
CREATE TABLE utilisateurs (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    nom                    VARCHAR(100)  NOT NULL,
    email                  VARCHAR(150)  NOT NULL UNIQUE,
    telephone              VARCHAR(20)   NOT NULL UNIQUE COMMENT 'Format E.164, ex. +221771234567',
    mot_de_passe           VARCHAR(255)  NOT NULL,
    role                   ENUM('citoyen', 'agent', 'administrateur') NOT NULL,
    notifications_actives  TINYINT(1)    NOT NULL DEFAULT 1,

    -- Spécifiques citoyen
    date_inscription       DATETIME      NULL,
    piece_identite_recto   VARCHAR(255)  NULL,
    piece_identite_verso   VARCHAR(255)  NULL,
    verifie                TINYINT(1)    NULL DEFAULT 0,

    -- Spécifiques agent
    service                VARCHAR(100)  NULL,

    -- Spécifiques admin
    niveau_acces           TINYINT       NULL,

    -- Trait Timestampable
    created_at             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_role      (role),
    INDEX idx_email     (email),
    INDEX idx_telephone (telephone)
) ENGINE=InnoDB;

-- ---------- categories ----------
CREATE TABLE categories (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    nom                    VARCHAR(100) NOT NULL,
    type_class             ENUM('voirie', 'eclairage', 'dechets', 'eau_assainissement') NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------- agent_categorie (rattachement agent à 1 catégorie) ----------
CREATE TABLE agent_categorie (
    agent_id      INT NOT NULL,
    categorie_id  INT NOT NULL,
    PRIMARY KEY (agent_id),
    CONSTRAINT fk_ac_agent     FOREIGN KEY (agent_id)     REFERENCES utilisateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_ac_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id)   ON DELETE RESTRICT,
    INDEX idx_categorie (categorie_id)
) ENGINE=InnoDB;

-- ---------- signalements ----------
CREATE TABLE signalements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    titre           VARCHAR(200)   NOT NULL,
    description     TEXT           NOT NULL,
    adresse         VARCHAR(255)   NOT NULL,
    latitude        DECIMAL(10, 7) NULL,
    longitude       DECIMAL(10, 7) NULL,
    statut          ENUM('nouveau', 'en_cours', 'resolu', 'rejete') NOT NULL DEFAULT 'nouveau',
    date_creation   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    auteur_id       INT            NOT NULL,
    categorie_id    INT            NOT NULL,

    created_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_signalement_auteur    FOREIGN KEY (auteur_id)    REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    CONSTRAINT fk_signalement_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id)   ON DELETE RESTRICT,

    INDEX idx_statut    (statut),
    INDEX idx_categorie (categorie_id),
    INDEX idx_auteur    (auteur_id),
    INDEX idx_date      (date_creation),
    FULLTEXT INDEX idx_recherche (titre, description, adresse)
) ENGINE=InnoDB;

-- ---------- photos (1-N avec signalements) ----------
CREATE TABLE photos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    chemin          VARCHAR(255) NOT NULL,
    signalement_id  INT          NOT NULL,
    ordre           INT          NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_photo_signalement FOREIGN KEY (signalement_id) REFERENCES signalements(id) ON DELETE CASCADE,
    INDEX idx_signalement (signalement_id, ordre)
) ENGINE=InnoDB;

-- ---------- commentaires (récursif) ----------
CREATE TABLE commentaires (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    contenu            TEXT     NOT NULL,
    date_commentaire   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nombre_likes       INT      NOT NULL DEFAULT 0,

    auteur_id          INT      NOT NULL,
    signalement_id     INT      NOT NULL,
    parent_id          INT      NULL COMMENT 'NULL si commentaire racine, sinon ID du commentaire parent',

    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_commentaire_auteur      FOREIGN KEY (auteur_id)      REFERENCES utilisateurs(id)  ON DELETE CASCADE,
    CONSTRAINT fk_commentaire_signalement FOREIGN KEY (signalement_id) REFERENCES signalements(id)  ON DELETE CASCADE,
    CONSTRAINT fk_commentaire_parent      FOREIGN KEY (parent_id)      REFERENCES commentaires(id)  ON DELETE CASCADE,

    INDEX idx_signalement (signalement_id),
    INDEX idx_auteur      (auteur_id),
    INDEX idx_parent      (parent_id)
) ENGINE=InnoDB;

-- ---------- historiques_statut ----------
CREATE TABLE historiques_statut (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    ancien_statut       ENUM('nouveau', 'en_cours', 'resolu', 'rejete') NOT NULL,
    nouveau_statut      ENUM('nouveau', 'en_cours', 'resolu', 'rejete') NOT NULL,
    remarque            TEXT     NULL,
    date_modification   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    signalement_id      INT      NOT NULL,
    agent_id            INT      NOT NULL,

    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_historique_signalement FOREIGN KEY (signalement_id) REFERENCES signalements(id) ON DELETE CASCADE,
    CONSTRAINT fk_historique_agent       FOREIGN KEY (agent_id)       REFERENCES utilisateurs(id) ON DELETE RESTRICT,

    INDEX idx_signalement (signalement_id),
    INDEX idx_agent       (agent_id)
) ENGINE=InnoDB;

-- ---------- soutiens (table d'association) ----------
CREATE TABLE soutiens (
    signalement_id   INT      NOT NULL,
    utilisateur_id   INT      NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (signalement_id, utilisateur_id),
    CONSTRAINT fk_soutien_signalement FOREIGN KEY (signalement_id) REFERENCES signalements(id) ON DELETE CASCADE,
    CONSTRAINT fk_soutien_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- likes_commentaires (table d'association) ----------
CREATE TABLE likes_commentaires (
    commentaire_id   INT      NOT NULL,
    utilisateur_id   INT      NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (commentaire_id, utilisateur_id),
    CONSTRAINT fk_like_commentaire FOREIGN KEY (commentaire_id) REFERENCES commentaires(id) ON DELETE CASCADE,
    CONSTRAINT fk_like_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- tokens "Se souvenir de moi" ----------
CREATE TABLE remember_tokens (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT          NOT NULL,
    selecteur       VARCHAR(64)  NOT NULL UNIQUE,
    validateur_hash VARCHAR(255) NOT NULL,
    expire_at       DATETIME     NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_remember_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_expire (expire_at)
) ENGINE=InnoDB;

-- =============================================================
--  Peuplement des catégories (4 fixes, requis par le projet)
-- =============================================================
INSERT INTO categories (nom, type_class) VALUES
  ('Voirie',                'voirie'),
  ('Éclairage',             'eclairage'),
  ('Déchets',               'dechets'),
  ('Eau et assainissement', 'eau_assainissement');

-- =============================================================
--  Peuplement minimal pour tests
--  Mot de passe pour tous : "password" (à régénérer avec password_hash)
--  Hash bcrypt valide : à remplacer par votre propre hash
-- =============================================================

-- Administrateur (1)
INSERT INTO utilisateurs (nom, email, telephone, mot_de_passe, role, niveau_acces) VALUES
  ('Awa Sarr', 'admin@cityalert.sn', '+221770000001',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'administrateur', 3);

-- Agents (4)
INSERT INTO utilisateurs (nom, email, telephone, mot_de_passe, role, service) VALUES
  ('Mamadou Diop',  'm.diop@dakar.sn',   '+221771111111',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 'Voirie'),
  ('Fatou Ndiaye',  'f.ndiaye@dakar.sn', '+221771111112',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 'Éclairage public'),
  ('Ibrahima Fall', 'i.fall@dakar.sn',   '+221771111113',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 'Gestion des déchets'),
  ('Aïssatou Bâ',   'a.ba@dakar.sn',     '+221771111114',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 'Eau et assainissement');

-- Affectations agents → catégories
INSERT INTO agent_categorie (agent_id, categorie_id) VALUES
  (2, 1), -- Mamadou Diop  → Voirie
  (3, 2), -- Fatou Ndiaye  → Éclairage
  (4, 3), -- Ibrahima Fall → Déchets
  (5, 4); -- Aïssatou Bâ   → Eau

-- Citoyens (3, avec téléphone)
INSERT INTO utilisateurs (nom, email, telephone, mot_de_passe, role, date_inscription, verifie) VALUES
  ('Oumar Kane',     'oumar.kane@mail.sn',     '+221772222221',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citoyen', '2026-05-12 10:30:00', 1),
  ('Aminata Sow',    'aminata.sow@mail.sn',    '+221772222222',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citoyen', '2026-05-18 14:20:00', 1),
  ('Mariama Diallo', 'mariama.diallo@mail.sn', '+221772222223',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citoyen', '2026-06-08 16:45:00', 0);

-- Quelques signalements de démonstration
INSERT INTO signalements (titre, description, adresse, latitude, longitude, statut, date_creation, auteur_id, categorie_id) VALUES
  ('Nid-de-poule dangereux sur la VDN',
   'Trou profond sur la voie rapide. Plusieurs accidents évités cette semaine.',
   'VDN, sortie Mermoz, Dakar', 14.7167, -17.4677, 'en_cours', '2026-06-18 08:42:00', 6, 1),
  ('Lampadaire éteint depuis 3 semaines',
   'Quartier dangereux la nuit pour les piétons.',
   'Angle rues 10 et 25, Médina, Dakar', 14.6928, -17.4467, 'nouveau', '2026-06-19 19:15:00', 7, 2),
  ('Fuite d''eau majeure',
   'Canalisation principale fuit depuis hier matin. La chaussée s''affaisse.',
   'Rue Carnot, Dakar Plateau', 14.6701, -17.4348, 'resolu', '2026-06-15 07:30:00', 8, 4);

-- Quelques commentaires et historiques pour valider l'arborescence
INSERT INTO commentaires (contenu, auteur_id, signalement_id, parent_id) VALUES
  ('J''ai failli avoir un accident hier soir au même endroit.', 7, 1, NULL),
  ('Votre signalement a été pris en compte.',                    2, 1, NULL),
  ('Merci pour la réponse rapide !',                              6, 1, 2);  -- réponse au #2

INSERT INTO historiques_statut (ancien_statut, nouveau_statut, remarque, signalement_id, agent_id, date_modification) VALUES
  ('nouveau', 'en_cours', 'Équipe technique mobilisée', 1, 2, '2026-06-18 16:45:00'),
  ('nouveau', 'en_cours', 'Équipe SDE prévenue',         3, 5, '2026-06-15 09:00:00'),
  ('en_cours', 'resolu',  'Canalisation réparée',        3, 5, '2026-06-15 16:00:00');

-- Quelques soutiens
INSERT INTO soutiens (signalement_id, utilisateur_id) VALUES
  (1, 7), (1, 8), (2, 6), (3, 6), (3, 7);

-- Like sur un commentaire
INSERT INTO likes_commentaires (commentaire_id, utilisateur_id) VALUES
  (2, 6), (2, 7);
UPDATE commentaires SET nombre_likes = (SELECT COUNT(*) FROM likes_commentaires WHERE commentaire_id = commentaires.id);

-- =============================================================
-- IMPORTANT : Remplacer les hashs de mot de passe par de vrais hashs
-- générés via PHP : password_hash('motdepasse', PASSWORD_DEFAULT)
-- =============================================================
