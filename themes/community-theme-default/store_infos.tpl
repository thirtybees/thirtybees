{* The following lines allow translations in back-office and has to stay commented

    {l s='Monday'}
    {l s='Tuesday'}
    {l s='Wednesday'}
    {l s='Thursday'}
    {l s='Friday'}
    {l s='Saturday'}
    {l s='Sunday'}
*}

{foreach from=$days_datas  item=one_day}
  <p>
    <strong>{l s=$one_day.day}: </strong> &nbsp;<span>{$one_day.hours}</span>
  </p>
{/foreach}

