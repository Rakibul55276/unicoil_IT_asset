<style>
  .accessory-summary { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:15px; }
  .accessory-summary .summary-box { min-width:160px; padding:12px 16px; background:#fff; border:1px solid #d9e2ec; border-left:4px solid #198754; border-radius:5px; }
  .accessory-summary strong { display:block; font-size:20px; color:#17324d; }
  .accessory-stock-low { color:#b56a00; font-weight:700; }
  .accessory-stock-out { color:#b42318; font-weight:700; }
  .accessory-stock-ok { color:#18794e; font-weight:700; }
  .accessory-form-panel .widget-body { padding:15px; }
  .accessory-table td, .accessory-table th { vertical-align:middle !important; }
  .accessory-remarks { max-width:320px; white-space:normal; overflow-wrap:anywhere; }
  .accessory-low-stock-popup { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:1100; width:min(460px, calc(100vw - 40px)); box-shadow:0 10px 32px rgba(0,0,0,.28); transition:opacity .25s ease; }
</style>

<div class="page-content">
  <div class="container-fluid">
    <div><?php Flasher::msgInfo(); ?></div>

    <?php
    $lowStockNotice = $_SESSION["accessory_low_stock_notice"] ?? null;
    unset($_SESSION["accessory_low_stock_notice"]);
    if (is_array($lowStockNotice)):
        $noticeQuantity = static function ($value): string {
            return rtrim(rtrim(number_format((float) $value, 3, ".", ""), "0"), ".");
        };
    ?>
      <div id="accessory-low-stock-popup" class="alert alert-warning accessory-low-stock-popup" role="alert">
        <strong><i class="fa fa-exclamation-triangle"></i> Low Stock</strong><br>
        <?= htmlspecialchars($lowStockNotice["code"] . " - " . $lowStockNotice["name"]) ?> has
        <strong><?= htmlspecialchars($noticeQuantity($lowStockNotice["stock"]) . " " . $lowStockNotice["unit"]) ?></strong>
        remaining. The low-stock alert quantity is
        <?= htmlspecialchars($noticeQuantity($lowStockNotice["level"]) . " " . $lowStockNotice["unit"]) ?>.
      </div>
    <?php endif; ?>

    <?php
    $totalItems = count($data["inventory"]);
    $totalUnits = 0.0;
    $lowItems = 0;
    foreach ($data["inventory"] as $summaryItem) {
        $totalUnits += (float) $summaryItem["quantity_on_hand"];
        if ((int) $summaryItem["active"] === 1 && (float) $summaryItem["quantity_on_hand"] <= (float) $summaryItem["reorder_level"]) {
            $lowItems++;
        }
    }
    $formatQuantity = static function ($value): string {
        $formatted = number_format((float) $value, 3, ".", "");
        return rtrim(rtrim($formatted, "0"), ".");
    };
    ?>

    <div class="accessory-summary">
      <div class="summary-box"><strong><?= (int) $totalItems ?></strong>Filtered accessory items</div>
      <div class="summary-box"><strong><?= htmlspecialchars($formatQuantity($totalUnits)) ?></strong>Total filtered quantity</div>
      <div class="summary-box"><strong><?= (int) $lowItems ?></strong>At/below low-stock alert</div>
    </div>

    <?php if (!empty($data["isAdministrator"]) && !empty($data["editItem"])):
        $editItem = $data["editItem"];
    ?>
    <div class="widget-box accessory-form-panel">
      <div class="widget-header"><h4 class="widget-title"><i class="fa fa-pencil"></i> Edit Accessory Item</h4></div>
      <div class="widget-body">
        <form method="post" action="<?= BASEURL ?>/Accessory/update">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"], ENT_QUOTES, "UTF-8") ?>">
          <input type="hidden" name="accessory_id" value="<?= (int) $editItem["accessory_id"] ?>">
          <div class="row">
            <div class="col-md-2 form-group"><label>Accessory Code *</label><input class="form-control" name="accessory_code" maxlength="30" required value="<?= htmlspecialchars($editItem["accessory_code"], ENT_QUOTES, "UTF-8") ?>"></div>
            <div class="col-md-3 form-group"><label>Accessory Name *</label><input class="form-control" name="accessory_name" maxlength="150" required value="<?= htmlspecialchars($editItem["accessory_name"], ENT_QUOTES, "UTF-8") ?>"></div>
            <div class="col-md-2 form-group"><label>Category *</label><input class="form-control" name="category" maxlength="100" list="accessory-category-list" required value="<?= htmlspecialchars($editItem["category"], ENT_QUOTES, "UTF-8") ?>"></div>
            <div class="col-md-1 form-group"><label>Unit *</label><input class="form-control" name="unit" maxlength="20" required value="<?= htmlspecialchars($editItem["unit"], ENT_QUOTES, "UTF-8") ?>"></div>
            <div class="col-md-2 form-group"><label>Current Stock *</label><input class="form-control" type="number" name="quantity_on_hand" min="0" max="99999999999" step="0.001" required value="<?= htmlspecialchars($formatQuantity($editItem["quantity_on_hand"])) ?>"></div>
            <div class="col-md-2 form-group"><label>Low Stock Alert At *</label><input class="form-control" type="number" name="reorder_level" min="0" max="99999999999" step="0.001" required value="<?= htmlspecialchars($formatQuantity($editItem["reorder_level"])) ?>"></div>
            <div class="col-md-2 form-group"><label>Status *</label><select class="form-control" name="active" required><option value="1" <?= (int) $editItem["active"] === 1 ? "selected" : "" ?>>Active</option><option value="0" <?= (int) $editItem["active"] === 0 ? "selected" : "" ?>>Inactive</option></select></div>
            <div class="col-md-3 form-group"><label>Reference</label><input class="form-control" name="reference_no" maxlength="100" placeholder="Ticket or correction reference"></div>
            <div class="col-md-7 form-group"><label>Change Remarks</label><input class="form-control" name="remarks" maxlength="500" placeholder="Required when changing current stock"></div>
          </div>
          <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Update Accessory</button>
          <a class="btn btn-default" href="<?= BASEURL ?>/Accessory"><i class="fa fa-times"></i> Cancel</a>
        </form>
      </div>
    </div>
    <div class="space-12"></div>
    <?php endif; ?>

    <div class="row accessory-form-panel">
      <div class="col-md-6">
        <div class="widget-box">
          <div class="widget-header"><h4 class="widget-title"><i class="fa fa-plus-circle"></i> Add Accessory Item</h4></div>
          <div class="widget-body">
            <form method="post" action="<?= BASEURL ?>/Accessory/create">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"], ENT_QUOTES, "UTF-8") ?>">
              <div class="row">
                <div class="col-sm-4 form-group"><label>Accessory Code *</label><input class="form-control" name="accessory_code" maxlength="30" required placeholder="CONN001"></div>
                <div class="col-sm-8 form-group"><label>Accessory Name *</label><input class="form-control" name="accessory_name" maxlength="150" required placeholder="USB-C Connector"></div>
                <div class="col-sm-6 form-group"><label>Category *</label><input class="form-control" name="category" maxlength="100" list="accessory-category-list" required placeholder="Connector"></div>
                <div class="col-sm-3 form-group"><label>Unit *</label><input class="form-control" name="unit" maxlength="20" value="PCS" required></div>
                <div class="col-sm-3 form-group"><label>Opening Qty</label><input class="form-control" type="number" name="opening_quantity" min="0" max="99999999999" step="0.001" value="0"></div>
                <div class="col-sm-4 form-group"><label>Low Stock Alert At</label><input class="form-control" type="number" name="reorder_level" min="0" max="99999999999" step="0.001" value="0"><small class="help-block">Show a warning when remaining stock reaches this quantity.</small></div>
                <div class="col-sm-8 form-group"><label>Reference</label><input class="form-control" name="reference_no" maxlength="100" placeholder="Invoice, PO or GRN"></div>
                <div class="col-sm-12 form-group"><label>Opening Stock Remarks</label><textarea class="form-control" name="remarks" maxlength="500" rows="2" placeholder="Required when opening quantity is greater than zero"></textarea></div>
              </div>
              <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save Accessory</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="widget-box">
          <div class="widget-header"><h4 class="widget-title"><i class="fa fa-exchange"></i> Accessory Stock Action</h4></div>
          <div class="widget-body">
            <form method="post" action="<?= BASEURL ?>/Accessory/transact" id="accessory-transaction-form">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"], ENT_QUOTES, "UTF-8") ?>">
              <div class="row">
                <div class="col-sm-8 form-group"><label>Accessory *</label><select class="form-control searchable-select" name="accessory_id" required><option value="">Select accessory</option><?php foreach ($data["items"] as $item): ?><option value="<?= (int) $item["accessory_id"] ?>"><?= htmlspecialchars($item["accessory_code"] . " - " . $item["accessory_name"] . " | Stock: " . $formatQuantity($item["quantity_on_hand"]) . " " . $item["unit"]) ?></option><?php endforeach; ?></select></div>
                <div class="col-sm-4 form-group"><label>Action *</label><select class="form-control" name="event_type" id="accessory-event-type" required><option value="">Select action</option><option value="RECEIPT">Receive Stock (+)</option><option value="ISSUE">Issue to Recipient (-)</option><option value="RETURN">Return from Recipient (+)</option><option value="ADJUSTMENT_IN">Adjustment Increase (+)</option><option value="ADJUSTMENT_OUT">Adjustment Decrease (-)</option></select></div>
                <div class="col-sm-4 form-group"><label>Quantity *</label><input class="form-control" type="number" name="quantity" min="0.001" max="99999999999" step="0.001" required></div>
                <div class="col-sm-4 form-group accessory-recipient"><label>Recipient Type *</label><select class="form-control" name="recipient_type" id="accessory-recipient-type"><option value="EMPLOYEE">Employee</option><option value="DEPARTMENT">Department</option></select></div>
                <div class="col-sm-4 form-group accessory-recipient accessory-employee"><label>Employee *</label><select class="form-control searchable-select" name="empId"><option value="">Select employee</option><?php foreach ($data["employees"] as $employee): ?><option value="<?= (int) $employee["empId"] ?>"><?= htmlspecialchars($employee["empId"] . " - " . $employee["fullname"] . (!empty($employee["deptName"]) ? " (" . $employee["deptName"] . ")" : "")) ?></option><?php endforeach; ?></select></div>
                <div class="col-sm-4 form-group accessory-recipient accessory-department" style="display:none"><label>Department *</label><select class="form-control searchable-select" name="deptId"><option value="">Select department</option><?php foreach ($data["departments"] as $department): ?><option value="<?= htmlspecialchars($department["deptId"], ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($department["deptName"]) ?></option><?php endforeach; ?></select></div>
                <div class="col-sm-4 form-group"><label>Reference</label><input class="form-control" name="reference_no" maxlength="100" placeholder="Ticket, invoice or document"></div>
                <div class="col-sm-12 form-group"><label>Remarks / Justification *</label><textarea class="form-control" name="remarks" maxlength="500" rows="2" required placeholder="Explain why this stock movement is required"></textarea></div>
              </div>
              <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i> Record Stock Action</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <datalist id="accessory-category-list"><?php foreach ($data["categories"] as $category): ?><option value="<?= htmlspecialchars($category, ENT_QUOTES, "UTF-8") ?>"><?php endforeach; ?></datalist>

    <div class="space-12"></div>
    <div class="widget-box">
      <div class="widget-header"><h4 class="widget-title"><i class="fa fa-cubes"></i> Accessory Inventory</h4></div>
      <div class="widget-body"><div class="widget-main">
        <form method="get" action="<?= BASEURL ?>/Accessory" class="row">
          <div class="col-md-4 form-group"><label>Search</label><input class="form-control" name="search" maxlength="100" value="<?= htmlspecialchars($data["search"], ENT_QUOTES, "UTF-8") ?>" placeholder="Code or accessory name"></div>
          <div class="col-md-3 form-group"><label>Category</label><select class="form-control" name="category"><option value="">All categories</option><?php foreach ($data["categories"] as $category): ?><option value="<?= htmlspecialchars($category, ENT_QUOTES, "UTF-8") ?>" <?= $data["category"] === $category ? "selected" : "" ?>><?= htmlspecialchars($category) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-3 form-group"><label>Stock Status</label><select class="form-control" name="status"><option value="">All statuses</option><?php foreach (["AVAILABLE" => "Available", "LOW" => "Low Stock", "OUT" => "Out of Stock", "INACTIVE" => "Inactive"] as $value => $label): ?><option value="<?= $value ?>" <?= $data["status"] === $value ? "selected" : "" ?>><?= $label ?></option><?php endforeach; ?></select></div>
          <div class="col-md-2 form-group"><label>&nbsp;</label><div><button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Apply</button> <a class="btn btn-warning" href="<?= BASEURL ?>/Accessory"><i class="fa fa-refresh"></i></a></div></div>
        </form>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover accessory-table">
            <thead><tr><th>#</th><th>Code</th><th>Accessory</th><th>Category</th><th>Unit</th><th>Current Stock</th><th>Low Stock Alert At</th><th>Status</th><th>Updated</th><?php if (!empty($data["isAdministrator"])): ?><th>Actions</th><?php endif; ?></tr></thead>
            <tbody>
              <?php foreach ($data["inventory"] as $index => $item):
                  $stock = (float) $item["quantity_on_hand"];
                  $reorder = (float) $item["reorder_level"];
                  $stockClass = $stock <= 0 ? "accessory-stock-out" : ($stock <= $reorder ? "accessory-stock-low" : "accessory-stock-ok");
              ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><strong><?= htmlspecialchars($item["accessory_code"]) ?></strong></td>
                <td><?= htmlspecialchars($item["accessory_name"]) ?></td>
                <td><?= htmlspecialchars($item["category"]) ?></td>
                <td><?= htmlspecialchars($item["unit"]) ?></td>
                <td class="<?= $stockClass ?>"><?= htmlspecialchars($formatQuantity($stock)) ?></td>
                <td><?= htmlspecialchars($formatQuantity($reorder)) ?></td>
                <td><span class="label label-<?= (int) $item["active"] === 1 ? "success" : "default" ?>"><?= (int) $item["active"] === 1 ? "Active" : "Inactive" ?></span></td>
                <td><?= htmlspecialchars(date("Y-m-d h:i A", strtotime($item["updated_at"]))) ?></td>
                <?php if (!empty($data["isAdministrator"])): ?>
                <td class="text-nowrap">
                  <a class="btn btn-xs btn-primary" href="<?= BASEURL ?>/Accessory?edit=<?= (int) $item["accessory_id"] ?>" title="Edit accessory"><i class="fa fa-pencil"></i> Edit</a>
                  <form method="post" action="<?= BASEURL ?>/Accessory/delete" style="display:inline" onsubmit="return confirm('Delete this unused accessory item?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"], ENT_QUOTES, "UTF-8") ?>">
                    <input type="hidden" name="accessory_id" value="<?= (int) $item["accessory_id"] ?>">
                    <button class="btn btn-xs btn-danger" type="submit" title="Delete unused accessory"><i class="fa fa-trash"></i> Delete</button>
                  </form>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
              <?php if (!$data["inventory"]): ?><tr><td colspan="<?= !empty($data["isAdministrator"]) ? 10 : 9 ?>" class="text-center">No accessory items found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
    </div>

    <div class="space-12"></div>
    <div class="widget-box">
      <div class="widget-header"><h4 class="widget-title"><i class="fa fa-history"></i> Recent Accessory Transactions</h4><div class="widget-toolbar"><a href="<?= BASEURL ?>/Asset/auditReport?search=ACT-"><i class="fa fa-external-link"></i> Open Full Audit</a></div></div>
      <div class="widget-body"><div class="widget-main no-padding"><div class="table-responsive"><table class="table table-bordered table-striped table-hover accessory-table"><thead><tr><th>Date & Time</th><th>Transaction</th><th>Event</th><th>Accessory</th><th>Quantity</th><th>Balance</th><th>Recipient</th><th>Actor</th><th>Reference</th><th>Remarks</th></tr></thead><tbody><?php foreach ($data["transactions"] as $row): $recipient = $row["recipient_type"] === "EMPLOYEE" ? (($row["empId"] ?? "") . " - " . ($row["fullname"] ?? "-")) : ($row["recipient_type"] === "DEPARTMENT" ? ($row["deptName"] ?? $row["deptId"] ?? "-") : "-"); ?><tr><td><?= htmlspecialchars(date("Y-m-d h:i:s A", strtotime($row["created_at"]))) ?></td><td><strong><?= htmlspecialchars($row["transaction_no"]) ?></strong></td><td><span class="label label-<?= in_array($row["event_type"], ["ISSUE", "ADJUSTMENT_OUT"], true) ? "warning" : "success" ?>"><?= htmlspecialchars(str_replace("_", " ", $row["event_type"])) ?></span></td><td><?= htmlspecialchars($row["accessory_code"] . " - " . $row["accessory_name"]) ?></td><td><?= htmlspecialchars($formatQuantity($row["quantity"]) . " " . $row["unit"]) ?></td><td><?= htmlspecialchars($formatQuantity($row["balance_before"]) . " → " . $formatQuantity($row["balance_after"])) ?></td><td><?= htmlspecialchars($recipient) ?></td><td><?= htmlspecialchars($row["performed_by"]) ?></td><td><?= htmlspecialchars($row["reference_no"] ?? "-") ?></td><td class="accessory-remarks"><?= nl2br(htmlspecialchars($row["remarks"])) ?></td></tr><?php endforeach; ?><?php if (!$data["transactions"]): ?><tr><td colspan="10" class="text-center">No accessory transactions recorded.</td></tr><?php endif; ?></tbody></table></div></div></div>
    </div>
  </div>
</div>

<script>
(function () {
  var lowStockPopup = document.getElementById('accessory-low-stock-popup');
  if (lowStockPopup) {
    window.setTimeout(function () {
      lowStockPopup.style.opacity = '0';
      window.setTimeout(function () {
        if (lowStockPopup.parentNode) lowStockPopup.parentNode.removeChild(lowStockPopup);
      }, 250);
    }, 10000);
  }
  var eventField = document.getElementById('accessory-event-type');
  var recipientField = document.getElementById('accessory-recipient-type');
  var recipientGroups = document.querySelectorAll('.accessory-recipient');
  var employeeGroup = document.querySelector('.accessory-employee');
  var departmentGroup = document.querySelector('.accessory-department');
  function updateRecipientFields() {
    var needsRecipient = eventField && (eventField.value === 'ISSUE' || eventField.value === 'RETURN');
    for (var i = 0; i < recipientGroups.length; i++) recipientGroups[i].style.display = needsRecipient ? '' : 'none';
    if (!needsRecipient) return;
    var employeeSelected = !recipientField || recipientField.value === 'EMPLOYEE';
    if (employeeGroup) employeeGroup.style.display = employeeSelected ? '' : 'none';
    if (departmentGroup) departmentGroup.style.display = employeeSelected ? 'none' : '';
  }
  if (eventField) eventField.addEventListener('change', updateRecipientFields);
  if (recipientField) recipientField.addEventListener('change', updateRecipientFields);
  updateRecipientFields();
})();
</script>
