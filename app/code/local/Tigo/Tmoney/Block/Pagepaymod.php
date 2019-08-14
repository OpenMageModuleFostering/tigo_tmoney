<?php 
class Tigo_Tmoney_Block_Pagepaymod extends Mage_Core_Block_Template{
    protected $_methodCode = 'tmoney';
	
    protected function _construct(){
        parent::_construct();
        $this->setTemplate('tigo/tmoney/paymod.phtml');
    }
}
