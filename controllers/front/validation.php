<?php
/**
* 2020 PAYTEF
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@paytef.es so we can send you a copy immediately.
*
*  @author    PAYTEF Sistemas S.L. <support@paytef.es>
*  @copyright 2020 PAYTEF Sistemas S.L.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PAYTEF Sistemas S.L.
*/

require_once(__DIR__."/../../src/puceutils.php");

class Ps_PaytefPuceValidationModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();
        if ($this->module->active == false) {
            die($this->resultCancel("Module inactive"));
        }

        try
        {
            $mac = $_POST['Op_MAC'];
            if(empty($mac)) {
                die($this->resultCancel("Empty MAC"));
            }
            $calcMac = PuceUtils::calculateConfirmMAC($_POST);
            if($mac != $calcMac) {
                die($this->resultCancel("Invalid MAC"));
            }

            $authID = $_POST['Op_Authorization_ID'];
            $parts = explode("-", $authID);
            if(sizeof($parts) != 2) {
                die($this->resultCancel("Invalid AuthorizationID"));
            }
            $customer_id = intval($parts[0]);
            $cart_id = intval($parts[1]);
            $amount = intval($_POST['Op_Merchant_Amount']) / 100.0;

            /*
            * Restore the context from the $cart_id & the $customer_id to process the validation properly.
            */
            Context::getContext()->cart = new Cart((int) $cart_id);
            Context::getContext()->customer = new Customer((int) $customer_id);
            Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
            Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

            $cart = Context::getContext()->cart;
            if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
                die($this->resultCancel("Invalid cart status"));
            }
            if (!Validate::isLoadedObject(Context::getContext()->customer)) {
                die($this->resultCancel("Invalid customer"));
            }
            $secure_key = Context::getContext()->customer->secure_key;
            $message = null;
            $module_name = $this->module->displayName;
            $currency_id = (int) Context::getContext()->currency->id;
            $this->getModule()->validateOrder($cart_id, PuceUtils::orderStatusPending(), $amount, $module_name, $message, array(), $currency_id, false, $secure_key);
            if ($this->getModule()->currentOrder) {
                $order = new Order((int)$this->getModule()->currentOrder);
                if ($order->current_state != PuceUtils::orderStatusPending()) {
                    die($this->resultCancel("Invalid order state = ".strval($order->current_state)));
                } else {
                    die($this->resultContinue("Order validated"));
                }
            } else {
                die($this->resultCancel("Order not created"));
            }
        } catch(Exception $ex) {
            PuceUtils::logException("PAYTEF.validation", $ex);
            die($this->resultCancel("Error: ". urlencode($ex->getMessage())));
        }
        exit;
    }

    /** @return PaymentModule */
    private function getModule() {
        return $this->module;
    }

    protected function resultContinue($reason) {
        return '<?xml version="1.0" encoding="UTF-8"?>
<puce-confirmation result="continue" reason="'.$reason.'" />';
    }

    protected function resultCancel($reason) {
        return '<?xml version="1.0" encoding="UTF-8"?>
<puce-confirmation result="cancel" reason="'.$reason.'" />';
    }
}
