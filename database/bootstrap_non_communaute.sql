-- Bootstrap SQL for non-community modules (formation, RH, recrutement, evenement)
-- Safe to run multiple times on MySQL 8.x

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1) Core lookup tables (non-community)
-- =====================================================================
CREATE TABLE IF NOT EXISTS type_action (
    code_type_action INT AUTO_INCREMENT PRIMARY KEY,
    description_action VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS type_background_etude (
    code_type_background_etude INT AUTO_INCREMENT PRIMARY KEY,
    description_type_background_etude VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS type_competence (
    code_type_competence INT AUTO_INCREMENT PRIMARY KEY,
    description_competence VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS type_contrat (
    code_type_contrat INT AUTO_INCREMENT PRIMARY KEY,
    description_contrat VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS type_langue (
    code_type_langue INT AUTO_INCREMENT PRIMARY KEY,
    description_langue VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS type_niveau_etude (
    code_type_niveau_etude INT AUTO_INCREMENT PRIMARY KEY,
    description_type_etude VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS type_status_condidature (
    code_type_status_condidature INT AUTO_INCREMENT PRIMARY KEY,
    description_status_condidature VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 2) Main feature tables
-- =====================================================================
CREATE TABLE IF NOT EXISTS formation (
    id_formation INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    num_ordre_creation INT NOT NULL,
    id_entreprise INT NOT NULL,
    image VARCHAR(255) NULL,
    mode VARCHAR(255) NULL,
    nombre_places INT NULL,
    places_restantes INT NULL,
    date_debut INT NULL,
    date_fin INT NULL,
    niveau VARCHAR(255) NULL,
    KEY idx_formation_entreprise (id_entreprise),
    CONSTRAINT fk_formation_entreprise FOREIGN KEY (id_entreprise) REFERENCES entreprise (ID_Entreprise)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS participation_formation (
    id_formation INT NOT NULL,
    id_participant INT NOT NULL,
    num_ordre_participation INT NOT NULL,
    statut VARCHAR(255) NULL,
    certificat VARCHAR(255) NULL,
    PRIMARY KEY (id_formation, id_participant, num_ordre_participation),
    KEY idx_pf_participant (id_participant),
    CONSTRAINT fk_pf_formation FOREIGN KEY (id_formation) REFERENCES formation (id_formation) ON DELETE CASCADE,
    CONSTRAINT fk_pf_participant FOREIGN KEY (id_participant) REFERENCES utilisateur (ID_UTILISATEUR) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS certification (
    id_certif INT PRIMARY KEY,
    id_formation INT NOT NULL,
    id_participant INT NOT NULL,
    description_certif VARCHAR(255) NULL,
    fichier_pdf LONGBLOB NULL,
    KEY idx_certif_formation (id_formation),
    KEY idx_certif_participant (id_participant),
    CONSTRAINT fk_certif_formation FOREIGN KEY (id_formation) REFERENCES formation (id_formation) ON DELETE CASCADE,
    CONSTRAINT fk_certif_participant FOREIGN KEY (id_participant) REFERENCES utilisateur (ID_UTILISATEUR) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS activite (
    id_activite INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    id_evenement INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS evenement (
    id_evenement INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    num_ordre_creation INT NULL,
    num_ordre_debut_evenement INT NULL,
    num_ordre_fin_evenement INT NULL,
    localisation VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    est_payant TINYINT(1) NULL,
    prix DECIMAL(10,2) NULL,
    nb_max INT NULL,
    KEY idx_evt_ordre_creation (num_ordre_creation),
    KEY idx_evt_ordre_debut (num_ordre_debut_evenement),
    KEY idx_evt_ordre_fin (num_ordre_fin_evenement),
    CONSTRAINT fk_evt_ordre_creation FOREIGN KEY (num_ordre_creation) REFERENCES ordre (Num_Ordre),
    CONSTRAINT fk_evt_ordre_debut FOREIGN KEY (num_ordre_debut_evenement) REFERENCES ordre (Num_Ordre),
    CONSTRAINT fk_evt_ordre_fin FOREIGN KEY (num_ordre_fin_evenement) REFERENCES ordre (Num_Ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS liste_attente (
    id_attente INT AUTO_INCREMENT PRIMARY KEY,
    id_evenement INT NULL,
    id_activite INT NULL,
    nom_complet VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    date_demande DATETIME NOT NULL,
    KEY idx_liste_attente_evt (id_evenement),
    CONSTRAINT fk_liste_attente_evt FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS participation_evenement (
    id_evenement INT NOT NULL,
    id_activite INT NOT NULL,
    id_participant INT NOT NULL,
    num_ordre_participation INT NULL,
    nom_complet VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    description LONGTEXT NULL,
    mode_paiement VARCHAR(255) NULL,
    PRIMARY KEY (id_evenement, id_activite, id_participant),
    KEY idx_pe_ordre (num_ordre_participation),
    CONSTRAINT fk_pe_evt FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement) ON DELETE CASCADE,
    CONSTRAINT fk_pe_activite FOREIGN KEY (id_activite) REFERENCES activite (id_activite) ON DELETE CASCADE,
    CONSTRAINT fk_pe_participant FOREIGN KEY (id_participant) REFERENCES utilisateur (ID_UTILISATEUR) ON DELETE CASCADE,
    CONSTRAINT fk_pe_ordre FOREIGN KEY (num_ordre_participation) REFERENCES ordre (Num_Ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS outils_de_travail (
    id_outil INT AUTO_INCREMENT PRIMARY KEY,
    identifiant_universelle VARCHAR(255) NOT NULL,
    hash_app VARCHAR(255) NOT NULL,
    nom_outil VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS employee (
    id_employe INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NULL,
    solde_conge INT NOT NULL,
    nbr_heure_de_travail INT NOT NULL,
    mac_machine VARCHAR(255) NULL,
    salaire INT NULL,
    KEY idx_employee_user (id_utilisateur),
    CONSTRAINT fk_employee_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (ID_UTILISATEUR) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS demande_conge (
    id_demende INT AUTO_INCREMENT PRIMARY KEY,
    id_employe INT NULL,
    nbr_jour_demande INT NOT NULL,
    num_ordre_debut_conge INT NULL,
    num_ordre_fin_conge INT NULL,
    status INT NOT NULL,
    KEY idx_dc_employee (id_employe),
    KEY idx_dc_ordre_debut (num_ordre_debut_conge),
    KEY idx_dc_ordre_fin (num_ordre_fin_conge),
    CONSTRAINT fk_dc_employee FOREIGN KEY (id_employe) REFERENCES employee (id_employe) ON DELETE SET NULL,
    CONSTRAINT fk_dc_ordre_debut FOREIGN KEY (num_ordre_debut_conge) REFERENCES ordre (Num_Ordre),
    CONSTRAINT fk_dc_ordre_fin FOREIGN KEY (num_ordre_fin_conge) REFERENCES ordre (Num_Ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS cours (
    id_cours INT AUTO_INCREMENT PRIMARY KEY,
    code_type_background_etude INT NULL,
    contenu LONGTEXT NOT NULL,
    KEY idx_cours_bg (code_type_background_etude),
    CONSTRAINT fk_cours_bg FOREIGN KEY (code_type_background_etude) REFERENCES type_background_etude (code_type_background_etude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS offre (
    id_offre INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    id_entreprise INT NULL,
    work_type VARCHAR(255) NULL,
    code_type_contrat INT NULL,
    nbr_annee_experience INT NULL,
    code_type_niveau_etude INT NULL,
    min_salaire INT NULL,
    max_salaire INT NULL,
    num_ordre_creation INT NULL,
    num_ordre_expiration INT NULL,
    KEY idx_offre_entreprise (id_entreprise),
    KEY idx_offre_contrat (code_type_contrat),
    KEY idx_offre_niveau (code_type_niveau_etude),
    KEY idx_offre_ordre_creation (num_ordre_creation),
    KEY idx_offre_ordre_expiration (num_ordre_expiration),
    CONSTRAINT fk_offre_entreprise FOREIGN KEY (id_entreprise) REFERENCES entreprise (ID_Entreprise),
    CONSTRAINT fk_offre_contrat FOREIGN KEY (code_type_contrat) REFERENCES type_contrat (code_type_contrat),
    CONSTRAINT fk_offre_niveau FOREIGN KEY (code_type_niveau_etude) REFERENCES type_niveau_etude (code_type_niveau_etude),
    CONSTRAINT fk_offre_ordre_creation FOREIGN KEY (num_ordre_creation) REFERENCES ordre (Num_Ordre),
    CONSTRAINT fk_offre_ordre_expiration FOREIGN KEY (num_ordre_expiration) REFERENCES ordre (Num_Ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS condidat (
    id_condidat INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NULL,
    cv LONGTEXT NULL,
    KEY idx_condidat_user (id_utilisateur),
    CONSTRAINT fk_condidat_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (ID_UTILISATEUR) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS condidature (
    id_condidature INT AUTO_INCREMENT PRIMARY KEY,
    id_condidat INT NULL,
    lettre_motivation LONGTEXT NULL,
    portfolio LONGTEXT NULL,
    lettre_recomendation LONGTEXT NULL,
    code_type_status INT NULL,
    id_offre INT NULL,
    KEY idx_condidature_condidat (id_condidat),
    KEY idx_condidature_status (code_type_status),
    KEY idx_condidature_offre (id_offre),
    CONSTRAINT fk_condidature_condidat FOREIGN KEY (id_condidat) REFERENCES condidat (id_condidat) ON DELETE SET NULL,
    CONSTRAINT fk_condidature_status FOREIGN KEY (code_type_status) REFERENCES type_status_condidature (code_type_status_condidature),
    CONSTRAINT fk_condidature_offre FOREIGN KEY (id_offre) REFERENCES offre (id_offre) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS entretien (
    id_condidat INT NOT NULL,
    id_rh INT NOT NULL,
    num_ordre_entretien INT NOT NULL,
    localisation VARCHAR(255) NULL,
    status_entretien INT NULL,
    evaluation LONGTEXT NULL,
    PRIMARY KEY (id_condidat, id_rh, num_ordre_entretien),
    KEY idx_entretien_rh (id_rh),
    KEY idx_entretien_ordre (num_ordre_entretien),
    CONSTRAINT fk_entretien_condidat FOREIGN KEY (id_condidat) REFERENCES condidat (id_condidat) ON DELETE CASCADE,
    CONSTRAINT fk_entretien_rh FOREIGN KEY (id_rh) REFERENCES utilisateur (ID_UTILISATEUR),
    CONSTRAINT fk_entretien_ordre FOREIGN KEY (num_ordre_entretien) REFERENCES ordre (Num_Ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 3) Join tables used by non-community entities
-- =====================================================================
CREATE TABLE IF NOT EXISTS detail_evenement (
    id_activite INT NOT NULL,
    id_evenement INT NOT NULL,
    num_ordre_debut_activite INT NULL,
    PRIMARY KEY (id_activite, id_evenement),
    KEY idx_de_evt (id_evenement),
    KEY idx_de_ordre (num_ordre_debut_activite),
    CONSTRAINT fk_de_activite FOREIGN KEY (id_activite) REFERENCES activite (id_activite) ON DELETE CASCADE,
    CONSTRAINT fk_de_evt FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement) ON DELETE CASCADE,
    CONSTRAINT fk_de_ordre FOREIGN KEY (num_ordre_debut_activite) REFERENCES ordre (Num_Ordre) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS detail_offre_background (
    id_offre INT NOT NULL,
    code_type_background_etude INT NOT NULL,
    PRIMARY KEY (id_offre, code_type_background_etude),
    CONSTRAINT fk_dob_offre FOREIGN KEY (id_offre) REFERENCES offre (id_offre) ON DELETE CASCADE,
    CONSTRAINT fk_dob_background FOREIGN KEY (code_type_background_etude) REFERENCES type_background_etude (code_type_background_etude) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS detail_offre_competence (
    id_offre INT NOT NULL,
    code_type_competence INT NOT NULL,
    PRIMARY KEY (id_offre, code_type_competence),
    CONSTRAINT fk_doc_offre FOREIGN KEY (id_offre) REFERENCES offre (id_offre) ON DELETE CASCADE,
    CONSTRAINT fk_doc_competence FOREIGN KEY (code_type_competence) REFERENCES type_competence (code_type_competence) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS detail_offre_langue (
    id_offre INT NOT NULL,
    code_type_langue INT NOT NULL,
    PRIMARY KEY (id_offre, code_type_langue),
    CONSTRAINT fk_dol_offre FOREIGN KEY (id_offre) REFERENCES offre (id_offre) ON DELETE CASCADE,
    CONSTRAINT fk_dol_langue FOREIGN KEY (code_type_langue) REFERENCES type_langue (code_type_langue) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS outil_employee (
    id_employee INT NOT NULL,
    id_outil INT NOT NULL,
    PRIMARY KEY (id_employee, id_outil),
    CONSTRAINT fk_oe_employee FOREIGN KEY (id_employee) REFERENCES employee (id_employe) ON DELETE CASCADE,
    CONSTRAINT fk_oe_outil FOREIGN KEY (id_outil) REFERENCES outils_de_travail (id_outil) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS action_utilisateur (
    id_utilisateur INT NOT NULL,
    code_type_action INT NOT NULL,
    num_ordre INT NOT NULL,
    PRIMARY KEY (id_utilisateur, code_type_action, num_ordre),
    KEY idx_au_type_action (code_type_action),
    KEY idx_au_ordre (num_ordre),
    CONSTRAINT fk_au_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (ID_UTILISATEUR) ON DELETE CASCADE,
    CONSTRAINT fk_au_type_action FOREIGN KEY (code_type_action) REFERENCES type_action (code_type_action) ON DELETE CASCADE,
    CONSTRAINT fk_au_ordre FOREIGN KEY (num_ordre) REFERENCES ordre (Num_Ordre) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 4) Seed data (idempotent)
-- =====================================================================
INSERT INTO profil (ID_Profil, Nom_Profil)
VALUES
    (1, 'Admin'),
    (2, 'RH'),
    (3, 'Employe'),
    (4, 'Candidat')
ON DUPLICATE KEY UPDATE Nom_Profil = VALUES(Nom_Profil);

INSERT INTO type_action (code_type_action, description_action)
VALUES
    (1, 'Creation'),
    (2, 'Modification'),
    (3, 'Suppression')
ON DUPLICATE KEY UPDATE description_action = VALUES(description_action);

INSERT INTO type_background_etude (code_type_background_etude, description_type_background_etude)
VALUES
    (1, 'Informatique'),
    (2, 'Management'),
    (3, 'Finance')
ON DUPLICATE KEY UPDATE description_type_background_etude = VALUES(description_type_background_etude);

INSERT INTO type_competence (code_type_competence, description_competence)
VALUES
    (1, 'PHP'),
    (2, 'Symfony'),
    (3, 'SQL'),
    (4, 'Communication')
ON DUPLICATE KEY UPDATE description_competence = VALUES(description_competence);

INSERT INTO type_contrat (code_type_contrat, description_contrat)
VALUES
    (1, 'CDI'),
    (2, 'CDD'),
    (3, 'Stage')
ON DUPLICATE KEY UPDATE description_contrat = VALUES(description_contrat);

INSERT INTO type_langue (code_type_langue, description_langue)
VALUES
    (1, 'Francais'),
    (2, 'Anglais')
ON DUPLICATE KEY UPDATE description_langue = VALUES(description_langue);

INSERT INTO type_niveau_etude (code_type_niveau_etude, description_type_etude)
VALUES
    (1, 'Bac'),
    (2, 'Licence'),
    (3, 'Master'),
    (4, 'Doctorat')
ON DUPLICATE KEY UPDATE description_type_etude = VALUES(description_type_etude);

INSERT INTO type_status_condidature (code_type_status_condidature, description_status_condidature)
VALUES
    (1, 'En attente'),
    (2, 'Acceptee'),
    (3, 'Refusee')
ON DUPLICATE KEY UPDATE description_status_condidature = VALUES(description_status_condidature);

INSERT INTO ordre (Num_Ordre, AAAA, MM, JJ, HH, MN, SS)
VALUES
    (1001, 2026, 4, 1, 9, 0, 0),
    (1002, 2026, 4, 15, 18, 0, 0),
    (1003, 2026, 5, 1, 9, 0, 0),
    (1004, 2026, 5, 31, 18, 0, 0),
    (1005, 2026, 6, 10, 10, 0, 0)
ON DUPLICATE KEY UPDATE
    AAAA = VALUES(AAAA),
    MM = VALUES(MM),
    JJ = VALUES(JJ),
    HH = VALUES(HH),
    MN = VALUES(MN),
    SS = VALUES(SS);

INSERT INTO entreprise (ID_Entreprise, Nom_Entreprise, Reference)
VALUES
    (1, 'HR One', 'HRONE-REF-001')
ON DUPLICATE KEY UPDATE
    Nom_Entreprise = VALUES(Nom_Entreprise),
    Reference = VALUES(Reference);

-- Ensure both legacy/new flags exist in utilisateur for auth compatibility
ALTER TABLE utilisateur ADD COLUMN IF NOT EXISTS first_login INT NULL;
ALTER TABLE utilisateur ADD COLUMN IF NOT EXISTS firstLogin INT NULL;
ALTER TABLE utilisateur ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;

-- Utilisateur business table
INSERT INTO utilisateur (
    ID_UTILISATEUR, ID_Entreprise, ID_Profil, Nom_Utilisateur, Mot_Passe, Email,
    Adresse, Num_Tel, CIN, Num_Ordre_Sign_In, Date_Naissance, Gender, first_login, firstLogin, is_active
)
VALUES
    (1, 1, 1, 'admin', '$2y$10$uXamuUd42uS4Ci78JTM4o.PM8P6sWOVyvifLOgppPKiNv4K6lPnGS', 'admin@hrone.local', 'Siege', '00000000', 'CIN0001', 1001, '1990-01-01', 'F', 0, 0, 1),
    (2, 1, 2, 'rh_manager', '$2y$10$uXamuUd42uS4Ci78JTM4o.PM8P6sWOVyvifLOgppPKiNv4K6lPnGS', 'rh@hrone.local', 'Siege', '11111111', 'CIN0002', 1002, '1992-02-02', 'M', 0, 0, 1),
    (3, 1, 3, 'employee_demo', '$2y$10$uXamuUd42uS4Ci78JTM4o.PM8P6sWOVyvifLOgppPKiNv4K6lPnGS', 'employee@hrone.local', 'Agence 1', '22222222', 'CIN0003', 1003, '1995-03-03', 'F', 0, 0, 1),
    (4, 1, 4, 'candidate_demo', '$2y$10$uXamuUd42uS4Ci78JTM4o.PM8P6sWOVyvifLOgppPKiNv4K6lPnGS', 'candidate@hrone.local', 'Agence 2', '33333333', 'CIN0004', 1004, '1998-04-04', 'M', 0, 0, 1)
ON DUPLICATE KEY UPDATE
    ID_Entreprise = VALUES(ID_Entreprise),
    ID_Profil = VALUES(ID_Profil),
    Nom_Utilisateur = VALUES(Nom_Utilisateur),
    Mot_Passe = VALUES(Mot_Passe),
    Email = VALUES(Email),
    Adresse = VALUES(Adresse),
    Num_Tel = VALUES(Num_Tel),
    CIN = VALUES(CIN),
    Num_Ordre_Sign_In = VALUES(Num_Ordre_Sign_In),
    Date_Naissance = VALUES(Date_Naissance),
    Gender = VALUES(Gender),
    first_login = VALUES(first_login),
    firstLogin = VALUES(firstLogin),
    is_active = VALUES(is_active);

INSERT INTO employee (id_employe, id_utilisateur, solde_conge, nbr_heure_de_travail, mac_machine, salaire)
VALUES
    (1, 3, 24, 40, '00:11:22:33:44:55', 2800)
ON DUPLICATE KEY UPDATE
    id_utilisateur = VALUES(id_utilisateur),
    solde_conge = VALUES(solde_conge),
    nbr_heure_de_travail = VALUES(nbr_heure_de_travail),
    mac_machine = VALUES(mac_machine),
    salaire = VALUES(salaire);

INSERT INTO outils_de_travail (id_outil, identifiant_universelle, hash_app, nom_outil)
VALUES
    (1, 'TOOL-001', 'HASH-001', 'Laptop'),
    (2, 'TOOL-002', 'HASH-002', 'VPN Token')
ON DUPLICATE KEY UPDATE
    identifiant_universelle = VALUES(identifiant_universelle),
    hash_app = VALUES(hash_app),
    nom_outil = VALUES(nom_outil);

INSERT INTO outil_employee (id_employee, id_outil)
VALUES
    (1, 1),
    (1, 2)
ON DUPLICATE KEY UPDATE
    id_employee = VALUES(id_employee),
    id_outil = VALUES(id_outil);

INSERT INTO formation (
    id_formation, titre, description, num_ordre_creation, id_entreprise, image,
    mode, nombre_places, places_restantes, date_debut, date_fin, niveau
)
VALUES
    (1, 'Symfony Avance', 'Perfectionnement Symfony pour RH et back office', 1001, 1, '/uploads/formations/symfony.png', 'en_ligne', 20, 18, 20260510, 20260520, 'Intermediaire'),
    (2, 'Communication RH', 'Techniques de communication professionnelle RH', 1002, 1, '/uploads/formations/rh.png', 'presentiel', 15, 10, 20260601, 20260605, 'Debutant')
ON DUPLICATE KEY UPDATE
    titre = VALUES(titre),
    description = VALUES(description),
    num_ordre_creation = VALUES(num_ordre_creation),
    id_entreprise = VALUES(id_entreprise),
    image = VALUES(image),
    mode = VALUES(mode),
    nombre_places = VALUES(nombre_places),
    places_restantes = VALUES(places_restantes),
    date_debut = VALUES(date_debut),
    date_fin = VALUES(date_fin),
    niveau = VALUES(niveau);

INSERT INTO participation_formation (id_formation, id_participant, num_ordre_participation, statut, certificat)
VALUES
    (1, 3, 2001, 'inscrit', NULL)
ON DUPLICATE KEY UPDATE
    statut = VALUES(statut),
    certificat = VALUES(certificat);

INSERT INTO certification (id_certif, id_formation, id_participant, description_certif, fichier_pdf)
VALUES
    (1, 1, 3, 'Certificat genere pour employee_demo', NULL)
ON DUPLICATE KEY UPDATE
    description_certif = VALUES(description_certif),
    fichier_pdf = VALUES(fichier_pdf);

INSERT INTO activite (id_activite, titre, description, id_evenement)
VALUES
    (1, 'Atelier CV', 'Optimisation de CV et profil pro', NULL),
    (2, 'Session Networking', 'Rencontre avec recruteurs', NULL)
ON DUPLICATE KEY UPDATE
    titre = VALUES(titre),
    description = VALUES(description),
    id_evenement = VALUES(id_evenement);

INSERT INTO evenement (
    id_evenement, titre, description, num_ordre_creation, num_ordre_debut_evenement, num_ordre_fin_evenement,
    localisation, image, est_payant, prix, nb_max
)
VALUES
    (1, 'Forum RH 2026', 'Evenement annuel RH', 1001, 1003, 1004, 'Tunis', '/uploads/evenements/forum-rh.png', 1, 30.00, 200)
ON DUPLICATE KEY UPDATE
    titre = VALUES(titre),
    description = VALUES(description),
    num_ordre_creation = VALUES(num_ordre_creation),
    num_ordre_debut_evenement = VALUES(num_ordre_debut_evenement),
    num_ordre_fin_evenement = VALUES(num_ordre_fin_evenement),
    localisation = VALUES(localisation),
    image = VALUES(image),
    est_payant = VALUES(est_payant),
    prix = VALUES(prix),
    nb_max = VALUES(nb_max);

INSERT INTO detail_evenement (id_activite, id_evenement, num_ordre_debut_activite)
VALUES
    (1, 1, 1003),
    (2, 1, 1003)
ON DUPLICATE KEY UPDATE
    id_evenement = VALUES(id_evenement),
    num_ordre_debut_activite = VALUES(num_ordre_debut_activite);

UPDATE activite SET id_evenement = 1 WHERE id_activite IN (1, 2);

INSERT INTO participation_evenement (
    id_evenement, id_activite, id_participant, num_ordre_participation, nom_complet, email, description, mode_paiement
)
VALUES
    (1, 1, 3, 1005, 'Employee Demo', 'employee@hrone.local', 'Participation standard', 'carte')
ON DUPLICATE KEY UPDATE
    nom_complet = VALUES(nom_complet),
    email = VALUES(email),
    description = VALUES(description),
    mode_paiement = VALUES(mode_paiement),
    num_ordre_participation = VALUES(num_ordre_participation);

INSERT INTO liste_attente (id_attente, id_evenement, id_activite, nom_complet, email, date_demande)
VALUES
    (1, 1, 2, 'Candidate Demo', 'candidate@hrone.local', NOW())
ON DUPLICATE KEY UPDATE
    id_evenement = VALUES(id_evenement),
    id_activite = VALUES(id_activite),
    nom_complet = VALUES(nom_complet),
    email = VALUES(email),
    date_demande = VALUES(date_demande);

INSERT INTO cours (id_cours, code_type_background_etude, contenu)
VALUES
    (1, 1, 'Cours de base en architecture web et SQL')
ON DUPLICATE KEY UPDATE
    code_type_background_etude = VALUES(code_type_background_etude),
    contenu = VALUES(contenu);

INSERT INTO offre (
    id_offre, titre, description, id_entreprise, work_type, code_type_contrat, nbr_annee_experience,
    code_type_niveau_etude, min_salaire, max_salaire, num_ordre_creation, num_ordre_expiration
)
VALUES
    (1, 'Developpeur Symfony', 'Offre backend Symfony', 1, 'hybride', 1, 2, 3, 2500, 3500, 1001, 1004)
ON DUPLICATE KEY UPDATE
    titre = VALUES(titre),
    description = VALUES(description),
    id_entreprise = VALUES(id_entreprise),
    work_type = VALUES(work_type),
    code_type_contrat = VALUES(code_type_contrat),
    nbr_annee_experience = VALUES(nbr_annee_experience),
    code_type_niveau_etude = VALUES(code_type_niveau_etude),
    min_salaire = VALUES(min_salaire),
    max_salaire = VALUES(max_salaire),
    num_ordre_creation = VALUES(num_ordre_creation),
    num_ordre_expiration = VALUES(num_ordre_expiration);

INSERT INTO detail_offre_background (id_offre, code_type_background_etude)
VALUES (1, 1)
ON DUPLICATE KEY UPDATE
    id_offre = VALUES(id_offre),
    code_type_background_etude = VALUES(code_type_background_etude);

INSERT INTO detail_offre_competence (id_offre, code_type_competence)
VALUES
    (1, 1),
    (1, 2),
    (1, 3)
ON DUPLICATE KEY UPDATE
    id_offre = VALUES(id_offre),
    code_type_competence = VALUES(code_type_competence);

INSERT INTO detail_offre_langue (id_offre, code_type_langue)
VALUES
    (1, 1),
    (1, 2)
ON DUPLICATE KEY UPDATE
    id_offre = VALUES(id_offre),
    code_type_langue = VALUES(code_type_langue);

INSERT INTO condidat (id_condidat, id_utilisateur, cv)
VALUES
    (1, 4, 'CV candidate demo')
ON DUPLICATE KEY UPDATE
    id_utilisateur = VALUES(id_utilisateur),
    cv = VALUES(cv);

INSERT INTO condidature (
    id_condidature, id_condidat, lettre_motivation, portfolio, lettre_recomendation, code_type_status, id_offre
)
VALUES
    (1, 1, 'Je suis motive pour ce poste', 'https://portfolio.example.com/candidate', 'Lettre de recommandation de test', 1, 1)
ON DUPLICATE KEY UPDATE
    id_condidat = VALUES(id_condidat),
    lettre_motivation = VALUES(lettre_motivation),
    portfolio = VALUES(portfolio),
    lettre_recomendation = VALUES(lettre_recomendation),
    code_type_status = VALUES(code_type_status),
    id_offre = VALUES(id_offre);

INSERT INTO entretien (
    id_condidat, id_rh, num_ordre_entretien, localisation, status_entretien, evaluation
)
VALUES
    (1, 2, 1005, 'Salle RH 1', 1, 'Evaluation initiale favorable')
ON DUPLICATE KEY UPDATE
    localisation = VALUES(localisation),
    status_entretien = VALUES(status_entretien),
    evaluation = VALUES(evaluation);

INSERT INTO demande_conge (
    id_demende, id_employe, nbr_jour_demande, num_ordre_debut_conge, num_ordre_fin_conge, status
)
VALUES
    (1, 1, 3, 1003, 1004, 0)
ON DUPLICATE KEY UPDATE
    id_employe = VALUES(id_employe),
    nbr_jour_demande = VALUES(nbr_jour_demande),
    num_ordre_debut_conge = VALUES(num_ordre_debut_conge),
    num_ordre_fin_conge = VALUES(num_ordre_fin_conge),
    status = VALUES(status);

INSERT INTO action_utilisateur (id_utilisateur, code_type_action, num_ordre)
VALUES
    (1, 1, 1001),
    (2, 2, 1002),
    (3, 1, 1003)
ON DUPLICATE KEY UPDATE
    id_utilisateur = VALUES(id_utilisateur),
    code_type_action = VALUES(code_type_action),
    num_ordre = VALUES(num_ordre);
