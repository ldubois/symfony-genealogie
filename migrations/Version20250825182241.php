<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250825182241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE lien (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, personne1_id INTEGER NOT NULL, personne2_id INTEGER NOT NULL, type_lien_id INTEGER NOT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, notes CLOB DEFAULT NULL, CONSTRAINT FK_A532B4B52577470A FOREIGN KEY (personne1_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A532B4B537C2E8E4 FOREIGN KEY (personne2_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A532B4B5C58BACA7 FOREIGN KEY (type_lien_id) REFERENCES type_lien (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A532B4B52577470A ON lien (personne1_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A532B4B537C2E8E4 ON lien (personne2_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A532B4B5C58BACA7 ON lien (type_lien_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE type_lien (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, est_biologique BOOLEAN NOT NULL, est_parental BOOLEAN NOT NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE lien
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE type_lien
        SQL);
    }
}
