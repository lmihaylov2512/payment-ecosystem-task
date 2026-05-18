<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518111445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transactions (id INT UNSIGNED AUTO_INCREMENT NOT NULL, transaction_id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, user_email VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, payment_method VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, request_id VARCHAR(255) NOT NULL, correlation_id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, accepted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_EAA81A4C2FC0CB0F (transaction_id), UNIQUE INDEX UNIQ_EAA81A4C427EB8A5 (request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE transactions');
    }
}
