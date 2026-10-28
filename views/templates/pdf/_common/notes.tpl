{strip}
{if $budget.notes}
  <br><br>
  <table style="width: 100%;" cellpadding="5">
    <tr>
      <td style="width: 100%; border: 0.3pt solid #cccccc; font-size: 8pt;">
        <b>{l s='Notes' mod='carttobudget'}</b><br>
        {$budget.notes|escape:'html':'UTF-8'|nl2br}
      </td>
    </tr>
  </table>
{/if}
{/strip}
