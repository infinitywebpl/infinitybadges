{*
 * Wersja demo: opis kryteriów zamiast edytora (kryteria są w pełnej wersji). Teksty z $ib_criteria to wyniki
 * trans() z kontrolera (już po htmlspecialchars), więc escapujemy je bez podwójnego kodowania encji.
 *}
<div class="ib-conditions ib-conditions-demo">
  <div class="alert alert-info">{$ib_criteria.demo|escape:'html':'UTF-8':false}</div>
  <p>{$ib_criteria.intro|escape:'html':'UTF-8':false}</p>
  <ul>
    {foreach $ib_criteria.items as $item}
      <li>{$item|escape:'html':'UTF-8':false}</li>
    {/foreach}
  </ul>
  <p class="help-block">{$ib_criteria.example|escape:'html':'UTF-8':false}</p>
  <p>
    {foreach $ib_criteria.buttons as $label}
      <button type="button" class="btn btn-default" disabled><i class="icon-plus"></i> {$label|escape:'html':'UTF-8':false} ({$ib_criteria.full_version|escape:'html':'UTF-8':false})</button>
    {/foreach}
  </p>
</div>
