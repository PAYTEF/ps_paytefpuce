{*
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
*}

<div class="panel">
	<div class="row ps_paytefpuce-header">
		<div class="col-xs-6 col-md-6 col-xl-4 text-left">
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/paytef_logo_name.png" id="payment-logo" /><br>
			
		</div>
		<div class="col-xs-6 col-md-6 text-left">
			<h4>{l s='PAYTEF PUCE Online payment processing' mod='ps_paytefpuce'}</h4>
			<ul class="ul-spaced">
				<li>
					<strong>{l s='Compatible with 3D Secure 1.0' mod='ps_paytefpuce'}:</strong>
					{l s='This provides an accurate validation of the user and secure transactions.' mod='ps_paytefpuce'}
				</li>
				
				<li>
					<strong>{l s='PCI Compliant' mod='ps_paytefpuce'}:</strong>
					{l s='This ensures the best security in the backend for the transactions.' mod='ps_paytefpuce'}
				</li>
			</ul>
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/verif_logos.png" id="Verified by Visa - Mastercard SecureCode" />
			<hr>
			{l s='For more information, call 91 140 7709' mod='ps_paytefpuce'} {l s='or' mod='ps_paytefpuce'} <a href="mailto:support@paytef.es">support@paytef.es</a>
		</div>
	</div>
</div>

<div class="panel">
	<div class="row">
		<div class="col-xs-12 text-left">
			<i class="icon icon-info-circle"></i> {l s='In order to use PAYTEF PUCE please complete the fields below and click Save.' mod='ps_paytefpuce'}
			{l s='Please contact PAYTEF to request the details for your business.' mod='ps_paytefpuce'}
		</div>
	</div>
</div>