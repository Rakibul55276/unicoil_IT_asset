<?php
$request = $data["request"];
$selectedItems = [];
foreach ($request["items"] as $item) {
    $selectedItems[(string) $item["assetKat"]] = (int) $item["quantity"];
}
?>
<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>
  <form action="<?= BASEURL ?>/Request/update/<?= (int) $request["request_id"] ?>" method="post" id="equipment-request-edit-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"], ENT_QUOTES, "UTF-8") ?>">
    <div class="widget-box">
      <div class="widget-header"><h4 class="widget-title"><i class="fa fa-pencil"></i> Edit Requested Equipment</h4></div>
      <div class="widget-body"><div class="widget-main">
        <div class="alert alert-info">
          You can change these items until the first approval. After anyone approves the request,
          editing is automatically locked.
        </div>
        <div class="form-group">
          <label for="required_date">Required Date</label>
          <input type="date" name="required_date" id="required_date" class="form-control"
            value="<?= htmlspecialchars((string) ($request["required_date"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
        </div>
        <h4 class="header blue smaller"><i class="fa fa-desktop"></i> Requested Equipment Types</h4>
        <div class="row">
          <?php foreach ($data["categories"] as $category):
              $categoryCode = (string) $category["assetKat"];
              $selectedQuantity = $selectedItems[$categoryCode] ?? 0;
              $isSelected = $selectedQuantity > 0;
              ?>
          <div class="col-sm-6 col-md-4"><div class="well well-sm equipment-choice" style="min-height:105px">
            <label style="display:block">
              <input type="checkbox" class="equipment-check" value="<?= htmlspecialchars($categoryCode) ?>" <?= $isSelected ? "checked" : "" ?>>
              <strong><?= htmlspecialchars($category["Description"]) ?></strong>
            </label>
            <small class="text-muted"><?= (int) $category["available_count"] ?> currently in stock</small>
            <div class="quantity-wrap <?= $isSelected ? "" : "hide" ?>" style="margin-top:8px">
              <input type="hidden" class="category-input" name="category[]" value="<?= htmlspecialchars($categoryCode) ?>" <?= $isSelected ? "" : "disabled" ?>>
              <input type="number" class="form-control quantity-input" name="quantity[]" min="1" max="8"
                value="<?= $isSelected ? $selectedQuantity : 1 ?>" <?= $isSelected ? "" : "disabled" ?>
                aria-label="Quantity for <?= htmlspecialchars($category["Description"]) ?>">
            </div>
          </div></div>
          <?php endforeach; ?>
        </div>
        <p class="help-block">Maximum 8 assets per request. Selected: <strong id="equipment-total">0</strong> / 8</p>
        <div id="equipment-error" class="text-danger hide" style="margin-bottom:10px"></div>
        <div class="text-right">
          <a href="<?= BASEURL ?>/Request/details/<?= (int) $request["request_id"] ?>" class="btn btn-default">Cancel</a>
          <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Changes</button>
        </div>
      </div></div>
    </div>
  </form>
</div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('equipment-request-edit-form');
  var equipmentError = document.getElementById('equipment-error');
  var equipmentTotal = document.getElementById('equipment-total');
  var submitButton = form.querySelector('button[type="submit"]');

  function validateEquipment() {
    var selected = document.querySelectorAll('.equipment-check:checked').length;
    var total = 0;
    Array.prototype.forEach.call(document.querySelectorAll('.quantity-input:not(:disabled)'), function (input) {
      total += parseInt(input.value, 10) || 0;
    });
    equipmentTotal.textContent = total;
    var valid = selected > 0 && total >= 1 && total <= 8;
    equipmentError.textContent = selected === 0
      ? 'Select at least one equipment type.'
      : 'A request can include a maximum of 8 assets.';
    equipmentError.classList.toggle('hide', valid);
    submitButton.disabled = !valid;
    return valid;
  }

  Array.prototype.forEach.call(document.querySelectorAll('.equipment-check'), function (checkbox) {
    checkbox.addEventListener('change', function () {
      var wrap = this.closest('.equipment-choice').querySelector('.quantity-wrap');
      wrap.classList.toggle('hide', !this.checked);
      Array.prototype.forEach.call(wrap.querySelectorAll('input'), function (input) {
        input.disabled = !checkbox.checked;
      });
      validateEquipment();
    });
  });
  Array.prototype.forEach.call(document.querySelectorAll('.quantity-input'), function (input) {
    input.addEventListener('input', validateEquipment);
  });
  form.addEventListener('submit', function (event) {
    if (!validateEquipment()) event.preventDefault();
  });
  validateEquipment();
});
</script>
