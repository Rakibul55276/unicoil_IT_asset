<div class="page-content">
  <style>
    .asset-upload-preview {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 220px;
      height: 135px;
      margin: 8px 0;
      border: 1px solid #d5dbe1;
      border-radius: 6px;
      background: #f7f8fa;
      overflow: hidden
    }

    .asset-upload-preview img {
      width: 100%;
      height: 100%;
      object-fit: contain
    }

    .serial-number-list .form-group {
      margin-bottom: 8px
    }
  </style>
  <div class="container-fluid">
    <div class="row">
      <div class="col-xs-12"><?php Flasher::msgInfo(); ?></div>
    </div>
    <?php
    $asset = $data["asset"] ?? [];

    // Treat the page as edit mode only when a real existing asset number is present.
    // The create page may still preload receipt/vendor/form values in $asset,
    // so !empty($asset) would incorrectly hide the batch asset count fields.
    $existingAssetNumber = trim((string) ($asset["assetNum"] ?? ""));
    $editing = $existingAssetNumber !== "";
    ?>
    <form action="<?= $data[
        "post_url"
    ] ?>" method="post" enctype="multipart/form-data" id="asset-receive-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
          $data["csrf"],
          ENT_QUOTES,
          "UTF-8",
      ) ?>">

      <div class="widget-box">
        <div class="widget-header">
          <h4 class="widget-title"><i class="fa fa-truck"></i> Receipt Details</h4>
        </div>
        <div class="widget-body">
          <div class="widget-main">
            <div class="row">
              <div class="col-md-4 form-group"><label>SAP GRN Number <span
                    class="red">*</span></label><input
                  class="form-control" name="receiptNo" maxlength="30"
                  placeholder="SAP###" required value="<?= htmlspecialchars(
              $asset["receipt_no"] ?? "",
          ) ?>"></div>
              <?php $receivedDate = trim((string) ($asset["received_date"] ?? "")) ?: date("Y-m-d"); ?>
              <div class="col-md-4 form-group"><label>Received Date <span
                    class="red">*</span></label><input type="date" class="form-control"
                  name="receivedDate" value="<?= htmlspecialchars(
              $receivedDate,
              ENT_QUOTES,
              "UTF-8",
          ) ?>" required></div>
              <div class="col-md-4 form-group"><label>Supplier / Vendor <span
                    class="red">*</span></label><select class="form-control searchable-select"
                  name="vendorId" data-placeholder="Search supplier name" required>
                  <option value="">Search and select supplier</option><?php foreach (
              $data["vendors"]
              as $vendor
          ): ?><option value="<?= (int) $vendor["vendor_id"] ?>" <?= (string) ($asset[
    "vendor_id"
] ?? "") === (string) $vendor["vendor_id"]
    ? "selected"
    : "" ?>><?= htmlspecialchars(
    $vendor["vendor_name"] .
        (!empty($vendor["contact_person"]) ? " - " . $vendor["contact_person"] : ""),
) ?></option><?php endforeach; ?>
                </select>
                <p class="help-block"><a href="<?= BASEURL ?>/Vendor/add" target="_blank"><i
                      class="fa fa-plus"></i> Add a new vendor</a> if the supplier is not listed.
                </p>
              </div>
              <div class="col-md-4 form-group"><label>PO Number <span
                    class="red">*</span></label><input class="form-control"
                  name="poNumber" maxlength="50" required value="<?= htmlspecialchars(
              $asset["po_number"] ?? "",
          ) ?>"></div>
              <div class="col-md-4 form-group"><label>Invoice Number <span
                    class="red">*</span></label><input
                  class="form-control" name="invoiceNumber" maxlength="50" required value="<?= htmlspecialchars(
              $asset["invoice_number"] ?? "",
          ) ?>"></div>
              <div class="col-md-4 form-group"><label>Invoice PDF</label><input type="file"
                  id="invoice-pdf-input" class="form-control" name="invoicePdf"
                  accept="application/pdf,.pdf">
                <p class="help-block"><span id="invoice-pdf-status">No PDF selected</span> &middot;
                  PDF format, maximum 10 MB.<?php if (
              !empty($asset["invoice_pdf"])
          ): ?> <a href="<?= BASEURL ?>/Asset/downloadInvoice/<?= rawurlencode(
    basename($asset["invoice_pdf"]),
) ?>" target="_blank" rel="noopener"><i class="fa fa-file-pdf-o"></i> View current
                    invoice</a><?php endif; ?></p>
              </div>
              <div class="col-md-4 form-group"><label>Entry Notes / Remarks <span
                    class="red">*</span></label><input
                  class="form-control" name="notes" maxlength="500" required value="<?= htmlspecialchars(
              $asset["receipt_notes"] ?? "",
          ) ?>"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="space-12"></div>
      <div class="widget-box">
        <div class="widget-header">
          <h4 class="widget-title"><i class="fa fa-laptop"></i> Asset Details</h4>
        </div>
        <div class="widget-body">
          <div class="widget-main">
            <div class="row">
              <div class="col-md-4 form-group"><label>Asset Number</label><input
                  class="form-control" id="asset-number-preview" name="assetNum" maxlength="30"
                  placeholder="Select a category; number is generated on save" value="<?= htmlspecialchars(
              $asset["assetNum"] ?? "",
          ) ?>" readonly>
                <p class="help-block"><?= $editing
    ? "Existing asset numbers are not changed."
    : "Generated from the category prefix, such as LT0001 or DT0001." ?></p>
              </div>
              <div class="col-md-4 form-group"><label>Category <span
                    class="red">*</span></label><select class="form-control" id="asset-category"
                  name="assetKat" required>
                  <option value="">Select category</option><?php foreach (
              $data["asetkat"]
              as $row
          ): ?><option value="<?= htmlspecialchars(
    $row["assetKat"],
) ?>" data-prefix="<?= htmlspecialchars($row["asset_prefix"] ?? "") ?>" <?= ($asset["assetKat"] ??
    "") ==
$row["assetKat"]
    ? "selected"
    : "" ?>><?= htmlspecialchars($row["Description"]) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-4 form-group"><label>Description <span
                    class="red">*</span></label><textarea class="form-control" name="desc"
                  maxlength="255" rows="3" required><?= htmlspecialchars(
              $asset["Description"] ?? "",
          ) ?></textarea></div>
              <?php if ($editing): ?>
              <input type="hidden" name="assetCount" value="1">
              <div class="col-md-4 col-md-offset-4 form-group"><label>Serial Number <span
                    class="red">*</span></label><input
                  class="form-control" name="serialNumber" maxlength="100" required value="<?= htmlspecialchars(
              $asset["serial_number"] ?? "",
          ) ?>"></div>
              <?php else: ?>
              <div class="col-md-4 col-md-offset-4 form-group"><label>Number of Individual Assets <span
                    class="red">*</span></label><input type="number" class="form-control"
                  name="assetCount" id="asset-count" min="1" max="100" step="1" value="1" required>
                <p class="help-block">Creates a separate asset number for every unit under this
                  receipt.</p>
              </div>
              <div class="col-md-12 form-group"><label>Individual Serial Numbers
                  <small class="text-muted">(optional)</small></label>
                <div id="serial-number-list" class="row serial-number-list">
                  <div class="col-sm-4 form-group"><input class="form-control"
                      name="serialNumbers[]" maxlength="100"
                      placeholder="Serial number for asset 1"></div>
                </div>
                <p class="help-block">Enter serial numbers when available. Any entered serial
                  number must be unique within this receipt.</p>
              </div>
              <?php endif; ?>
              <div class="col-md-4 form-group"><label>Manufacturer <span
                    class="red">*</span></label><input
                  class="form-control" name="manufacturer" maxlength="100" required value="<?= htmlspecialchars(
              $asset["manufacturer"] ?? "",
          ) ?>"></div>
              <div class="col-md-4 form-group"><label>Model <span
                    class="red">*</span></label><input class="form-control"
                  name="model" maxlength="100" required value="<?= htmlspecialchars(
              $asset["model"] ?? "",
          ) ?>"></div>
              <?php if ($editing): ?>
              <?php $currentCondition = strtoupper(
                  trim((string) ($asset["asset_condition"] ?? "NEW")),
              ); ?>
              <div class="col-md-4 form-group">
                <label>Current Condition <span class="red">*</span></label>
                <select class="form-control" id="current-asset-condition"
                  name="currentCondition" data-original-condition="<?= htmlspecialchars(
                      $currentCondition,
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>" required>
                  <?php foreach (
                      [
                          "NEW" => "New",
                          "GOOD" => "Good",
                          "DAMAGED" => "Damaged",
                      ]
                      as $conditionValue => $conditionLabel
                  ): ?>
                  <option value="<?= $conditionValue ?>" <?= $currentCondition === $conditionValue
    ? "selected"
    : "" ?>><?= $conditionLabel ?></option>
                  <?php endforeach; ?>
                </select>
                <p class="help-block">Updates the asset's current global condition only. Receipt
                  and return history remain unchanged.</p>
              </div>
              <div class="col-md-8 form-group">
                <label>Asset Update Reason <span class="red">*</span></label>
                <textarea class="form-control" name="updateReason" maxlength="500" rows="2"
                  placeholder="Explain why this asset record is being updated" required></textarea>
                <p class="help-block">Every changed field, its previous and new value, the user,
                  date and this reason are retained in the asset audit trail.</p>
              </div>
              <?php else: ?>
              <!-- Every newly received asset starts with one global NEW condition. -->
              <input type="hidden" name="conditionReceived" value="NEW">
              <input type="hidden" name="currentCondition" value="NEW">
              <?php endif; ?>
              <div class="col-md-4 form-group"><label>Stock Location <span
                    class="red">*</span></label><select class="form-control" name="location"
                  required>
                  <option value="">Select Location</option><?php foreach (
              $data["location"]
              as $row
          ): ?><option value="<?= htmlspecialchars($row["location"]) ?>" <?= ($asset["location"] ??
    "") ==
$row["location"]
    ? "selected"
    : "" ?>><?= htmlspecialchars($row["description"]) ?></option><?php endforeach; ?>
                </select></div>
              <input type="hidden" name="qty" value="<?= htmlspecialchars(
              $asset["quantity"] ?? "1",
          ) ?>">
              <div class="col-md-4 form-group"><label>Unit <span class="red">*</span></label><select
                  class="form-control" name="unit" required><?php foreach (
              $data["runit"]
              as $row
          ): ?><option value="<?= htmlspecialchars($row["unit"]) ?>" <?= ($asset["unit"] ??
    "Unit") ==
$row["unit"]
    ? "selected"
    : "" ?>><?= htmlspecialchars($row["unit"]) ?></option><?php endforeach; ?></select></div>
              <div class="col-md-4 form-group"><label>Purchase Date <span
                    class="red">*</span></label><input type="date"
                  class="form-control" id="purchase-date" name="purchaseDate" required value="<?= htmlspecialchars(
              $asset["purchase_date"] ?? "",
          ) ?>"></div>
              <div class="col-md-4 form-group"><label>Warranty Expiry
                  <small class="text-muted">(optional)</small></label><input type="date"
                  class="form-control" id="warranty-expiry" name="warrantyExpiry" value="<?= htmlspecialchars(
              $asset["warranty_expiry"] ?? "",
          ) ?>"></div>
              <div class="col-md-6 form-group"><label>Asset Images (multiple; max 5 MB each)</label>
                <div class="asset-upload-preview"><img id="entry-image-preview" src="<?= !empty(
              $asset["assetImage"]
          )
              ? BASEURL . "/Asset/media/" . rawurlencode($asset["assetImage"])
              : BASEURL .
                  "/public/img/no-image.png" ?>" alt="Asset image preview"
                    onerror="this.onerror=null;this.src='<?= BASEURL ?>/public/img/no-image.png';">
                </div><input type="file" id="entry-image-input" class="form-control"
                  name="assetImages[]" accept="image/jpeg,image/png,image/webp" multiple>
                <p class="help-block">For batch entry, the selected entry images are copied to every
                  created asset.</p>
              </div>

            </div>
          </div>
        </div>
      </div>

      <div class="clearfix form-actions">
        <div class="text-right">
          <a href="<?= BASEURL ?>/Asset/inactive" class="btn btn-default"><i
              class="fa fa-times"></i> Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= $editing
              ? "Update Asset"
              : "Receive Asset" ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  document.getElementById('asset-receive-form').addEventListener('submit', function() {
    var button = this.querySelector('button[type="submit"]');
    button.disabled = true;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
  });
  (function() {
    var category = document.getElementById('asset-category'),
      number = document.getElementById('asset-number-preview'),
      editing = <?= $editing
      ? "true"
      : "false" ?>;
    if (editing) return;

    function preview() {
      var option = category.options[category.selectedIndex],
        prefix = option && option.dataset.prefix || '';
      number.value = prefix ? prefix + '####' : '';
      number.placeholder = prefix ? 'The next available ' + prefix +
        ' number is confirmed on save' : 'Select a category; number is generated on save';
    }
    category.addEventListener('change', preview);
    preview();
  })();
  (function() {
    var count = document.getElementById('asset-count'),
      list = document.getElementById('serial-number-list');
    if (!count || !list) return;

    function renderSerials() {
      var existing = Array.prototype.map.call(list.querySelectorAll('input'), function(input) {
        return input.value;
      });
      var total = Math.max(1, Math.min(100, parseInt(count.value, 10) || 1)),
        html = '';
      for (var i = 0; i < total; i++) html +=
        '<div class="col-sm-4 form-group"><input class="form-control" name="serialNumbers[]" maxlength="100" placeholder="Serial number for asset ' +
        (i + 1) + '" value="' + String(existing[i] || '').replace(/&/g, '&amp;').replace(/"/g,
          '&quot;').replace(/</g, '&lt;') + '"></div>';
      list.innerHTML = html;
    }
    count.addEventListener('input', renderSerials);
    renderSerials();
  })();
  (function() {
    var purchaseDate = document.getElementById('purchase-date'),
      warrantyExpiry = document.getElementById('warranty-expiry');
    if (!purchaseDate || !warrantyExpiry) return;

    function syncWarrantyMinimum() {
      warrantyExpiry.min = purchaseDate.value || '';
      if (
        purchaseDate.value &&
        warrantyExpiry.value &&
        warrantyExpiry.value < purchaseDate.value
      ) {
        warrantyExpiry.value = '';
      }
    }
    purchaseDate.addEventListener('change', syncWarrantyMinimum);
    syncWarrantyMinimum();
  })();
  (function() {
    var input = document.getElementById('entry-image-input'),
      preview = document.getElementById('entry-image-preview'),
      objectUrl = '';
    if (!input || !preview) return;
    input.addEventListener('change', function() {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      if (this.files && this.files[0]) {
        objectUrl = URL.createObjectURL(this.files[0]);
        preview.src = objectUrl;
      } else preview.src = '<?= BASEURL ?>/public/img/no-image.png';
    });
  })();
  (function() {
    var input = document.getElementById('invoice-pdf-input'),
      status = document.getElementById('invoice-pdf-status');
    if (!input || !status) return;
    input.addEventListener('change', function() {
      status.textContent = this.files && this.files[0] ? this.files[0].name :
        'No PDF selected';
    });
  })();
</script>
