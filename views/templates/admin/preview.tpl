<div class="panel">
  <div class="panel-heading"><i class="icon-search"></i> {l s='Badge preview' d='Modules.Infinitybadges.Admin'}</div>
  <form method="get" action="index.php" class="form-inline ib-preview-form">
    <input type="hidden" name="controller" value="AdminInfinityBadgesPreview">
    <input type="hidden" name="token" value="{$ib.token|escape:'html':'UTF-8'}">
    <div class="form-group">
      <label for="ib_product">{l s='Product ID or reference' d='Modules.Infinitybadges.Admin'}</label>
      <input type="text" class="form-control" id="ib_product" name="ib_product" value="{$ib.query|escape:'html':'UTF-8'}" required>
    </div>
    <div class="form-group">
      <label for="ib_lang">{l s='Language' d='Modules.Infinitybadges.Admin'}</label>
      <select class="form-control" id="ib_lang" name="ib_lang">
        {foreach $ib.languages as $language}
          <option value="{$language.id|intval}"{if $language.id == $ib.id_lang} selected{/if}>{$language.name|escape:'html':'UTF-8'}</option>
        {/foreach}
      </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="icon-eye"></i> {l s='Show' d='Modules.Infinitybadges.Admin'}</button>
  </form>
</div>

{if $ib.error}
  <div class="alert alert-warning">{$ib.error|escape:'html':'UTF-8'}</div>
{/if}

{if $ib.analysis}
  {assign var=an value=$ib.analysis}
  <div class="panel">
    <div class="panel-heading">
      <i class="icon-bar-chart"></i>
      {l s='Rule matches' d='Modules.Infinitybadges.Admin'}:
      <a href="{$an.edit_href|escape:'html':'UTF-8'}">#{$an.id_rule|intval} {$an.name|escape:'html':'UTF-8'}</a>
      {if !$an.active}<span class="label label-default">{l s='inactive' d='Modules.Infinitybadges.Admin'}</span>{/if}
    </div>
    <table class="table ib-analysis">
      <tbody>
        <tr><td>{l s='Active products in the shop' d='Modules.Infinitybadges.Admin'}</td><td>{$an.total|intval}</td></tr>
        <tr class="success"><td><strong>{l s='Qualified (get the badge)' d='Modules.Infinitybadges.Admin'}</strong></td><td><strong>{$an.qualified|intval}</strong></td></tr>
        {foreach $an.groups as $group}
          <tr><td class="ib-analysis-indent">{$group.label|escape:'html':'UTF-8'}</td><td>{$group.count|intval}</td></tr>
        {/foreach}
        <tr><td>{l s='Excluded by "Always exclude"' d='Modules.Infinitybadges.Admin'}</td><td>{$an.excluded|intval}</td></tr>
        <tr><td>{l s='No group matches' d='Modules.Infinitybadges.Admin'}</td><td>{$an.no_group|intval}</td></tr>
        <tr>
          <td>{if $an.is_feature}{l s='Matching criteria, but no visible feature value' d='Modules.Infinitybadges.Admin'}{else}{l s='Matching criteria, but empty badge text' d='Modules.Infinitybadges.Admin'}{/if}</td>
          <td>{$an.no_label|intval}</td>
        </tr>
      </tbody>
    </table>
    <p class="help-block">
      {l s='Counted for active products with the same logic as on the front office. Validity dates, duplicates and limits are not taken into account.' d='Modules.Infinitybadges.Admin'}
      ({$an.time|escape:'html':'UTF-8'} s)
    </p>
    <div class="row">
      <div class="col-lg-6">
        <h4>{l s='Sample of qualified products' d='Modules.Infinitybadges.Admin'}</h4>
        <ul>
          {foreach $an.sample_qualified as $item}
            <li><a href="{$item.href|escape:'html':'UTF-8'}">#{$item.id|intval} {$item.name|escape:'html':'UTF-8'}</a></li>
          {foreachelse}
            <li class="text-muted">—</li>
          {/foreach}
        </ul>
      </div>
      <div class="col-lg-6">
        <h4>{l s='Sample of excluded products' d='Modules.Infinitybadges.Admin'}</h4>
        <ul>
          {foreach $an.sample_excluded as $item}
            <li><a href="{$item.href|escape:'html':'UTF-8'}">#{$item.id|intval} {$item.name|escape:'html':'UTF-8'}</a></li>
          {foreachelse}
            <li class="text-muted">—</li>
          {/foreach}
        </ul>
      </div>
    </div>
  </div>
{/if}

{if $ib.candidates}
  <div class="panel">
    <div class="panel-heading">{l s='Several products have this reference' d='Modules.Infinitybadges.Admin'}</div>
    <ul>
      {foreach $ib.candidates as $candidate}
        <li><a href="{$candidate.href|escape:'html':'UTF-8'}">#{$candidate.id|intval} {$candidate.name|escape:'html':'UTF-8'}</a></li>
      {/foreach}
    </ul>
  </div>
{/if}

{if $ib.product}
  <div class="panel">
    <div class="panel-heading">
      <i class="icon-cube"></i>
      <a href="{$ib.product.edit_href|escape:'html':'UTF-8'}" target="_blank" rel="noopener">#{$ib.product.id|intval} {$ib.product.name|escape:'html':'UTF-8'}</a>
    </div>
    {if !$ib.product.active}
      <div class="alert alert-warning">{l s='The product is inactive: it gets no badges on the front office. The evaluation below is shown for information.' d='Modules.Infinitybadges.Admin'}</div>
    {/if}
    <div class="row">
      <div class="col-lg-6">
        <dl class="dl-horizontal ib-product-data">
          <dt>{l s='Default category' d='Modules.Infinitybadges.Admin'}</dt>
          <dd>{$ib.product.default_category|escape:'html':'UTF-8'}</dd>
          <dt>{l s='Assigned categories' d='Modules.Infinitybadges.Admin'}</dt>
          <dd>{$ib.product.categories_count|intval}</dd>
          <dt>{l s='Manufacturer' d='Modules.Infinitybadges.Admin'}</dt>
          <dd>{if $ib.product.manufacturer}{$ib.product.manufacturer|escape:'html':'UTF-8'}{else}—{/if}</dd>
          <dt>{l s='Suppliers' d='Modules.Infinitybadges.Admin'}</dt>
          <dd>{if $ib.product.suppliers}{$ib.product.suppliers|escape:'html':'UTF-8'}{else}—{/if}</dd>
          <dt>{l s='Base price (tax excl.)' d='Modules.Infinitybadges.Admin'}</dt>
          <dd>{$ib.product.price|escape:'html':'UTF-8'}</dd>
          <dt>{l s='Discount' d='Modules.Infinitybadges.Admin'}</dt>
          <dd>
            {if $ib.product.discount}{$ib.product.discount|escape:'html':'UTF-8'}{else}—{/if}
            <span class="help-block">{l s='Default context: visitor group, default currency and country, default combination.' d='Modules.Infinitybadges.Admin'}</span>
          </dd>
        </dl>
      </div>
      <div class="col-lg-6">
        <dl class="dl-horizontal ib-product-data">
          {foreach $ib.product.features as $feature}
            <dt title="{$feature.name|escape:'html':'UTF-8'}">{$feature.name|escape:'html':'UTF-8'}</dt>
            <dd>{$feature.values|escape:'html':'UTF-8'}</dd>
          {foreachelse}
            <dt>{l s='Features' d='Modules.Infinitybadges.Admin'}</dt>
            <dd>—</dd>
          {/foreach}
        </dl>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-heading"><i class="icon-tags"></i> {l s='Badges' d='Modules.Infinitybadges.Admin'}</div>
    {if $ib.badges}
      <ul class="product-flags ib-preview-flags">
        {foreach $ib.badges as $badge}
          <li>
            <span class="product-flag infinitybadge ib-style-{$badge.id_style|intval} ib-rule-{$badge.id_rule|intval}{if $badge.image} ib-image{/if}"
                  title="{$badge.text|escape:'html':'UTF-8'}">{$badge.text|escape:'html':'UTF-8'}</span>
            {if $badge.hide_desktop}<small class="text-muted">{l s='mobile only' d='Modules.Infinitybadges.Admin'}</small>{/if}
            {if $badge.hide_mobile}<small class="text-muted">{l s='desktop only' d='Modules.Infinitybadges.Admin'}</small>{/if}
          </li>
        {/foreach}
      </ul>
      <p class="help-block">{l s='Badges with the base style use the flag style of the shop theme; here they are shown in grey.' d='Modules.Infinitybadges.Admin'}</p>
    {else}
      <p class="text-muted">{l s='No badges for this product.' d='Modules.Infinitybadges.Admin'}</p>
    {/if}
  </div>

  <div class="panel">
    <div class="panel-heading"><i class="icon-list"></i> {l s='Rules' d='Modules.Infinitybadges.Admin'}</div>
    {if $ib.trace}
      <table class="table">
        <thead>
          <tr>
            <th>{l s='Rule' d='Modules.Infinitybadges.Admin'}</th>
            <th>{l s='Status' d='Modules.Infinitybadges.Admin'}</th>
            <th>{l s='Badge text' d='Modules.Infinitybadges.Admin'}</th>
            <th>{l s='Reason' d='Modules.Infinitybadges.Admin'}</th>
          </tr>
        </thead>
        <tbody>
          {foreach $ib.trace as $item}
            <tr>
              <td><a href="{$item.edit_href|escape:'html':'UTF-8'}">#{$item.id_rule|intval} {$item.name|escape:'html':'UTF-8'}</a></td>
              <td><span class="label label-{$item.status_class|escape:'html':'UTF-8'}">{$item.status_label|escape:'html':'UTF-8'}</span></td>
              <td>
                {$item.label|escape:'html':'UTF-8'}
                {if $item.fallback}<br><small class="text-warning">{l s='Missing translation, default language used.' d='Modules.Infinitybadges.Admin'}</small>{/if}
              </td>
              <td>
                {foreach $item.reasons as $reason}
                  <div>{$reason|escape:'html':'UTF-8'}</div>
                {/foreach}
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    {else}
      <p class="text-muted">{l s='There are no rules yet.' d='Modules.Infinitybadges.Admin'}</p>
    {/if}
  </div>
{/if}
