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
{extends "$layout"}

{block name="content"}
	<section class="container">
		<div class="row">
			<div class="col">
				<h3>{l s='Payment could not be processed' mod='ps_paytefpuce'}:</h3>
				<p>{$message}</p>
				<ul class="alert alert-danger">
					{foreach from=$errors item='error'}
						<li>{$error|escape:'htmlall':'UTF-8'}.</li>
					{/foreach}
				</ul>
			</div>
		</div>
		<div class="row">
			<div class="col text-center">
				<a class="btn btn-primary" href="{$go_back}" role="button">{l s='Try again' mod='ps_paytefpuce'}</a>
			</div>
		</div>
	</section>
{/block}