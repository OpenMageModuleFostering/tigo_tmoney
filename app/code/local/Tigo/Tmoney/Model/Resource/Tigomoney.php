<?php

class Tigo_Tmoney_Model_Resource_Tigomoney extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('tigo_tmoney/tigomoney', 'tmoney_id');
    }
}
