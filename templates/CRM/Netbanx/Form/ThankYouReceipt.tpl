{if $trxn_id}
  <div class="content crm-netbanx-receipt">
    <pre>{$trxn_id|netbanx_civicrm_receipt}</pre>
  </div>
{/if}

{if $membership_amount and $is_separate_payment}
  <div class="content crm-netbanx-receipt-membership">
    <pre>{$membership_trx_id|netbanx_civicrm_receipt}</pre>
  </div>
{/if}
