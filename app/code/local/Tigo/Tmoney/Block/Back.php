<?php 
class Tigo_Tmoney_Block_Back extends Mage_Core_Block_Template{
	protected $_methodCode = 'tmoney';
	
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('tigo/tmoney/back.phtml');
    }
    public function validate(){
        $response = array();
        
        try{
            $id = Mage::app()->getRequest()->getParam('orderID');//(isset($_REQUEST['r']))? $_REQUEST['r'] : '';
            $id_error = Mage::app()->getRequest()->getParam('motivo');
            $tigomoney = Mage::getModel('tmoney/paymethod');
            $message = $tigomoney->getError($id_error);
            $response['msg'] = (isset($message['msg_show']))? $message['msg_show'] : Mage::app()->getRequest()->getParam('msg');
            $response['response'] = $tigomoney->verify($id);
        } catch (Exception $ex){
            Mage::Log('Error validate status Error page(Block): '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $response;
    }
    
}
