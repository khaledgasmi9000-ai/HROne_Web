<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417093122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE work_session (id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, session_duration DOUBLE PRECISION DEFAULT NULL, active_time DOUBLE PRECISION DEFAULT NULL, afk_time DOUBLE PRECISION DEFAULT NULL, unknown_time DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE work_session_detail (id INT AUTO_INCREMENT NOT NULL, app VARCHAR(255) NOT NULL, duration DOUBLE PRECISION NOT NULL, tool_id INT DEFAULT NULL, percentage DOUBLE PRECISION DEFAULT NULL, work_session_id INT NOT NULL, INDEX IDX_60995C067A5C410C (work_session_id), UNIQUE INDEX unique_session_app (work_session_id, app), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE work_session_detail ADD CONSTRAINT FK_60995C067A5C410C FOREIGN KEY (work_session_id) REFERENCES work_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE action_utilisateur DROP FOREIGN KEY FK_ActionUTILISATEUR_Type');
        $this->addSql('ALTER TABLE action_utilisateur DROP FOREIGN KEY FK_ActionUTILISATEUR_UTILISATEUR');
        $this->addSql('ALTER TABLE action_utilisateur DROP FOREIGN KEY FK_ActionUTILISATEUR_Ordre');
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY FK_Chat_UTILISATEUR2');
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY FK_Chat_UTILISATEUR1');
        $this->addSql('ALTER TABLE condidat DROP FOREIGN KEY FK_Condidat_UTILISATEUR');
        $this->addSql('ALTER TABLE condidature DROP FOREIGN KEY FK_Condidature_Status');
        $this->addSql('ALTER TABLE condidature DROP FOREIGN KEY fk_candidature_offre');
        $this->addSql('ALTER TABLE condidature DROP FOREIGN KEY FK_Condidature_Condidat');
        $this->addSql('ALTER TABLE cours DROP FOREIGN KEY FK_Cours_Background');
        $this->addSql('ALTER TABLE detail_evenement DROP FOREIGN KEY FK_DE_Activite');
        $this->addSql('ALTER TABLE detail_evenement DROP FOREIGN KEY FK_DE_Ordre_Fin');
        $this->addSql('ALTER TABLE detail_evenement DROP FOREIGN KEY FK_DE_Evenement');
        $this->addSql('ALTER TABLE detail_evenement DROP FOREIGN KEY FK_DE_Ordre_Debut');
        $this->addSql('ALTER TABLE detail_offre_background DROP FOREIGN KEY FK_DOB_Background');
        $this->addSql('ALTER TABLE detail_offre_background DROP FOREIGN KEY FK_DOB_Offre');
        $this->addSql('ALTER TABLE detail_offre_competence DROP FOREIGN KEY FK_DOC_Competence');
        $this->addSql('ALTER TABLE detail_offre_competence DROP FOREIGN KEY FK_DOC_Offre');
        $this->addSql('ALTER TABLE detail_offre_langue DROP FOREIGN KEY FK_DOL_Offre');
        $this->addSql('ALTER TABLE detail_offre_langue DROP FOREIGN KEY FK_DOL_Langue');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_Email_Sender');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_Email_Ordre');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_Email_Receiver');
        $this->addSql('ALTER TABLE entretien DROP FOREIGN KEY FK_Entretien_Condidat');
        $this->addSql('ALTER TABLE entretien DROP FOREIGN KEY FK_Entretien_Ordre');
        $this->addSql('ALTER TABLE entretien DROP FOREIGN KEY FK_Entretien_RH');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_Evenement_Ordre_Creation');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_Evenement_Ordre_Debut');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_Evenement_Ordre_Fin');
        $this->addSql('ALTER TABLE liste_attente DROP FOREIGN KEY liste_attente_ibfk_1');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_Message_Chat');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_Message_UTILISATEUR');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_Offre_Contrat');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_Offre_Ordre_Creation');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_Offre_Entreprise');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_Offre_Ordre_Expiration');
        $this->addSql('ALTER TABLE offre DROP FOREIGN KEY FK_Offre_Niveau');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_PE_Ordre');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_PE_Activite');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_PE_UTILISATEUR');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_PE_Evenement');
        $this->addSql('DROP TABLE action_utilisateur');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE certification');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE comment_votes');
        $this->addSql('DROP TABLE condidat');
        $this->addSql('DROP TABLE condidature');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE detail_evenement');
        $this->addSql('DROP TABLE detail_offre_background');
        $this->addSql('DROP TABLE detail_offre_competence');
        $this->addSql('DROP TABLE detail_offre_langue');
        $this->addSql('DROP TABLE email');
        $this->addSql('DROP TABLE entretien');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE liste_attente');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE newsletter_emails');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE participation_evenement');
        $this->addSql('DROP TABLE participation_formation');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE post_votes');
        $this->addSql('DROP TABLE type_action');
        $this->addSql('DROP TABLE type_background_etude');
        $this->addSql('DROP TABLE type_competence');
        $this->addSql('DROP TABLE type_contrat');
        $this->addSql('DROP TABLE type_langue');
        $this->addSql('DROP TABLE type_niveau_etude');
        $this->addSql('DROP TABLE type_status_condidature');
        $this->addSql('ALTER TABLE demande_conge CHANGE Num_Ordre_Debut_Conge Num_Ordre_Debut_Conge INT DEFAULT NULL, CHANGE Num_Ordre_Fin_Conge Num_Ordre_Fin_Conge INT DEFAULT NULL');
        $this->addSql('ALTER TABLE demande_conge RENAME INDEX fk_demandeconge_employee TO IDX_D80610612488C226');
        $this->addSql('ALTER TABLE demande_conge RENAME INDEX fk_demandeconge_ordre_debut TO IDX_D8061061EA582039');
        $this->addSql('ALTER TABLE demande_conge RENAME INDEX fk_demandeconge_ordre_fin TO IDX_D80610619E941DFE');
        $this->addSql('ALTER TABLE employee CHANGE Mac_Machine mac_machine VARCHAR(255) DEFAULT NULL, CHANGE SALAIRE salaire INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employee RENAME INDEX fk_employee_utilisateur TO IDX_5D9F75A1F6170F81');
        $this->addSql('ALTER TABLE outil_employee RENAME INDEX pfk_outil TO IDX_FE715F28D78A274');
        $this->addSql('ALTER TABLE entreprise CHANGE Reference reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ordre CHANGE AAAA aaaa INT NOT NULL, CHANGE MM mm INT NOT NULL, CHANGE JJ jj INT NOT NULL, CHANGE HH hh INT NOT NULL, CHANGE MN mn INT NOT NULL, CHANGE SS ss INT NOT NULL');
        $this->addSql('ALTER TABLE outils_de_travail CHANGE Nom_Outil nom_outil VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE profil CHANGE Nom_Profil nom_profil VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_UTILISATEUR_Ordre');
        $this->addSql('DROP INDEX FK_UTILISATEUR_Ordre ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP Num_Ordre_Sign_In, CHANGE ID_Entreprise ID_Entreprise INT DEFAULT NULL, CHANGE ID_Profil ID_Profil INT DEFAULT NULL, CHANGE Nom_Utilisateur nom_utilisateur VARCHAR(255) NOT NULL, CHANGE Email email VARCHAR(255) DEFAULT NULL, CHANGE Adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE Num_Tel num_tel VARCHAR(255) DEFAULT NULL, CHANGE CIN cin VARCHAR(255) DEFAULT NULL, CHANGE Date_Naissance date_naissance DATE DEFAULT NULL, CHANGE Gender gender VARCHAR(255) DEFAULT NULL, CHANGE firstLogin firstLogin INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX fk_utilisateur_entreprise TO IDX_1D1C63B34C81E96A');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX fk_utilisateur_profil TO IDX_1D1C63B3BCCAE2B9');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action_utilisateur (ID_UTILISATEUR INT NOT NULL, Code_Type_Action INT NOT NULL, Num_Ordre INT NOT NULL, Commentaire VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, INDEX FK_ActionUTILISATEUR_Type (Code_Type_Action), INDEX FK_ActionUTILISATEUR_Ordre (Num_Ordre), INDEX IDX_66CEEA77F6170F81 (ID_UTILISATEUR), PRIMARY KEY(ID_UTILISATEUR, Code_Type_Action, Num_Ordre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE activite (ID_Activite INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ID_Evenement INT DEFAULT NULL, PRIMARY KEY(ID_Activite)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE certification (ID_Certif INT NOT NULL, ID_Formation INT NOT NULL, ID_Participant INT NOT NULL, Description_Certif VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Fichier_PDF LONGBLOB DEFAULT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE chat (ID_Chat INT AUTO_INCREMENT NOT NULL, ID_UTILISATEUR1 INT NOT NULL, ID_UTILISATEUR2 INT NOT NULL, INDEX FK_Chat_UTILISATEUR2 (ID_UTILISATEUR2), INDEX FK_Chat_UTILISATEUR1 (ID_UTILISATEUR1), PRIMARY KEY(ID_Chat)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comments (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, parent_comment_id INT DEFAULT NULL, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_active TINYINT(1) DEFAULT 1, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment_votes (id INT AUTO_INCREMENT NOT NULL, comment_id INT NOT NULL, user_id INT NOT NULL, vote_type ENUM(\'up\', \'down\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX unique_comment_vote (comment_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE condidat (ID_Condidat INT AUTO_INCREMENT NOT NULL, ID_UTILISATEUR INT NOT NULL, CV TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX FK_Condidat_UTILISATEUR (ID_UTILISATEUR), PRIMARY KEY(ID_Condidat)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE condidature (ID_Condidature INT AUTO_INCREMENT NOT NULL, ID_Condidat INT NOT NULL, Lettre_Motivation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Portfolio TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Lettre_Recomendation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Code_Type_Status INT NOT NULL, ID_Offre INT DEFAULT NULL, INDEX fk_candidature_offre (ID_Offre), INDEX FK_Condidature_Condidat (ID_Condidat), INDEX FK_Condidature_Status (Code_Type_Status), PRIMARY KEY(ID_Condidature)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE cours (ID_Cours INT AUTO_INCREMENT NOT NULL, Code_Type_Background_Etude INT NOT NULL, Contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX FK_Cours_Background (Code_Type_Background_Etude), PRIMARY KEY(ID_Cours)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE detail_evenement (ID_Evenement INT NOT NULL, ID_Activite INT NOT NULL, Num_Ordre_Debut_Activite INT NOT NULL, Num_Ordre_Fin_Activite INT NOT NULL, INDEX FK_DE_Ordre_Fin (Num_Ordre_Fin_Activite), INDEX FK_DE_Activite (ID_Activite), INDEX FK_DE_Ordre_Debut (Num_Ordre_Debut_Activite), INDEX IDX_1AD1BDAB974F02B5 (ID_Evenement), PRIMARY KEY(ID_Evenement, ID_Activite, Num_Ordre_Debut_Activite, Num_Ordre_Fin_Activite)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE detail_offre_background (ID_Offre INT NOT NULL, Code_Type_Background_Etude INT NOT NULL, INDEX FK_DOB_Background (Code_Type_Background_Etude), INDEX IDX_B3398259B609B391 (ID_Offre), PRIMARY KEY(ID_Offre, Code_Type_Background_Etude)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE detail_offre_competence (ID_Offre INT NOT NULL, Code_Type_Competence INT NOT NULL, INDEX FK_DOC_Competence (Code_Type_Competence), INDEX IDX_9B855E76B609B391 (ID_Offre), PRIMARY KEY(ID_Offre, Code_Type_Competence)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE detail_offre_langue (ID_Offre INT NOT NULL, Code_Type_Langue INT NOT NULL, INDEX FK_DOL_Langue (Code_Type_Langue), INDEX IDX_E8DE7FAAB609B391 (ID_Offre), PRIMARY KEY(ID_Offre, Code_Type_Langue)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE email (ID_Email INT NOT NULL, ID_Receiver INT NOT NULL, ID_Sender INT NOT NULL, Objet VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Contenue TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Num_Ordre_Envoi INT NOT NULL, Status_Mail INT NOT NULL, INDEX FK_Email_Receiver (ID_Receiver), INDEX FK_Email_Sender (ID_Sender), INDEX FK_Email_Ordre (Num_Ordre_Envoi), PRIMARY KEY(ID_Email, ID_Receiver, ID_Sender)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE entretien (ID_Condidat INT NOT NULL, ID_RH INT NOT NULL, Num_Ordre_Entretien INT NOT NULL, Localisation VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Status_Entretien INT DEFAULT NULL, Evaluation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX FK_Entretien_RH (ID_RH), INDEX FK_Entretien_Ordre (Num_Ordre_Entretien), INDEX IDX_2B58D6DA5AE6CDE8 (ID_Condidat), PRIMARY KEY(ID_Condidat, ID_RH, Num_Ordre_Entretien)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE evenement (ID_Evenement INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Num_Ordre_Creation INT NOT NULL, Num_Ordre_Debut_Evenement INT NOT NULL, Num_Ordre_Fin_Evenement INT NOT NULL, Localisation VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, est_payant TINYINT(1) DEFAULT 0, prix DOUBLE PRECISION DEFAULT \'0\', nbMax INT DEFAULT 50, INDEX FK_Evenement_Ordre_Creation (Num_Ordre_Creation), INDEX FK_Evenement_Ordre_Debut (Num_Ordre_Debut_Evenement), INDEX FK_Evenement_Ordre_Fin (Num_Ordre_Fin_Evenement), PRIMARY KEY(ID_Evenement)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE formation (ID_Formation INT NOT NULL, Titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Num_Ordre_Creation INT NOT NULL, ID_Entreprise INT NOT NULL, Image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Mode VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'\'\'presentiel\'\'\' COLLATE `utf8mb4_general_ci`, NombrePlaces INT DEFAULT 30, PlacesRestantes INT DEFAULT 30, Date_Debut BIGINT DEFAULT 0, Date_Fin BIGINT DEFAULT 0, Niveau VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'\'\'Débutant\'\'\' COLLATE `utf8mb4_general_ci`) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE liste_attente (ID_Attente INT AUTO_INCREMENT NOT NULL, ID_Evenement INT DEFAULT NULL, ID_Activite INT DEFAULT NULL, nom_complet VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, date_demande DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX ID_Evenement (ID_Evenement), PRIMARY KEY(ID_Attente)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE message (ID_Message INT AUTO_INCREMENT NOT NULL, ID_Chat INT NOT NULL, ID_Sender INT NOT NULL, Contenue TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Status INT NOT NULL, INDEX FK_Message_UTILISATEUR (ID_Sender), INDEX FK_Message_Chat (ID_Chat), PRIMARY KEY(ID_Message)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE newsletter_emails (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, subscribed_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE offre (ID_Offre INT AUTO_INCREMENT NOT NULL, Titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ID_Entreprise INT NOT NULL, Work_Type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, Code_Type_Contrat INT NOT NULL, Nbr_Annee_Experience INT DEFAULT NULL, Code_Type_Niveau_Etude INT NOT NULL, Min_Salaire INT DEFAULT NULL, Max_Salaire INT DEFAULT NULL, Num_Ordre_Creation INT NOT NULL, Num_Ordre_Expiration INT NOT NULL, INDEX FK_Offre_Niveau (Code_Type_Niveau_Etude), INDEX FK_Offre_Ordre_Creation (Num_Ordre_Creation), INDEX FK_Offre_Entreprise (ID_Entreprise), INDEX FK_Offre_Ordre_Expiration (Num_Ordre_Expiration), INDEX FK_Offre_Contrat (Code_Type_Contrat), PRIMARY KEY(ID_Offre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE participation_evenement (ID_Evenement INT NOT NULL, ID_Activite INT NOT NULL, ID_Participant INT NOT NULL, Num_Ordre_Participation INT NOT NULL, nom_complet VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, mode_paiement VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'Gratuit\'\'\' COLLATE `utf8mb4_general_ci`, INDEX FK_PE_Ordre (Num_Ordre_Participation), INDEX FK_PE_Activite (ID_Activite), INDEX FK_PE_UTILISATEUR (ID_Participant), INDEX IDX_65A14675974F02B5 (ID_Evenement), PRIMARY KEY(ID_Evenement, ID_Activite, ID_Participant)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE participation_formation (ID_Formation INT NOT NULL, ID_Participant INT NOT NULL, Num_Ordre_Participation BIGINT NOT NULL, Statut VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'\'\'inscrit\'\'\' COLLATE `utf8mb4_general_ci`, Certificat VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE posts (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, tag VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'General\'\'\' COLLATE `utf8mb4_general_ci`, is_active TINYINT(1) DEFAULT 1, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE post_votes (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, vote_type ENUM(\'up\', \'down\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX unique_post_vote (post_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_action (Code_Type_Action INT AUTO_INCREMENT NOT NULL, Description_Action VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(Code_Type_Action)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_background_etude (Code_Type_Background_Etude INT AUTO_INCREMENT NOT NULL, Description_Type_Background_Etude VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(Code_Type_Background_Etude)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_competence (Code_Type_Competence INT AUTO_INCREMENT NOT NULL, Description_Competence VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(Code_Type_Competence)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_contrat (Code_Type_Contrat INT AUTO_INCREMENT NOT NULL, Description_Contrat VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(Code_Type_Contrat)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_langue (Code_Type_Langue INT AUTO_INCREMENT NOT NULL, Description_Langue VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(Code_Type_Langue)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_niveau_etude (Code_Type_Niveau_Etude INT AUTO_INCREMENT NOT NULL, Description_Type_Etude VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(Code_Type_Niveau_Etude)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE type_status_condidature (Code_Type_Status_Condidature INT AUTO_INCREMENT NOT NULL, Description_Status_Condidature VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(Code_Type_Status_Condidature)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE action_utilisateur ADD CONSTRAINT FK_ActionUTILISATEUR_Type FOREIGN KEY (Code_Type_Action) REFERENCES type_action (Code_Type_Action)');
        $this->addSql('ALTER TABLE action_utilisateur ADD CONSTRAINT FK_ActionUTILISATEUR_UTILISATEUR FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE action_utilisateur ADD CONSTRAINT FK_ActionUTILISATEUR_Ordre FOREIGN KEY (Num_Ordre) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT FK_Chat_UTILISATEUR2 FOREIGN KEY (ID_UTILISATEUR2) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT FK_Chat_UTILISATEUR1 FOREIGN KEY (ID_UTILISATEUR1) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE condidat ADD CONSTRAINT FK_Condidat_UTILISATEUR FOREIGN KEY (ID_UTILISATEUR) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE condidature ADD CONSTRAINT FK_Condidature_Status FOREIGN KEY (Code_Type_Status) REFERENCES type_status_condidature (Code_Type_Status_Condidature)');
        $this->addSql('ALTER TABLE condidature ADD CONSTRAINT fk_candidature_offre FOREIGN KEY (ID_Offre) REFERENCES offre (ID_Offre) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE condidature ADD CONSTRAINT FK_Condidature_Condidat FOREIGN KEY (ID_Condidat) REFERENCES condidat (ID_Condidat)');
        $this->addSql('ALTER TABLE cours ADD CONSTRAINT FK_Cours_Background FOREIGN KEY (Code_Type_Background_Etude) REFERENCES type_background_etude (Code_Type_Background_Etude)');
        $this->addSql('ALTER TABLE detail_evenement ADD CONSTRAINT FK_DE_Activite FOREIGN KEY (ID_Activite) REFERENCES activite (ID_Activite)');
        $this->addSql('ALTER TABLE detail_evenement ADD CONSTRAINT FK_DE_Ordre_Fin FOREIGN KEY (Num_Ordre_Fin_Activite) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE detail_evenement ADD CONSTRAINT FK_DE_Evenement FOREIGN KEY (ID_Evenement) REFERENCES evenement (ID_Evenement)');
        $this->addSql('ALTER TABLE detail_evenement ADD CONSTRAINT FK_DE_Ordre_Debut FOREIGN KEY (Num_Ordre_Debut_Activite) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE detail_offre_background ADD CONSTRAINT FK_DOB_Background FOREIGN KEY (Code_Type_Background_Etude) REFERENCES type_background_etude (Code_Type_Background_Etude)');
        $this->addSql('ALTER TABLE detail_offre_background ADD CONSTRAINT FK_DOB_Offre FOREIGN KEY (ID_Offre) REFERENCES offre (ID_Offre)');
        $this->addSql('ALTER TABLE detail_offre_competence ADD CONSTRAINT FK_DOC_Competence FOREIGN KEY (Code_Type_Competence) REFERENCES type_competence (Code_Type_Competence)');
        $this->addSql('ALTER TABLE detail_offre_competence ADD CONSTRAINT FK_DOC_Offre FOREIGN KEY (ID_Offre) REFERENCES offre (ID_Offre)');
        $this->addSql('ALTER TABLE detail_offre_langue ADD CONSTRAINT FK_DOL_Offre FOREIGN KEY (ID_Offre) REFERENCES offre (ID_Offre)');
        $this->addSql('ALTER TABLE detail_offre_langue ADD CONSTRAINT FK_DOL_Langue FOREIGN KEY (Code_Type_Langue) REFERENCES type_langue (Code_Type_Langue)');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_Email_Sender FOREIGN KEY (ID_Sender) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_Email_Ordre FOREIGN KEY (Num_Ordre_Envoi) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_Email_Receiver FOREIGN KEY (ID_Receiver) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT FK_Entretien_Condidat FOREIGN KEY (ID_Condidat) REFERENCES condidat (ID_Condidat)');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT FK_Entretien_Ordre FOREIGN KEY (Num_Ordre_Entretien) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT FK_Entretien_RH FOREIGN KEY (ID_RH) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_Evenement_Ordre_Creation FOREIGN KEY (Num_Ordre_Creation) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_Evenement_Ordre_Debut FOREIGN KEY (Num_Ordre_Debut_Evenement) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_Evenement_Ordre_Fin FOREIGN KEY (Num_Ordre_Fin_Evenement) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE liste_attente ADD CONSTRAINT liste_attente_ibfk_1 FOREIGN KEY (ID_Evenement) REFERENCES evenement (ID_Evenement) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_Message_Chat FOREIGN KEY (ID_Chat) REFERENCES chat (ID_Chat)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_Message_UTILISATEUR FOREIGN KEY (ID_Sender) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_Offre_Contrat FOREIGN KEY (Code_Type_Contrat) REFERENCES type_contrat (Code_Type_Contrat)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_Offre_Ordre_Creation FOREIGN KEY (Num_Ordre_Creation) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_Offre_Entreprise FOREIGN KEY (ID_Entreprise) REFERENCES entreprise (ID_Entreprise)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_Offre_Ordre_Expiration FOREIGN KEY (Num_Ordre_Expiration) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE offre ADD CONSTRAINT FK_Offre_Niveau FOREIGN KEY (Code_Type_Niveau_Etude) REFERENCES type_niveau_etude (Code_Type_Niveau_Etude)');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_PE_Ordre FOREIGN KEY (Num_Ordre_Participation) REFERENCES ordre (Num_Ordre)');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_PE_Activite FOREIGN KEY (ID_Activite) REFERENCES activite (ID_Activite)');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_PE_UTILISATEUR FOREIGN KEY (ID_Participant) REFERENCES utilisateur (ID_UTILISATEUR)');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_PE_Evenement FOREIGN KEY (ID_Evenement) REFERENCES evenement (ID_Evenement)');
        $this->addSql('ALTER TABLE work_session_detail DROP FOREIGN KEY FK_60995C067A5C410C');
        $this->addSql('DROP TABLE work_session');
        $this->addSql('DROP TABLE work_session_detail');
        $this->addSql('ALTER TABLE demande_conge CHANGE Num_Ordre_Debut_Conge Num_Ordre_Debut_Conge INT NOT NULL, CHANGE Num_Ordre_Fin_Conge Num_Ordre_Fin_Conge INT NOT NULL');
        $this->addSql('ALTER TABLE demande_conge RENAME INDEX idx_d80610619e941dfe TO FK_DemandeConge_Ordre_Fin');
        $this->addSql('ALTER TABLE demande_conge RENAME INDEX idx_d80610612488c226 TO FK_DemandeConge_Employee');
        $this->addSql('ALTER TABLE demande_conge RENAME INDEX idx_d8061061ea582039 TO FK_DemandeConge_Ordre_Debut');
        $this->addSql('ALTER TABLE employee CHANGE mac_machine Mac_Machine VARCHAR(50) DEFAULT \'NULL\', CHANGE salaire SALAIRE INT DEFAULT 0');
        $this->addSql('ALTER TABLE employee RENAME INDEX idx_5d9f75a1f6170f81 TO FK_Employee_UTILISATEUR');
        $this->addSql('ALTER TABLE entreprise CHANGE reference Reference VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE ordre CHANGE aaaa AAAA INT DEFAULT 0, CHANGE mm MM INT DEFAULT 0, CHANGE jj JJ INT DEFAULT 0, CHANGE hh HH INT DEFAULT 0, CHANGE mn MN INT DEFAULT 0, CHANGE ss SS INT DEFAULT 0');
        $this->addSql('ALTER TABLE outils_de_travail CHANGE nom_outil Nom_Outil VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE outil_employee RENAME INDEX idx_fe715f28d78a274 TO pfk_outil');
        $this->addSql('ALTER TABLE profil CHANGE nom_profil Nom_Profil VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD Num_Ordre_Sign_In INT DEFAULT 0 NOT NULL, CHANGE nom_utilisateur Nom_Utilisateur VARCHAR(100) NOT NULL, CHANGE email Email VARCHAR(255) DEFAULT \'NULL\', CHANGE adresse Adresse VARCHAR(255) DEFAULT \'NULL\', CHANGE num_tel Num_Tel VARCHAR(30) DEFAULT \'NULL\', CHANGE cin CIN VARCHAR(20) DEFAULT \'NULL\', CHANGE date_naissance Date_Naissance DATE DEFAULT \'NULL\', CHANGE gender Gender CHAR(1) DEFAULT \'NULL\', CHANGE firstLogin firstLogin INT DEFAULT 0, CHANGE ID_Entreprise ID_Entreprise INT DEFAULT 0 NOT NULL, CHANGE ID_Profil ID_Profil INT DEFAULT 3 NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_UTILISATEUR_Ordre FOREIGN KEY (Num_Ordre_Sign_In) REFERENCES ordre (Num_Ordre)');
        $this->addSql('CREATE INDEX FK_UTILISATEUR_Ordre ON utilisateur (Num_Ordre_Sign_In)');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX idx_1d1c63b34c81e96a TO FK_UTILISATEUR_Entreprise');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX idx_1d1c63b3bccae2b9 TO FK_UTILISATEUR_Profil');
    }
}
