<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200430174151 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE role_user (role_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_332CA4DDD60322AC (role_id), INDEX IDX_332CA4DDA76ED395 (user_id), PRIMARY KEY(role_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE role_user ADD CONSTRAINT FK_332CA4DDD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_user ADD CONSTRAINT FK_332CA4DDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE email_verified_at email_verified_at DATETIME DEFAULT NULL, CHANGE remember_token remember_token VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE last_connected_at last_connected_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz CHANGE data data VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE answer CHANGE question_id question_id INT DEFAULT NULL, CHANGE is_correct is_correct TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE historic CHANGE user_id user_id INT DEFAULT NULL, CHANGE answers answers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE score score VARCHAR(255) DEFAULT NULL, CHANGE succeeded succeeded TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE role DROP FOREIGN KEY FK_57698A6A67B3B43D');
        $this->addSql('DROP INDEX IDX_57698A6A67B3B43D ON role');
        $this->addSql('ALTER TABLE role DROP users_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE role_user');
        $this->addSql('ALTER TABLE answer CHANGE question_id question_id INT DEFAULT NULL, CHANGE is_correct is_correct TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE historic CHANGE user_id user_id INT DEFAULT NULL, CHANGE answers answers LONGTEXT CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE score score VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE succeeded succeeded TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE quiz CHANGE data data VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE role ADD users_id INT NOT NULL');
        $this->addSql('ALTER TABLE role ADD CONSTRAINT FK_57698A6A67B3B43D FOREIGN KEY (users_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_57698A6A67B3B43D ON role (users_id)');
        $this->addSql('ALTER TABLE user CHANGE email_verified_at email_verified_at DATETIME DEFAULT \'NULL\', CHANGE remember_token remember_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE last_connected_at last_connected_at DATETIME DEFAULT \'NULL\'');
    }
}
