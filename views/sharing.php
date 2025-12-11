<h1 class="h4 mb-3">Home Sharing</h1>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form id="sharing-form" class="row g-3">
      <div class="col-md-6"><label class="form-label">ID Immobile</label><input class="form-control" name="immobile_id" required></div>
      <div class="col-md-6">
        <label class="form-label">Visibilità</label>
        <select class="form-select" name="visibilita">
          <option value="base">Base</option>
          <option value="dettagli">Dettagli</option>
        </select>
      </div>
      <div class="col-12"><button class="btn btn-primary" type="submit">Condividi</button></div>
    </form>
  </div>
</div>
<div class="card shadow-sm">
  <div class="card-body">
    <h2 class="h5">Immobili condivisi</h2>
    <div class="table-responsive">
      <table class="table table-striped" id="sharing-table">
        <thead><tr><th>Immobile</th><th>Visibilità</th><th>Agenzia</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
<script type="module">
import { initSharing } from '/public/assets/js/sharing.js';
initSharing();
</script>
