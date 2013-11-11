{* civix automatic layout + added hlp links *}
{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label} {help id="netbanx-logo-help" file="CRM/Netbanx/Form/NetbanxSettings.hlp"}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
