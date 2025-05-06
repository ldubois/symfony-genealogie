<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506213632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE person ADD COLUMN gender VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__person AS SELECT id, father_id, mother_id, first_name, last_name, birth_date, death_date, birth_place, death_place, biography, photo, generation FROM person
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE person
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, father_id INTEGER DEFAULT NULL, mother_id INTEGER DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birth_date DATE DEFAULT NULL, death_date DATE DEFAULT NULL, birth_place VARCHAR(255) DEFAULT NULL, death_place VARCHAR(255) DEFAULT NULL, biography CLOB DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, generation INTEGER DEFAULT NULL, CONSTRAINT FK_34DCD1762055B9A2 FOREIGN KEY (father_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_34DCD176B78A354D FOREIGN KEY (mother_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO person (id, father_id, mother_id, first_name, last_name, birth_date, death_date, birth_place, death_place, biography, photo, generation) SELECT id, father_id, mother_id, first_name, last_name, birth_date, death_date, birth_place, death_place, biography, photo, generation FROM __temp__person
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__person
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_34DCD1762055B9A2 ON person (father_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_34DCD176B78A354D ON person (mother_id)
        SQL);
    }
}
