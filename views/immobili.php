<h1 class="h4 mb-3">Immobili</h1>
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form id="immobile-form" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Titolo</label>
        <input class="form-control" name="titolo" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Prezzo</label>
        <input class="form-control" name="prezzo" type="number">
      </div>
      <div class="col-12">
        <label class="form-label">Descrizione</label>
        <textarea class="form-control" name="descrizione"></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Stato</label>
        <select class="form-select" name="stato">
          <option value="disponibile">Disponibile</option>
          <option value="trattativa">Trattativa</option>
          <option value="venduto">Venduto</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Immagine</label>
        <input class="form-control" type="file" name="immagine" accept="image/*">
      </div>
      <div class="col-md-6">
        <label class="form-label">Planimetria</label>
        <input class="form-control" type="file" name="planimetria" accept="application/pdf,image/*">
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">Salva</button>
      </div>
    </form>
  </div>
</div>
<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped" id="immobili-table">
        <thead><tr><th>Titolo</th><th>Prezzo</th><th>Stato</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
<script type="module">
import { initImmobili } from '/public/assets/js/immobili.js';
initImmobili();
</script>
