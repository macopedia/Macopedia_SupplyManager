<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$attachmentsTable = $installer->getTable('macopedia_supplymanager/attachment');
$installer->startSetup();
if ($installer->tableExists($attachmentsTable)) {
    $installer->run(
        "DROP TABLE IF EXISTS `{$attachmentsTable}`;"
    );
}

$table = $installer->getConnection()
    ->newTable($attachmentsTable)
    ->addColumn(
        'attachment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 'Attachment ID'
    )
    ->addColumn(
        'employee_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => true,
            'default'  => null,
        ), 'Employee ID'
    )
    ->addColumn(
        'supply_manager_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => true,
            'default'  => null,
        ), 'Supply Manager ID'
    )
    ->addColumn(
        'status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'default'  => '0',
        ), 'Status'
    )
    ->addColumn(
        'email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Email'
    )
    ->setComment('Supply manager <> Employee attachments');
$installer->getConnection()->createTable($table);

$installer->endSetup();