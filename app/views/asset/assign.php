<div class="page-content">
  <div class="container-fluid">
    <style>
      .workflow-image-preview {
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

      .workflow-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: contain
      }
    </style>
    <div class="row">
      <div class="col-xs-12"><?php Flasher::msgInfo(); ?></div>
    </div>
    <form action="<?= BASEURL ?>/Asset/storeAssignment" method="post" enctype="multipart/form-data"
      id="assign-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
        $data["csrf"],
        ENT_QUOTES,
        "UTF-8",
    ) ?>">
      <div class="widget-box">
        <div class="widget-header">
          <h4 class="widget-title"><i class="fa fa-exchange"></i> Guided Asset Assignment</h4>
        </div>
        <div class="widget-body">
          <div class="widget-main">
            <div class="alert alert-info">Complete each step. The next section appears after the
              required selection is valid. A UNICOIL assignment PDF will be generated automatically
              after assignment.</div>
            <div class="row">
              <div class="col-md-6 form-group"><label>1. Assign To <span
                    class="red">*</span></label>
                <select name="assigneeType" id="assigneeType" class="form-control" required>
                  <option value="">Select department or user</option>
                  <option value="DEPARTMENT">Department</option>
                  <option value="USER">User</option>
                </select>
              </div>
            </div>
            <div id="department-step" class="row hide">
              <div class="col-md-6 form-group"><label>2. Department <span
                    class="red">*</span></label><select name="deptId" id="deptId"
                  class="form-control">
                  <option value="">Select department</option><?php foreach (
              $data["departments"]
              as $d
          ): ?><option value="<?= htmlspecialchars($d["deptId"]) ?>"><?= htmlspecialchars(
    $d["deptName"],
) ?></option><?php endforeach; ?>
                </select></div>
              <div class="col-md-6 form-group"><label>3. Section Head</label><input type="hidden"
                  name="departmentHeadId" id="departmentHeadId"><input id="departmentHeadName"
                  class="form-control" value="Select department first" readonly>
                <p class="help-block">Automatically loaded from the selected department.</p>
              </div>
            </div>
            <div id="user-step" class="row hide">
              <div class="col-md-4 form-group"><label>2. Employee ID <span
                    class="red">*</span></label><input name="empId" id="empIdAssign"
                  class="form-control" inputmode="numeric" autocomplete="off"></div>
              <div class="col-md-4 form-group"><label>3. Employee Name</label><input
                  id="empNameAssign" class="form-control" readonly></div>
              <div class="col-md-4 form-group"><label>Department</label><input id="empDeptAssign"
                  class="form-control" readonly></div>
              <div class="col-md-12"><span id="employee-message" class="help-block"></span></div>
            </div>
            <div id="asset-step" class="hide">
              <hr>
              <div class="row">
                  <div class="col-md-8 form-group"><label>4. Available Assets <span
                      class="red">*</span></label><select name="assetNums[]" id="assetNumAssign"
                    class="form-control searchable-select"
                    data-placeholder="Type at least 2 characters to find an asset"
                    data-remote-url="<?= BASEURL ?>/Asset/searchAvailableAssets"
                    required multiple><?php foreach (
                $data["assets"]
                as $a
            ): ?><option value="<?= htmlspecialchars(
    $a["assetNum"],
) ?>" data-category="<?= htmlspecialchars($a["category"]) ?>" data-status="<?= htmlspecialchars(
    $a["status_name"],
) ?>" data-description="<?= htmlspecialchars(
    $a["Description"],
) ?>" data-condition="<?= htmlspecialchars(
    $a["asset_condition"] ?? "NEW",
) ?>" data-image="<?= htmlspecialchars(
    $a["assetImage"] ?? "",
    ENT_QUOTES,
    "UTF-8",
) ?>"><?= htmlspecialchars(
    $a["assetNum"] . " - " . $a["Description"] . " - " . $a["category"],
) ?></option><?php endforeach; ?></select>
                  <p class="help-block">Type an asset number, serial number, or description. Select
                    up to 8 assets for the same user or department.</p>
                </div>
                <div class="col-md-4 form-group"><label>Selected Assets</label><input
                    id="assetSelectionCount" class="form-control" value="No assets selected"
                    readonly></div>
                <div class="col-md-12 form-group"><label>Selection Summary</label><textarea
                    id="assetSelectionSummary" class="form-control" rows="3" readonly></textarea>
                </div>
                <div class="col-md-12">
                  <div id="asset-condition-list" class="row"></div>
                </div>
              </div>
            </div>
            <div id="issue-step" class="hide">
              <hr>
              <div class="row">
                <!-- Issue Number input is temporarily hidden. The backend generates it automatically.
            <div class="col-md-4 form-group"><label>Issue Number</label><input name="issueNo" class="form-control" maxlength="30" placeholder="Auto-generated if blank"></div>
            -->
                <div class="col-md-4 form-group"><label>Issue Date <span
                      class="red">*</span></label><input type="datetime-local" name="issueDate"
                    class="form-control" value="<?= date(
                "Y-m-d\TH:i",
            ) ?>" required></div>
                <div class="col-md-4 form-group"><label>Expected Return Date</label><input
                    type="date" name="expectedReturnDate" class="form-control" min="<?= date(
                "Y-m-d",
            ) ?>"></div>
                <!-- The single Condition Out field is temporarily replaced by one condition selector per selected asset.
            <div class="col-md-4 form-group"><label>Condition Out <span class="red">*</span></label><select name="conditionOut" class="form-control" required><option value="NEW">New</option><option value="GOOD">Good</option><option value="DAMAGED">Damaged</option></select></div>
            -->
                <div class="col-md-8 form-group"><label>Remarks</label><input name="remarks"
                  class="form-control" maxlength="500"></div>
                <div class="col-md-12 form-group"><label>Assignment Images (multiple; max 5 MB
                    each)</label>
                  <div class="workflow-image-preview"><img id="assignment-image-preview"
                      src="<?= BASEURL ?>/public/img/no-image.png" alt="Assignment image preview"
                      onerror="this.onerror=null;this.src='<?= BASEURL ?>/public/img/no-image.png';">
                  </div><input type="file" id="assignment-image-input" name="assignmentImages[]"
                    class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                  <p class="help-block">The selected asset image is shown until you select
                    assignment evidence. If none exists, the placeholder is shown.</p>
                </div>
              </div>
              <div class="text-right"><button type="submit" class="btn btn-primary"><i
                    class="fa fa-check"></i> Assign Asset</button></div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  (function() {
    var base = '<?= BASEURL ?>',
      type = document.getElementById('assigneeType'),
      dept = document.getElementById('deptId'),
      head = document.getElementById('departmentHeadId'),
      headName = document.getElementById('departmentHeadName'),
      emp = document.getElementById('empIdAssign'),
      empName = document.getElementById('empNameAssign'),
      empDept = document.getElementById('empDeptAssign'),
      msg = document.getElementById('employee-message'),
      asset = document.getElementById('assetNumAssign');
    var deptStep = document.getElementById('department-step'),
      userStep = document.getElementById('user-step'),
      assetStep = document.getElementById('asset-step'),
      issueStep = document.getElementById('issue-step'),
      timer;

    function show(el, on) {
      el.classList.toggle('hide', !on);
    }

    function enableAssets(on) {
      show(assetStep, on);
      if (!on) {
        Array.prototype.forEach.call(asset.options, function(o) {
          o.selected = false;
        });
        show(issueStep, false);
      }
    }
    type.addEventListener('change', function() {
      var d = this.value === 'DEPARTMENT',
        u = this.value === 'USER';
      show(deptStep, d);
      show(userStep, u);
      dept.required = d;
      emp.required = u;
      head.value = '';
      headName.value = 'Select department first';
      enableAssets(false);
      empName.value = '';
      empDept.value = '';
      msg.textContent = '';
    });
    dept.addEventListener('change', function() {
      head.value = '';
      headName.value = this.value ? 'Loading section head...' : 'Select department first';
      enableAssets(false);
      if (!this.value) return;
      fetch(base + '/Asset/departmentHeads/' + encodeURIComponent(this.value)).then(r => {
        if (!r.ok) throw Error();
        return r.json();
      }).then(rows => {
        if (!rows.length) {
          headName.value = 'No section head assigned';
          return;
        }
        head.value = rows[0].empId;
        headName.value = rows[0].fullname + ' (' + rows[0].empId + ')';
        enableAssets(true);
      }).catch(() => {
        headName.value = 'Unable to load section head';
        enableAssets(false);
      });
    });
    emp.addEventListener('input', function() {
      clearTimeout(timer);
      enableAssets(false);
      empName.value = '';
      empDept.value = '';
      msg.textContent = '';
      var id = this.value.trim();
      if (!id) return;
      timer = setTimeout(function() {
        fetch(base + '/Asset/assignmentEmployee/' + encodeURIComponent(id)).then(r => {
          if (!r.ok) throw Error();
          return r.json();
        }).then(e => {
          empName.value = e.fullname;
          empDept.value = e.deptName || '';
          msg.textContent = 'Employee verified';
          msg.className = 'help-block text-success';
          enableAssets(true);
        }).catch(() => {
          msg.textContent = 'Active employee not found';
          msg.className = 'help-block text-danger';
        });
      }, 350);
    });

    function onAssetChange() {
      var selected = Array.prototype.filter.call(asset.options, function(o) {
        return o.selected;
      });
      if (selected.length > 8) {
        selected[selected.length - 1].selected = false;
        selected.pop();
        if (window.jQuery) jQuery(asset).trigger('change.select2');
        alert('A maximum of 8 assets can be assigned at one time.');
      }
      document.getElementById('assetSelectionCount').value = selected.length ? selected.length +
        ' asset(s) selected' : 'No assets selected';
      document.getElementById('assetSelectionSummary').value = selected.map(function(o) {
        return o.value + ' - ' + o.dataset.description + ' - ' + o.dataset.category;
      }).join('\n');
      renderAssetConditions(selected);
      var image = selected[0] && selected[0].dataset.image || '';
      document.getElementById('assignment-image-preview').src = image ? base +
        '/Asset/media/' + encodeURIComponent(image) : base + '/public/img/no-image.png';
      show(issueStep, selected.length > 0);
    }

    function renderAssetConditions(selected) {
      var list = document.getElementById('asset-condition-list');
      list.innerHTML = '';
      selected.forEach(function(option) {
        var group = document.createElement('div');
        group.className = 'col-md-4 form-group';
        var label = document.createElement('label');
        label.textContent = option.value + ' Current Condition';
        var condition = document.createElement('input');
        condition.type = 'text';
        condition.className = 'form-control';
        condition.value = String(option.dataset.condition || 'NEW')
          .toUpperCase().replace(/_/g, ' ');
        condition.readOnly = true;
        condition.setAttribute('aria-readonly', 'true');
        condition.title = 'Condition is maintained on the asset record and cannot be changed during assignment.';
        var help = document.createElement('p');
        help.className = 'help-block';
        help.textContent = 'Uses the current condition stored for this asset.';
        group.appendChild(label);
        group.appendChild(condition);
        group.appendChild(help);
        list.appendChild(group);
      });
    }
    asset.addEventListener('change', onAssetChange);
    asset.addEventListener('searchable-select-change', onAssetChange);
    document.getElementById('assign-form').addEventListener('submit', function() {
      var b = this.querySelector('button[type=submit]');
      b.disabled = true;
      b.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Assigning...';
    });
    var assignmentInput = document.getElementById('assignment-image-input'),
      assignmentPreview = document.getElementById('assignment-image-preview'),
      assignmentUrl = '';
    assignmentInput.addEventListener('change', function() {
      if (assignmentUrl) URL.revokeObjectURL(assignmentUrl);
      if (this.files && this.files[0]) {
        assignmentUrl = URL.createObjectURL(this.files[0]);
        assignmentPreview.src = assignmentUrl;
      } else onAssetChange();
    });
  })();
</script>
