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

class Ps_PaytefPuceFinishModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        try
        {
            $mac = $_POST['Op_MAC'];
            if(empty($mac)) {
                return $this->displayError('An error occurred while validating your payment. Please contact the merchant for more information or try again');
            }
            $calcMac = PuceUtils::calculateResultsMAC($_POST);
            if($mac != $calcMac) {
                return $this->displayError('An error occurred while validating your payment. Please contact the merchant for more information or try again.');
            }

            $authID = $_POST['Op_Authorization_ID'];
            $parts = explode("-", $authID);
            if(sizeof($parts) != 2) {
                return $this->displayError('An error occurred while validating your payment data. Please contact the merchant for more information or try again');
            }
            $customer_id = intval($parts[0]);
            $cart_id = intval($parts[1]);

            Context::getContext()->cart = new Cart((int) $cart_id);
            Context::getContext()->customer = new Customer((int) $customer_id);
            Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
            Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

            $resultCode = $_POST["Op_Result_Code"];
            $approved = ($resultCode == "X" || $resultCode == "Z");

            $order = Order::getByCartId($cart_id);
            $has_order = !!$order && Validate::isLoadedObject($order);
            
            if($approved) {
                if ($has_order) {
                    $module_id = $this->module->id;
                    $secure_key = Context::getContext()->customer->secure_key;
                    Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart_id . '&id_module=' . $module_id . '&id_order=' . $order->id . '&key=' . $secure_key);
                } else {
                    return $this->displayError('Payment was approved but the order seems to be in an invalid state. Please contact the merchant to resolve this issue.');
                }
            } else {
                $this->errors[] = $this->module->l("Result code: ").$resultCode;
                if (!empty($_POST["Op_Result_Reason"])) {
                    $this->errors[] = $this->module->l("Reason: ").strval($_POST["Op_Result_Reason"]);
                }
                return $this->displayError('Provided payment method seems to have been declined, you can try again.');
            }
        } catch(Exception $ex) {
            PuceUtils::logException("PAYTEF.finish", $ex);
            return $this->displayError('An unexpected error occurred. Please contact the merchant for more information or try again.');
        }
    }

    protected function displayError($message)
    {
        $this->context->smarty->assign([
            'go_back' => $this->context->link->getPageLink('order', null, null, 'step=3'),
            'message' => $this->module->l($message),
            'errors' => $this->errors
        ]);
        return $this->setTemplate('module:ps_paytefpuce/views/templates/front/error.tpl');
    }
}
