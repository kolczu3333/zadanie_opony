<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250521000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stock_item table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('stock_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('supplier', 'string', ['length' => 64, 'notnull' => true]);
        $table->addColumn('external_id', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('mpn', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('producer_name', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('ean', 'string', ['length' => 32, 'notnull' => false]);
        $table->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'notnull' => true]);
        $table->addColumn('quantity', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['supplier', 'external_id'], 'uniq_supplier_external_id');
        $table->addIndex(['mpn'], 'idx_mpn');
        $table->addIndex(['ean'], 'idx_ean');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('stock_item');
    }
}
