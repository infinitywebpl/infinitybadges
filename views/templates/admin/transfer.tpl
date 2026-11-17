{* Wersja demo: eksport i import widoczne, ale nieaktywne (pełna wersja). Skrypt wyłącza też przełącznik trybu debug. *}
<div class="panel" id="ib-transfer">
  <div class="panel-heading"><i class="icon-exchange"></i> {l s='Export and import' d='Modules.Infinitybadges.Admin'}</div>

  <div class="alert alert-info">{l s='Demo version: export and import of the configuration work in the full version.' d='Modules.Infinitybadges.Admin'}</div>

  <p>{l s='Export saves all rules (with texts and criteria) and styles to a JSON file.' d='Modules.Infinitybadges.Admin'}</p>
  <button type="button" class="btn btn-default" disabled><i class="icon-download"></i> {l s='Export configuration' d='Modules.Infinitybadges.Admin'}</button>

  <hr>

  <div class="form-group">
    <label for="ib_import_file">{l s='JSON file' d='Modules.Infinitybadges.Admin'}</label>
    <input type="file" id="ib_import_file" accept=".json,application/json" disabled>
  </div>
  <div class="radio">
    <label><input type="radio" name="ib_import_mode" value="append" checked disabled> {l s='Add to existing rules (styles with the same name are reused)' d='Modules.Infinitybadges.Admin'}</label>
  </div>
  <div class="radio">
    <label><input type="radio" name="ib_import_mode" value="replace" disabled> {l s='Replace all rules and styles' d='Modules.Infinitybadges.Admin'}</label>
  </div>
  <button type="button" class="btn btn-primary" disabled><i class="icon-upload"></i> {l s='Import' d='Modules.Infinitybadges.Admin'}</button>
</div>
<script>
  document.querySelectorAll('input[name="INFINITYBADGES_DEBUG"]').forEach(function (input) {
    input.disabled = true;
  });
</script>
