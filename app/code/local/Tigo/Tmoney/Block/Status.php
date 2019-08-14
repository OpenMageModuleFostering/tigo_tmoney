<?php 
class Tigo_Tmoney_Block_Status extends Mage_Core_Block_Template{
    protected $_methodCode = 'tmoney';
	
    protected function _construct(){
        parent::_construct();
        $this->setTemplate('tigo/tmoney/status.phtml');
    }
    
    /*
     * get data of URL
     */
    public function getDataP(){
        $data = array();
        try {
            $data['orderId'] = Mage::app()->getRequest()->getParam('orderId');
            $data['message'] = Mage::app()->getRequest()->getParam('mensaje');
            $data['codRes'] = Mage::app()->getRequest()->getParam('codRes');
            $data['transaccion'] = Mage::app()->getRequest()->getParam('transaccion');
        } catch (Exception $ex) {
            Mage::Log('Error get data status block: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $data;
    }
    /*
     * Verify
     */
    public function verifyS($id){
        $return = array();
        try {
            $tigomoney = Mage::getModel('tmoney/paymethod');
            $return = $tigomoney->verify($id);
        } catch (Exception $ex) {
            Mage::Log('Error get verify status block: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        return $return;
    }
    
    /**/
    function getIDOrder(){
        $session = $this->_getCheckout();
        $orderIncrementId =  $session->getLastRealOrderId() ;
        return $orderIncrementId;
    }
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
    /*
     * Timer
     */
    function getTimer(){
        $tigomoney = Mage::getModel('tmoney/paymethod');
        $timer = trim($tigomoney->getTimer());
        $result = $timer;
        if($timer == ''){
            $result = 60;
        }
        return $result;
    }
}
