<?php 
class Tigo_Tmoney_Block_Info extends Mage_Payment_Block_Info{

    protected function _prepareSpecificInformation($transport = null){
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        
        return $transport;
    }
	/*protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();
		//print_r($info);
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        $transport->addData(array('test'=>'test'));
        return $transport;
    }
/*
    protected function _prepareSpecificInformation($transport = null)
    {
        
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();
        $data['name'] = 'tester';
        return $transport->setData(array_merge($data, $transport->getData()));
    }
*/
}
