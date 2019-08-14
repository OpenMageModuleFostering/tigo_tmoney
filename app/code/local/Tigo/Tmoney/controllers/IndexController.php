<?php
class Tigo_Tmoney_IndexController extends Mage_Core_Controller_Front_Action {
    public function indexAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        $block = $this->getLayout()->createBlock('tmoney/pagepaymod');
        $this->getLayout()->getBlock('content')->insert($block);
        $this->renderLayout();
    }
    /*
     * Success page when is the transaction in correct
     */
    public function successAction(){
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        $block = $this->getLayout()->createBlock('tmoney/success');
        $this->getLayout()->getBlock('content')->insert($block);
        
        try{
            
            $session = $this->_getCheckout();
            $orderIncrementId =  $session->getLastRealOrderId() ;
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            Mage::Log('Order id:'.$order->getId(), null, 'tigobusiness-tigomoney.log');
            
            $payment = $order->getPayment();
            $grandTotal = $order->getBaseGrandTotal();
            
            if($order->getId()){
                $tid = $order->getId();
            } else {
                $tid = -1 ;
            }
            //echo $tid;
            $payment->setTransactionId($tid)->setPreparedMessage("Payment Sucessfull Result:")->setIsTransactionClosed(0)->registerAuthorizationNotification($grandTotal);
            $order->save();
            try {
                if(!$order->canInvoice()){
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                }
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                if (!$invoice->getTotalQty()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                }
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                //Or you can use
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();
                $comment = $order->sendNewOrderEmail()->addStatusHistoryComment($message)->setIsCustomerNotified(true)->save();
            } catch (Mage_Core_Exception $e) {
                //Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::Log('Invoce status status order #'.$orderIncrementId.': '.$e->getMessage(), null, 'tigobusiness-tigomoney.log');
            }
        } catch (Exception $ex) {
            //Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            Mage::Log('Success page status: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        
        $this->renderLayout();
    }
    
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
    /*
     * Show Error data and have a button action to reacrete the order
     */
    public function backAction(){
        $this->loadLayout();
        $resp = Mage::app()->getRequest()->getParam('r');//(isset($_REQUEST['r']))? $_REQUEST['r'] : '';
        $excep = Mage::app()->getRequest()->getParam('e');//(isset($_REQUEST['e']))? $_REQUEST['e'] : '';
        $tigomoney = Mage::getModel('tmoney/paymethod');
        $response = $tigomoney->tigoverify($resp, $excep);
        //print_r($response);
        $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        $block = $this->getLayout()->createBlock('tmoney/back');
        $this->getLayout()->getBlock('content')->insert($block);
        try{
            $session = $this->_getCheckout();
            if ($session->getLastRealOrderId()) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
                if ($order->getId()) {
                    //Cancel order
                    if ($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
                        $errorMsg = 'Canceled';
                        $order->cancel();
                        $order->registerCancellation($errorMsg)->save();
                    }
                    $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                    //Return quote
                    if ($quote->getId()) {
                        $quote->setIsActive(1)->setReservedOrderId(NULL)->save();
                        $session->replaceQuote($quote);
                    }
                    $session->unsLastRealOrderId();
                }
            }
        } catch (Exception $ex){
            //Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            Mage::Log('Error page status: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        $this->renderLayout();
    }
    
    /*
     * Verify Status
     */
    public function verifyAction(){
        try{
            $orderId = Mage::app()->getRequest()->getParam('orderId');
            $tigomoney = Mage::getModel('tmoney/paymethod');
            $return = $tigomoney->verify($orderId);
            //$verify_msg = $tigomoney->verify($orderId);
            echo json_encode($return);
        } catch (Exception $ex){
            Mage::Log('Error verify status: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
    }
    
    /*
     * Status
     */
    public function statusAction(){
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        $block = $this->getLayout()->createBlock('tmoney/status');
        $this->getLayout()->getBlock('content')->insert($block);
        try{
            
        } catch (Exception $ex) {
            Mage::Log('Error status controller: '.$ex->getMessage(), null, 'tigobusiness-tigomoney.log');
        }
        $this->renderLayout();
    }
    
}
