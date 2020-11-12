<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190925160827 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, login VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE posts_olod');
        $this->addSql('ALTER TABLE sections CHANGE title title VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_5A8A6C8DEAF7576F ON posts');
        $this->addSql('ALTER TABLE posts CHANGE title title VARCHAR(255) NOT NULL, CHANGE slug slug VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE images CHANGE file_name file_name VARCHAR(255) NOT NULL, CHANGE post_date post_date VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE posts_olod (id INT AUTO_INCREMENT NOT NULL, log LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, title VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, slug VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, post_date VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, section_id INT NOT NULL, clicks INT NOT NULL, build_hours INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE images CHANGE file_name file_name VARCHAR(255) DEFAULT \'\'\'\' NOT NULL COLLATE latin1_swedish_ci, CHANGE post_date post_date VARCHAR(255) DEFAULT \'\'\'\' NOT NULL COLLATE latin1_swedish_ci');
        $this->addSql('ALTER TABLE posts CHANGE title title VARCHAR(255) DEFAULT \'NULL\' COLLATE latin1_swedish_ci, CHANGE slug slug VARCHAR(255) DEFAULT \'\'\'\' NOT NULL COLLATE latin1_swedish_ci');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A8A6C8DEAF7576F ON posts (title)');
        $this->addSql('ALTER TABLE sections CHANGE title title VARCHAR(255) DEFAULT \'\'\'\' NOT NULL COLLATE latin1_swedish_ci');
    }
}
