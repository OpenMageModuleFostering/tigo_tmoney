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
            $id = Mage::app()->getRequest()->getParam('orderID');//(isset($_REQUEST['r']))? $_REQUEST['r'] : '';
            $response['msg'] = Mage::app()->getRequest()->getParam('msg');//(isset($_REQUEST['e']))? $_REQUEST['e'] : '';
            $tigomoney = Mage::getModel('tmoney/paymethod');
            $response['response'] = $tigomoney->verify($id);
        } catch (Exception $ex){
            Mage::Log('Error validate status Error page(Block): '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $response;
    }
}
