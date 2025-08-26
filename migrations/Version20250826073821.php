<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826073821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE person ADD COLUMN surnom VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__person AS SELECT id, pere_id, mere_id, prenom, nom, date_naissance, date_deces, lieu_naissance, lieu_deces, biographie, photo, sexe, generation FROM person
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE person
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, pere_id INTEGER DEFAULT NULL, mere_id INTEGER DEFAULT NULL, prenom VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, date_naissance DATE DEFAULT NULL, date_deces DATE DEFAULT NULL, lieu_naissance VARCHAR(255) DEFAULT NULL, lieu_deces VARCHAR(255) DEFAULT NULL, biographie CLOB DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, sexe VARCHAR(255) DEFAULT NULL, generation INTEGER DEFAULT NULL, CONSTRAINT FK_34DCD1763FD73900 FOREIGN KEY (pere_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_34DCD17639DEC40E FOREIGN KEY (mere_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO person (id, pere_id, mere_id, prenom, nom, date_naissance, date_deces, lieu_naissance, lieu_deces, biographie, photo, sexe, generation) SELECT id, pere_id, mere_id, prenom, nom, date_naissance, date_deces, lieu_naissance, lieu_deces, biographie, photo, sexe, generation FROM __temp__person
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__person
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_34DCD1763FD73900 ON person (pere_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_34DCD17639DEC40E ON person (mere_id)
        SQL);
    }
}
