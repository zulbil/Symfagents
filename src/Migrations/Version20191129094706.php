<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191129094706 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE agent_tasks ADD agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agent_tasks ADD CONSTRAINT FK_69F64DEF3414710B FOREIGN KEY (agent_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_69F64DEF3414710B ON agent_tasks (agent_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE agent_tasks DROP FOREIGN KEY FK_69F64DEF3414710B');
        $this->addSql('DROP INDEX IDX_69F64DEF3414710B ON agent_tasks');
        $this->addSql('ALTER TABLE agent_tasks DROP agent_id');
    }
}
