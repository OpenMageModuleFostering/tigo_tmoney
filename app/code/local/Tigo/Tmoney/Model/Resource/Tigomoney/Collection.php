<?php

class Tigo_Tmoney_Model_Resource_Tigomoney_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('tigo_tmoney/tigomoney');
    }
}
