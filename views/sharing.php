<h1 class="title">Home Sharing</h1>
<div class="box">
  <form id="sharing-form" class="columns is-multiline">
    <div class="column is-half"><label class="label">ID Immobile</label><input class="input" name="immobile_id" required></div>
    <div class="column is-half">
      <label class="label">Visibilità</label>
      <div class="select is-fullwidth">
        <select name="visibilita">
          <option value="base">Base</option>
          <option value="dettagli">Dettagli</option>
        </select>
      </div>
    </div>
    <div class="column is-full"><button class="button is-primary" type="submit">Condividi</button></div>
  </form>
</div>
<div class="box">
  <h2 class="subtitle">Immobili condivisi</h2>
  <table class="table is-fullwidth" id="sharing-table">
    <thead><tr><th>Immobile</th><th>Visibilità</th><th>Agenzia</th></tr></thead>
    <tbody></tbody>
  </table>
</div>
<script type="module">
import { initSharing } from '/public/assets/js/sharing.js';
initSharing();
</script>
