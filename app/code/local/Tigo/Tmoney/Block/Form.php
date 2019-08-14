<?php 
class Tigo_Tmoney_Block_Form extends Mage_Payment_Block_Form{
    protected $_methodCode = 'tmoney';
    protected $_config;
    
    protected function _construct(){
        parent::_construct();
        $this->setTemplate('tigo/tmoney/form.phtml');
    }
    public function getCCTypes(){
    	return array('VI'=>'Visa', 'MC'=> 'MasterCard');
    }
	
    public function getMethodCode(){
        return $this->_methodCode;
    }
	
    public function getMessage(){
        return  $this->getMethod()->getConfigData('message');
    }
    public function addItem($type, $path){
        $head = $this->getLayout()->getBlock('head');
        return $type == 'css' ? $head->addCss($path) : $type == 'javascript' ? $head->addJs($path) : $this ;
    }
}
