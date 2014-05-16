<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$attachmentsTable = $installer->getTable('macopedia_supplymanager/attachment');
$installer->startSetup();
$installer->getConnection()->addColumn(
    $attachmentsTable,
    'direction',
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'unsigned' => true,
        'nullable' => true,
        'comment'  => 'Attachment direction'
    )
);

$installer->endSetup();