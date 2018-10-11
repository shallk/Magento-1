<?php

include 'abstract.php';

/**
 * Changing catalog product attribute type to integer
 *
 * Class Change_Attributes_Type
 */
class Change_Attributes_Type extends Mage_Shell_Abstract
{
    protected $backendType = 'int';

    public function __construct()
    {
        parent::__construct();
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
    }

    public function run()
    {
        $code = $this->getArg('code');
        $this->alterAttribute($code);
    }

    /**
     * @param $code
     */
    protected function alterAttribute($code)
    {
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($code, 'attribute_code');
        $attribute->setData('backend_type', $this->backendType)->save();
        $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = '
            INSERT INTO catalog_product_entity_int
                (entity_type_id, attribute_id, entity_id, store_id, value)
            SELECT
                entity_type_id, attribute_id, entity_id, store_id, value from catalog_product_entity_varchar
            WHERE
                attribute_id=' . $attribute->getId() . ' AND entity_type_id=' . $entityTypeId . ';

            DELETE FROM catalog_product_entity_varchar WHERE attribute_id=' . $attribute->getId() . ' AND entity_type_id= ' . $entityTypeId . ';
        ';
        $write->query($query);
        echo "Processed attribute '" . $code . "' \n";
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f change_attribute_type.php -- [options]

  --code <code>       Update type from varchar to int for attribute
  
  help                This help

  <code>     Attribute code for update

USAGE;
    }
}

$app = new Change_Attributes_Type();
$app->run();