{*
 * Zakładka „Licencja”, wariant demo: bez klucza i bez statusu licencji. Wartości z $ib_license.html i features
 * to gotowy HTML z kontrolera (tłumaczenia po htmlspecialchars), więc idą bez filtra. Escapujemy tu tylko adresy.
 *}
<div class="panel ib-license">
  <div class="panel-heading"><i class="icon-certificate"></i> {$ib_license.html.header nofilter}</div>

  <p>{$ib_license.html.demo nofilter}</p>

  <p><strong>{$ib_license.html.full nofilter}</strong></p>
  <ul>
    {foreach $ib_license.features as $feature}
      <li>{$feature nofilter}</li>
    {/foreach}
  </ul>

  <p>{$ib_license.html.contact nofilter}</p>

  <p class="ib-license-form">
    <a href="{$ib_license.back_url|escape:'html':'UTF-8'}" class="btn btn-default">
      <i class="icon-arrow-left"></i> {$ib_license.html.back nofilter}
    </a>
  </p>

  <div class="alert alert-info ib-license-more">
    <p><strong>{$ib_license.html.more_title nofilter}</strong></p>
    <p>{$ib_license.html.more_github nofilter}<br>{$ib_license.html.more_site nofilter}</p>
    <p>
      <a href="{$ib_license.github_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-default">{$ib_license.html.more_github_button nofilter}</a>
      <a href="{$ib_license.site_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-default">infinityweb.pl</a>
    </p>
  </div>

  <p class="help-block">{$ib_license.html.footer nofilter}</p>
</div>
