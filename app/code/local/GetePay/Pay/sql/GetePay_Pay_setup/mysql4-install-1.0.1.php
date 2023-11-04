<?php
$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('sales/order'),
    'getepay_payment_id',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 255,
        'nullable' => true,
        'comment' => 'Getepay Payment Id'
    )
);

$installer->endSetup();
?>