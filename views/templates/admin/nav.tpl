{*
 * Pasek „Wersja niezarejestrowana”, pasek zakładek modułu i treść otwartej zakładki w ramce doklejonej do paska.
 * Klasy podwójne: Bootstrap 3 (li.active) i Bootstrap 4 (nav-link.active).
 * Zmienne: $ib_nav (label, href, active), $ib_content (gotowy HTML treści zakładki),
 * $ib_license_notice (gotowy HTML paska licencji albo '').
 *}
<div class="ib-admin">
  {$ib_license_notice nofilter}
  <ul class="nav nav-tabs ib-tabs">
    {foreach $ib_nav as $item}
      <li class="nav-item{if $item.active} active{/if}">
        <a class="nav-link{if $item.active} active{/if}" href="{$item.href|escape:'html':'UTF-8'}">{$item.label|escape:'html':'UTF-8':false}</a>
      </li>
    {/foreach}
  </ul>
  <div class="ib-tab-body">
    {$ib_content nofilter}
  </div>
</div>
