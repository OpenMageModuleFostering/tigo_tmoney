<?php
/*$installer = $this;
$installer->startSetup();

$installer->addAttribute("customer", "nitname",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Nombre a Facturar del Equipo",
    "input"    => "text",
    "source"   => "",
    "visible"  => false,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""
));

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "nitname");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="checkout_register";
$used_in_forms[]="customer_account_create";
$used_in_forms[]="customer_account_edit";
$used_in_forms[]="adminhtml_checkout";
$attribute->setData("used_in_forms", $used_in_forms)
    ->setData("is_used_for_customer_segment", true)
    ->setData("is_system", 0)
    ->setData("is_user_defined", 0)
    ->setData("is_visible", 1)
    ->setData("sort_order", 100)
    ;
$attribute->save();


$installer->addAttribute("customer", "nitscan",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Imagen de CI",
    "input"    => "text",
    "source"   => "",
    "visible"  => false,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => ""
));

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "nitscan");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
$used_in_forms[]="checkout_register";
$used_in_forms[]="customer_account_create";
$used_in_forms[]="customer_account_edit";
$used_in_forms[]="adminhtml_checkout";
$attribute->setData("used_in_forms", $used_in_forms)
    ->setData("is_used_for_customer_segment", true)
    ->setData("is_system", 0)
    ->setData("is_user_defined", 0)
    ->setData("is_visible", 1)
    ->setData("sort_order", 100)
    ;
$attribute->save();
	
$installer->endSetup();
	 */