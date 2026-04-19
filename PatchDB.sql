ALTER TABLE utilisateur MODIFY Num_Ordre_Sign_In INT NOT NULL DEFAULT 0;
ALTER TABLE utilisateur MODIFY ID_Profil INT NOT NULL DEFAULT 3;
ALTER TABLE utilisateur MODIFY ID_Entreprise INT NOT NULL DEFAULT 0;
ALTER TABLE ORDRE MODIFY AAAA INT NULL DEFAULT 0;
ALTER TABLE ORDRE MODIFY MM INT NULL DEFAULT 0;
ALTER TABLE ORDRE MODIFY JJ INT NULL DEFAULT 0;
ALTER TABLE ORDRE MODIFY HH INT NULL DEFAULT 0;
ALTER TABLE ORDRE MODIFY MN INT NULL DEFAULT 0;
ALTER TABLE ORDRE MODIFY SS INT NULL DEFAULT 0;

DROP TRIGGER IF EXISTS trg_User_bu_Num_Ordre_Sign_In;
DROP TRIGGER IF EXISTS trg_Action_User_bu_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Demande_Conge_bu_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Detail_Evenement_bi_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Detail_Evenement_bu_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Email_bi_Num_Ordre_Envoi;
DROP TRIGGER IF EXISTS trg_Email_bu_Num_Ordre_Envoi;
DROP TRIGGER IF EXISTS trg_Entretien_bi_Num_Ordre_Entretien;
DROP TRIGGER IF EXISTS trg_Entretien_bu_Num_Ordre_Entretien;
DROP TRIGGER IF EXISTS trg_Evenement_bi_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Action_User_bi_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Demande_Conge_bi_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Evenement_bu_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Email_bi_Num_Ordre_Envoi;
DROP TRIGGER IF EXISTS trg_Offre_bi_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Offre_bu_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Participation_Evenement_bi_Num_Ordre;
DROP TRIGGER IF EXISTS trg_Participation_Evenement_bu_Num_Ordre;
DROP TRIGGER IF EXISTS trg_User_bi_Num_Ordre_Sign_In;

DROP TABLE PERFORMANCE;
CREATE TABLE work_session (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, session_duration DOUBLE PRECISION DEFAULT NULL, active_time DOUBLE PRECISION DEFAULT NULL, afk_time DOUBLE PRECISION DEFAULT NULL, unknown_time DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id));
CREATE TABLE work_session_detail (id INT AUTO_INCREMENT NOT NULL, app VARCHAR(255) NOT NULL, duration DOUBLE PRECISION NOT NULL, tool_id INT DEFAULT NULL, percentage DOUBLE PRECISION DEFAULT NULL, work_session_id INT NOT NULL, INDEX IDX_60995C067A5C410C (work_session_id), UNIQUE INDEX unique_session_app (work_session_id, app), PRIMARY KEY(id));

ALTER TABLE `work_session_detail` DROP INDEX `unique_session_app`;

CREATE TABLE departement ( ID_Departement INT AUTO_INCREMENT PRIMARY KEY, Nom VARCHAR(255) NOT NULL );
INSERT INTO departement (ID_Departement, Nom) VALUES (0,'');
ALTER TABLE employee ADD COLUMN ID_Departement INT DEFAULT 0;
ALTER TABLE employee ADD CONSTRAINT FK_EMP_DEPARTEMENT FOREIGN KEY (ID_Departement) REFERENCES departement(ID_Departement) ON DELETE SET NULL;
INSERT INTO departement (Nom) VALUES ('Direction Générale'), ('Ressources Humaines'), ('Finance'), ('Comptabilité'), ('Informatique'), ('Marketing'), ('Ventes'), ('Support Client'), ('Opérations'), ('Logistique');
UPDATE departement SET Nom = 'Unkown' WHERE ID_Departement = 0;

CREATE TABLE categorie ( ID_Categorie INT AUTO_INCREMENT PRIMARY KEY, Nom VARCHAR(255) NOT NULL );
ALTER TABLE outils_de_travail ADD COLUMN ID_Categorie INT DEFAULT 0;
INSERT INTO `categorie` (`ID_Categorie`, `Nom`) VALUES (1, 'Unknown');
ALTER TABLE outils_de_travail ADD CONSTRAINT FK_OUTIL_CATEGORIE FOREIGN KEY (ID_Categorie) REFERENCES categorie(ID_Categorie);
UPDATE outils_de_travail SET ID_Categorie = 1 WHERE ID_Categorie = NULL;
INSERT INTO categorie (Nom) VALUES ('Navigateurs'), ('Développement'), ('Communication'), ('Gestion de projet'), ('Bureautique'), ('Design'), ('Base de données'), ('DevOps'), ('Sécurité'), ('Support');