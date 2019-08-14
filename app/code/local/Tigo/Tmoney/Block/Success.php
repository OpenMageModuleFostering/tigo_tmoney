<?php 
class Tigo_Tmoney_Block_Success extends Mage_Core_Block_Template{
    protected $_methodCode = 'tmoney';
	
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('tigo/tmoney/success.phtml');
    }
    public function validate(){
        $response = array();
        
        try{
            $resp = Mage::app()->getRequest()->getParam('r');//(isset($_REQUEST['r']))? $_REQUEST['r'] : '';
            $excep = Mage::app()->getRequest()->getParam('e');//(isset($_REQUEST['e']))? $_REQUEST['e'] : '';
            $tigomoney = Mage::getModel('tmoney/paymethod');
            $response = $tigomoney->tigoverify($resp, $excep);
        } catch (Exception $ex){
            Mage::Log('Error validate status Error page(Block): '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $response;
    }
}
