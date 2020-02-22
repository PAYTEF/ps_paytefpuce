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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
require_once "src/puceutils.php";

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_PaytefPuce extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'ps_paytefpuce';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'PAYTEF';
        $this->controllers = array('validation', 'results', 'finish');
        $this->is_eu_compatible = 1;
        $this->need_instance = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('PAYTEF PUCE');
        $this->description = $this->l('PAYTEF PUCE online payment module');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall PAYTEF module? All PAYTEF the configuration will be removed for security.');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        $config = $this->getConfigFormValues();
        $missingConfig = array();
        foreach ($config as $name => $value) {
            if ($name != 'PS_PAYTEF_PUCE_MERCHANT_TERMINAL' && $name != 'PS_PAYTEF_PUCE_LIVE_MODE' && empty($value)) {
                $missingConfig[] = $name;
            }
        }
        if (sizeof($missingConfig) > 0) {
            $this->warning = $this->l('Please check configuration, missing parameters. Fields: ').join(", ", $missingConfig);
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }
        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submit'.$this->name)) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'PS_PAYTEF_PUCE_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('If disabled this will use testing servers. Make sure you enable this when going to production'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-terminal"></i>',
                        'desc' => $this->l('TCOD string provided by PAYTEF'),
                        'name' => 'PS_PAYTEF_PUCE_TCOD',
                        'label' => $this->l('TCOD'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-building"></i>',
                        'desc' => $this->l('Merchant code (Numeric)'),
                        'name' => 'PS_PAYTEF_PUCE_MERCHANT_CODE',
                        'label' => $this->l('Merchant Code'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Name to display for this merchant'),
                        'name' => 'PS_PAYTEF_PUCE_MERCHANT_NAME',
                        'label' => $this->l('Merchant Name'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-desktop"></i>',
                        'desc' => $this->l('Terminal number or empty if none was provided'),
                        'name' => 'PS_PAYTEF_PUCE_MERCHANT_TERMINAL',
                        'label' => $this->l('Merchant Terminal'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-university"></i>',
                        'desc' => $this->l('CSB number'),
                        'name' => 'PS_PAYTEF_PUCE_CSB',
                        'label' => $this->l('CSB'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Secret key provided for the MAC calculation'),
                        'name' => 'PS_PAYTEF_PUCE_MAC_KEY',
                        'label' => $this->l('MAC Secret key'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return Configuration::getMultiple(array(
            'PS_PAYTEF_PUCE_LIVE_MODE', 'PS_PAYTEF_PUCE_TCOD', 'PS_PAYTEF_PUCE_MERCHANT_CODE', 'PS_PAYTEF_PUCE_MERCHANT_NAME', 
            'PS_PAYTEF_PUCE_MERCHANT_TERMINAL', 'PS_PAYTEF_PUCE_CSB', 'PS_PAYTEF_PUCE_MAC_KEY'));
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($key == 'PS_PAYTEF_PUCE_LIVE_MODE') {
                Configuration::updateValue($key, boolval(Tools::getValue($key)));
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        try
        {
            $payment_options = [
                $this->getExternalPaymentOption($params['cart'])
            ];
        } catch (Exception $e) {
            PuceUtils::logException("PAYTEF. Error creating payment options", $e);
        }
        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @param Cart $cart */
    public function getExternalPaymentOption($cart)
    {
        $config = $this->getConfigFormValues();
        $amountCents = intval(PuceUtils::getCartTotal($cart)*100);
        $lang = Context::getContext()->language->iso_code;
        if (empty($lang)) {
            $lang = "ES";
        }
        $isTest = !boolval($config['PS_PAYTEF_PUCE_LIVE_MODE']);
        $merchantCode = strval($config["PS_PAYTEF_PUCE_MERCHANT_CODE"]);
        if ($isTest) {
            $merchantCode = "999999999";
        }
        $body = array (
                "Op_TCOD" => strval($config["PS_PAYTEF_PUCE_TCOD"]),
                "Op_Type" => 'V',
                "Op_Version" => '2',
                "Op_Merchant_MerchantCode" => $merchantCode,
                "Op_Merchant_MerchantName" => strval($config["PS_PAYTEF_PUCE_MERCHANT_NAME"]),
                "Op_Merchant_CSB" => strval($config["PS_PAYTEF_PUCE_CSB"]),
                "Op_Merchant_Terminal" => strval($config["PS_PAYTEF_PUCE_MERCHANT_TERMINAL"]),
                "Language" => $lang,
                "Op_Client_Confirm_Url" => $this->getCtlLink('validation'),
                "Op_Merchant_UrlOK" => $this->getCtlLink('finish'),
                "Op_Client_Result_Url" => $this->getCtlLink('results'),
                "Op_Authorization_ID" => strval($cart->id_customer)."-".strval($cart->id),
                "Op_Merchant_Amount" => strval($amountCents),

        );
        $body['Op_MAC'] = PuceUtils::calculateRequestMAC($body);

        $htmlInputs = array();
        foreach ($body as $name => $value) {
            $htmlInputs[$name] = ['name' => $name, 'type' =>'hidden', 'value' => $value];
        }

        $htmlInfo = $this->context->smarty->fetch('module:ps_paytefpuce/views/templates/front/payment_infos.tpl');

        $externalOption = new PaymentOption();
        $externalOption->setCallToActionText($this->l('Credit card payment with PAYTEF.'))
                       ->setAction("https://puce.paytef.es/auth_card".($isTest?"?env=preprod":""))
                       ->setInputs($htmlInputs)
                       ->setAdditionalInformation($htmlInfo);

        return $externalOption;
    }

    private function getCtlLink($ctlName) {
        return $this->context->link->getModuleLink($this->name, $ctlName, array(), true);
    }
}
