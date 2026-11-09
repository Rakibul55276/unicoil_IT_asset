<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>
  <form action="<?= BASEURL ?>/Request/store" method="post" id="equipment-request-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"], ENT_QUOTES, "UTF-8") ?>">
    <div class="widget-box">
      <div class="widget-header"><h4 class="widget-title"><i class="fa fa-shopping-basket"></i> Equipment Needed</h4></div>
      <div class="widget-body"><div class="widget-main">
        <div class="alert alert-info">
          You do not need to know a model or specification. Select only what is needed; IT will
          choose the suitable standard. Department Managers may request for themselves or their
          department. HR Requesters may request for themselves or another employee. Issuers may
          also submit requests for themselves as normal users; another Issuer must complete issuance.
        </div>
        <div class="row">
          <div class="col-md-3 form-group">
            <label for="request_target_type">Request Type <span class="red">*</span></label>
            <select name="request_target_type" id="request_target_type" class="form-control" required>
              <option value="SELF">For Myself</option>
              <?php if (!empty($data["canRequestForOthers"])): ?>
              <option value="USER">For Another Employee</option>
              <?php endif; ?>
              <?php if (!empty($data["canRequestDepartment"])): ?>
              <option value="DEPARTMENT">For a Department</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-5 form-group hide" id="employee-target-group">
            <label for="requested_for">Employee <span class="red">*</span></label>
            <select name="requested_for" id="requested_for" class="form-control searchable-select" data-placeholder="Search employee ID or name" required>
              <option value=""></option>
              <?php foreach ($data["employees"] as $employee): ?>
              <option value="<?= (int) $employee["empId"] ?>"><?= htmlspecialchars($employee["empId"] . " - " . $employee["fullname"] . " (" . $employee["deptName"] . ")") ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if (!empty($data["canRequestDepartment"])): ?>
          <div class="col-md-5 form-group hide" id="department-target-group">
            <label for="requested_department">Department <span class="red">*</span></label>
            <select name="requested_department" id="requested_department" class="form-control searchable-select" data-placeholder="Select department">
              <option value=""></option>
              <?php foreach ($data["departments"] as $department): ?>
              <option value="<?= htmlspecialchars($department["deptId"]) ?>"><?= htmlspecialchars($department["deptName"] . (!empty($department["headName"]) ? " - Section Head: " . $department["headName"] : " - No section head")) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="help-block">The section head will accept the issued assets.</p>
          </div>
          <?php endif; ?>
          <div class="col-md-4 form-group"><label for="required_date">Required Date</label><input type="date" name="required_date" id="required_date" class="form-control" min="<?= date("Y-m-d") ?>"></div>
        </div>
        <h4 class="header blue smaller"><i class="fa fa-desktop"></i> Select Equipment Types</h4>
        <div class="row">
          <?php foreach ($data["categories"] as $category): ?>
          <div class="col-sm-6 col-md-4"><div class="well well-sm equipment-choice" style="min-height:105px">
            <label style="display:block"><input type="checkbox" class="equipment-check" value="<?= htmlspecialchars($category["assetKat"]) ?>"> <strong><?= htmlspecialchars($category["Description"]) ?></strong></label>
            <small class="text-muted"><?= (int) $category["available_count"] ?> currently in stock</small>
            <div class="quantity-wrap hide" style="margin-top:8px">
              <input type="hidden" class="category-input" name="category[]" disabled value="<?= htmlspecialchars($category["assetKat"]) ?>">
              <input type="number" class="form-control quantity-input" name="quantity[]" min="1" max="8" value="1" disabled aria-label="Quantity for <?= htmlspecialchars($category["Description"]) ?>">
            </div>
          </div></div>
          <?php endforeach; ?>
        </div>
        <p class="help-block">Maximum 8 assets per request. Selected: <strong id="equipment-total">0</strong> / 8</p>
        <div id="equipment-error" class="text-danger" style="margin-bottom:10px">Select at least one equipment type.</div>
        <div class="form-group"><label for="business_reason">Business Reason <span class="red">*</span></label><textarea name="business_reason" id="business_reason" class="form-control" rows="3" maxlength="500" required placeholder="For example: New employee joining the Finance team"></textarea></div>
        <div class="text-right"><a href="<?= BASEURL ?>/Request" class="btn btn-default">Cancel</a> <button type="submit" class="btn btn-success" disabled><i class="fa fa-paper-plane"></i> Submit Request</button></div>
      </div></div>
    </div>
  </form>
</div></div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('equipment-request-form');
  var targetType = document.getElementById('request_target_type');
  var employeeGroup = document.getElementById('employee-target-group');
  var departmentGroup = document.getElementById('department-target-group');
  var employee = document.getElementById('requested_for');
  var department = document.getElementById('requested_department');
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
  function updateTargetType() {
    var isDepartment = targetType.value === 'DEPARTMENT';
    var isOtherEmployee = targetType.value === 'USER';
    employeeGroup.classList.toggle('hide', !isOtherEmployee);
    if (departmentGroup) departmentGroup.classList.toggle('hide', !isDepartment);
    employee.required = isOtherEmployee;
    if (department) department.required = isDepartment;
  }
  targetType.addEventListener('change', updateTargetType);
  updateTargetType();
  Array.prototype.forEach.call(document.querySelectorAll('.equipment-check'), function (checkbox) {
    checkbox.addEventListener('change', function () {
      var wrap = this.closest('.equipment-choice').querySelector('.quantity-wrap');
      wrap.classList.toggle('hide', !this.checked);
      Array.prototype.forEach.call(wrap.querySelectorAll('input'), function (input) { input.disabled = !checkbox.checked; });
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
