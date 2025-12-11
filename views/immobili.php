<h1 class="title">Immobili</h1>
<div class="box">
  <form id="immobile-form" class="columns is-multiline">
    <div class="column is-half">
      <label class="label">Titolo</label>
      <input class="input" name="titolo" required>
    </div>
    <div class="column is-half">
      <label class="label">Prezzo</label>
      <input class="input" name="prezzo" type="number">
    </div>
    <div class="column is-full">
      <label class="label">Descrizione</label>
      <textarea class="textarea" name="descrizione"></textarea>
    </div>
    <div class="column is-half">
      <label class="label">Stato</label>
      <div class="select is-fullwidth">
        <select name="stato">
          <option value="disponibile">Disponibile</option>
          <option value="trattativa">Trattativa</option>
          <option value="venduto">Venduto</option>
        </select>
      </div>
    </div>
    <div class="column is-half">
      <label class="label">Immagine</label>
      <input class="input" type="file" name="immagine" accept="image/*">
    </div>
    <div class="column is-half">
      <label class="label">Planimetria</label>
      <input class="input" type="file" name="planimetria" accept="application/pdf,image/*">
    </div>
    <div class="column is-full">
      <button class="button is-primary" type="submit">Salva</button>
    </div>
  </form>
</div>
<table class="table is-fullwidth" id="immobili-table">
  <thead><tr><th>Titolo</th><th>Prezzo</th><th>Stato</th></tr></thead>
  <tbody></tbody>
</table>
<script type="module">
import { initImmobili } from '/public/assets/js/immobili.js';
initImmobili();
</script>
