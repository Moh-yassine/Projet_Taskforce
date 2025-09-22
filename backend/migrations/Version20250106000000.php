<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250106000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default roles to existing users';
    }

    public function up(Schema $schema): void
    {
        // Ajouter le rôle ROLE_COLLABORATOR par défaut aux utilisateurs existants qui n'ont que ROLE_USER
        $this->addSql("
            UPDATE `user` 
            SET roles = JSON_ARRAY('ROLE_COLLABORATOR', 'ROLE_USER') 
            WHERE JSON_LENGTH(roles) = 1 AND JSON_CONTAINS(roles, '\"ROLE_USER\"')
        ");
    }

    public function down(Schema $schema): void
    {
        // Retirer le rôle ROLE_COLLABORATOR des utilisateurs
        $this->addSql("
            UPDATE `user` 
            SET roles = JSON_ARRAY('ROLE_USER') 
            WHERE JSON_CONTAINS(roles, '\"ROLE_COLLABORATOR\"')
        ");
    }
}
