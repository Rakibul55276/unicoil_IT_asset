<?php $conditionButtonClass = static function ($condition) {
    return [
        "GOOD" => "btn-success",
        "DAMAGED" => "btn-warning",
    ][strtoupper((string) $condition)] ?? "btn-default";
}; ?>
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

      #returnIssue+.select2-container .select2-selection--multiple {
        height: auto !important;
        min-height: 42px
      }

      #returnIssue+.select2-container .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap;
        gap: 3px;
        white-space: normal !important
      }

      #returnIssue+.select2-container .select2-selection__choice {
        float: none !important
      }

      #returnIssue+.select2-container .select2-choices {
        height: auto !important;
        max-height: none !important;
        white-space: normal !important;
        overflow: visible !important
      }

      .condition-button {
        min-width: 105px;
        cursor: default !important;
        font-weight: 700;
        text-transform: capitalize
      }

      .condition-select.condition-good {
        background: #dff0d8;
        color: #27632b;
        border-color: #85c785
      }

      .condition-select.condition-fair {
        background: #d9edf7;
        color: #245269;
        border-color: #7dc4df
      }

      .condition-select.condition-damaged {
        background: #fcf8e3;
        color: #8a5d00;
        border-color: #e9c75b
      }

      .condition-select.condition-beyond-repair {
        background: #f2dede;
        color: #8b2424;
        border-color: #dc7777
      }
    </style>
    <div class="row">
      <div class="col-xs-12"><?php Flasher::msgInfo(); ?></div>
    </div>
    <?php if (!empty($data["returnPdfIds"])):
      $returnPdfUrl =
          BASEURL .
          "/Asset/downloadReturnPdf/" .
          htmlspecialchars($data["returnPdfIds"], ENT_QUOTES, "UTF-8"); ?>
    <div class="alert alert-success"><i class="fa fa-file-pdf-o"></i> UNICOIL return form is ready.
      <a class="btn btn-danger btn-sm" href="<?= $returnPdfUrl ?>"><i class="fa fa-download"></i>
        Download Return PDF</a></div>
    <?php
  endif; ?>
    <form action="<?= BASEURL ?>/Asset/storeReturn" method="post" enctype="multipart/form-data"
      id="return-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
        $data["csrf"],
        ENT_QUOTES,
        "UTF-8",
    ) ?>">
      <input type="hidden" name="return_source" value="<?= htmlspecialchars(
          (string) ($data["returnSource"] ?? ""),
          ENT_QUOTES,
          "UTF-8",
      ) ?>">
      <div class="widget-box">
        <div class="widget-header">
          <h4 class="widget-title"><i class="fa fa-reply"></i> Return Assigned Asset</h4>
        </div>
        <div class="widget-body">
          <div class="widget-main">
            <div class="row">
              <div class="col-md-6 form-group"><label>1. Search Return Assignment <span
                    class="red">*</span></label><select id="returnSearch"
                  class="form-control searchable-select"
                  data-placeholder="Type at least 2 characters"
                  data-remote-url="<?= BASEURL ?>/Asset/searchOpenIssues">
                  <option value="">Start search</option><?php foreach (
            $data["issues"]
            as $i
        ):

            $isDepartment = $i["assignee_type"] === "DEPARTMENT";
            $custodianKey = $isDepartment
                ? "DEPARTMENT|" . ($i["deptId"] ?? "")
                : "USER|" . ($i["empId"] ?? "");
            $searchLabel = implode(
                " - ",
                array_filter(
                    $isDepartment
                        ? [
                            $i["assetNum"],
                            $i["Description"],
                            $i["category"],
                            $i["deptName"] ?? null,
                        ]
                        : [
                            $i["assetNum"],
                            $i["Description"],
                            $i["category"],
                            $i["empId"] ?? null,
                            $i["employee_name"] ?? null,
                            $i["deptName"] ?? null,
                        ],
                ),
            );
            ?><option value="<?= (int) $i["issue_id"] ?>"
                  data-asset="<?= htmlspecialchars($i["assetNum"]) ?>"
                  data-description="<?= htmlspecialchars($i["Description"]) ?>"
                  data-category="<?= htmlspecialchars($i["category"]) ?>"
                  data-type="<?= htmlspecialchars($i["assignee_type"]) ?>"
                  data-holder="<?= htmlspecialchars(
                      $isDepartment ? $i["deptName"] ?? "" : $i["employee_name"] ?? "",
                  ) ?>"
                  data-custodian="<?= htmlspecialchars($custodianKey) ?>"
                  data-status="<?= htmlspecialchars($i["asset_status"]) ?>"
                  data-image="<?= htmlspecialchars(
                      $i["assetImage"] ?? "",
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>"><?= htmlspecialchars($searchLabel) ?></option><?php
        endforeach; ?>
                </select>
                <p class="help-block">Search by asset number/name, employee ID/name, category, or
                  department.</p>
              </div>
              <div class="col-md-6 form-group"><label>2. Assets in Selected ISS Batch
                  <span class="red">*</span></label><select name="issueIds[]" id="returnIssue"
                  class="form-control searchable-select"
                  data-placeholder="Select assets for the same custodian"
                  required multiple disabled><?php foreach (
            $data["issues"]
            as $i
        ):

            $isDepartment = $i["assignee_type"] === "DEPARTMENT";
            $holder = $isDepartment ? $i["deptName"] ?? "" : $i["employee_name"];
            $custodianKey = $isDepartment
                ? "DEPARTMENT|" . ($i["deptId"] ?? "")
                : "USER|" . ($i["empId"] ?? "");
            $searchLabel = implode(
                " - ",
                array_filter([$i["assetNum"], $i["Description"], $i["category"]]),
            );
            ?><option value="<?= (int) $i["issue_id"] ?>" data-asset="<?= htmlspecialchars(
    $i["assetNum"],
) ?>" data-description="<?= htmlspecialchars(
    $i["Description"],
) ?>" data-category="<?= htmlspecialchars($i["category"]) ?>" data-type="<?= htmlspecialchars(
    $i["assignee_type"],
) ?>" data-holder="<?= htmlspecialchars($holder) ?>" data-custodian="<?= htmlspecialchars(
    $custodianKey,
) ?>" data-status="<?= htmlspecialchars($i["asset_status"]) ?>" data-image="<?= htmlspecialchars(
    $i["assetImage"] ?? "",
    ENT_QUOTES,
    "UTF-8",
) ?>"><?= htmlspecialchars($searchLabel) ?></option><?php
        endforeach; ?></select>
                <p class="help-block" id="return-asset-help">All eligible assets from the selected ISS batch are loaded.
                  Select 1 to 8 assets from that batch for one RET transaction.</p>
              </div>
              <div class="col-md-3 form-group"><label>Asset Number</label><input id="returnAssetNum"
                  class="form-control" readonly></div>
              <div class="col-md-3 form-group"><label>Assignment Type</label><input
                  id="returnTypeView" class="form-control" readonly></div>
              <div class="col-md-4 form-group"><label>Assigned To</label><input id="returnHolder"
                  class="form-control" readonly></div>
              <div class="col-md-4 form-group"><label>Category</label><input id="returnCategory"
                  class="form-control" readonly></div>
              <div class="col-md-4 form-group"><label>Current Status</label><input id="returnStatus"
                  class="form-control" readonly></div>
              <div class="col-md-12">
                <div id="return-condition-list" class="row"></div>
              </div>
            </div>
            <div id="return-details" class="hide">
              <hr>
              <div class="row">
                <!-- Return Number is temporarily hidden and generated automatically.
        <div class="col-md-4 form-group"><label>Return Number</label><input name="returnNo" class="form-control" maxlength="30" placeholder="Auto-generated if blank"></div>
        -->
                <div class="col-md-4 form-group"><label>Return Date <span
                      class="red">*</span></label><input type="datetime-local" name="returnDate"
                    class="form-control" value="<?= date(
            "Y-m-d\TH:i",
        ) ?>" required>
                <input type="hidden" name="returnType" id="returnDisposition" value="REISSUE">
                <!-- Return Type is temporarily fixed to Return for Re-issue.
        <div class="col-md-4 form-group"><label>Return Type <span class="red">*</span></label><select name="returnType" class="form-control" required><option value="REISSUE">Return for Re-issue</option><option value="SCRAP">Scrap</option></select></div>
        -->
                <!-- The shared Condition In field is replaced by one condition selector per asset.
        <div class="col-md-4 form-group"><label>Condition In <span class="red">*</span></label><select name="conditionIn" class="form-control" required><option value="NEW">New</option><option value="GOOD">Good</option><option value="DAMAGED">Damaged</option></select></div>
        -->
                <!-- Scrap Reference is temporarily hidden.
        <div class="col-md-4 form-group"><label>Scrap Reference</label><input name="scrapReference" id="scrapReference" class="form-control" maxlength="100"></div>
        -->
                    <div class="col-md-8 form-group"><label>Remarks <span id="reasonRequired"
                      class="red hide">*</span></label><textarea name="remarks" id="returnReason"
                    class="form-control" rows="2" maxlength="500"></textarea></div>
                <div class="col-md-12 form-group"><label>Return Images (multiple; max 5 MB
                    each)</label>
                  <div class="workflow-image-preview"><img id="return-image-preview"
                      src="<?= BASEURL ?>/public/img/no-image.png" alt="Return image preview"
                      onerror="this.onerror=null;this.src='<?= BASEURL ?>/public/img/no-image.png';">
                  </div><input type="file" id="return-image-input" name="returnImages[]"
                    class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                  <p class="help-block">The selected asset image is shown until return evidence is
                    selected. If none exists, the placeholder is shown.</p>
                </div>
              </div>
              <div class="text-right"><button type="submit" class="btn btn-primary"><i
                    class="fa fa-check"></i> Complete Return</button></div>
            </div>
          </div>
        </div>
      </div>
    </form>
    <div class="space-12"></div>
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title">Recent Return History</h4>
        <div class="widget-toolbar"><a class="btn btn-xs btn-primary"
            href="<?= BASEURL ?>/Asset/returnHistory"><i class="fa fa-history"></i> View Complete
            History</a></div>
      </div>
      <div class="widget-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Return No.</th>
                <th>Date</th>
                <th>Asset Number</th>
                <th>Asset Name / Return Condition</th>
                <th>Category</th>
                <th>Custodian</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody><?php
    foreach ($data["history"] as $r):
        $recentAssetNums = explode("|||", (string) ($r["assetNum"] ?? ""));
        $recentConditions = explode("|||", (string) ($r["condition_in"] ?? ""));
        $recentDescriptions = explode("|||", (string) ($r["assetDescriptions"] ?? ""));
        $recentCategories = explode("|||", (string) ($r["categoryNames"] ?? ""));
        $recentRowspan = max(1, count($recentAssetNums));
        foreach ($recentAssetNums as $recentIndex => $recentAssetNum): ?>
              <tr class="return-asset-subrow">
                <?php if (
            $recentIndex === 0
        ): ?><td rowspan="<?= $recentRowspan ?>" class="return-reference-cell"><span
                    class="return-number-box"><?= htmlspecialchars(
    $r["return_no"],
) ?></span><a class="btn btn-danger btn-sm return-pdf-button" href="<?= BASEURL ?>/Asset/downloadReturnPdf/<?= htmlspecialchars(
    $r["return_id"],
    ENT_QUOTES,
    "UTF-8",
) ?>" title="Download combined return PDF"><i class="fa fa-file-pdf-o"></i></a></td>
                <td rowspan="<?= $recentRowspan ?>" class="return-shared-cell"><?= htmlspecialchars(
    $r["return_date"],
) ?></td><?php endif; ?>
                <?php
        $recentRawCondition = $recentConditions[$recentIndex] ?? "-";
        $recentCondition = trim(
            strrchr($recentRawCondition, ":") ?: $recentRawCondition,
            ": \t\n\r\0\x0B",
        );
        $recentDescription =
            $recentDescriptions[$recentIndex] ?? trim(strstr($recentRawCondition, ":", true) ?: "");
        ?>
                <td><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars(
            $recentAssetNum,
            ENT_QUOTES,
            "UTF-8",
        ) ?>"><strong><?= htmlspecialchars($recentAssetNum) ?></strong></button></td>
                <td>
                  <div><?= htmlspecialchars(
    $recentDescription ?: "-",
) ?></div><span class="btn btn-xs <?= $conditionButtonClass(
    $recentCondition,
) ?> condition-button"><?= htmlspecialchars(
     ucwords(strtolower(str_replace("_", " ", $recentCondition))),
 ) ?></span>
                </td>
                <td><?= htmlspecialchars($recentCategories[$recentIndex] ?? "-") ?></td>
                <?php if (
            $recentIndex === 0
        ): ?><td rowspan="<?= $recentRowspan ?>" class="return-shared-cell"><?= htmlspecialchars(
    $r["assignee_type"] === "DEPARTMENT" ? $r["deptName"] : $r["custodian"],
) ?></td>
                <td rowspan="<?= $recentRowspan ?>" class="return-shared-cell"><?= htmlspecialchars(
    $r["reason"] ?? "-",
) ?></td><?php endif; ?>
              </tr>
              <?php endforeach;
    endforeach;
    if (
        !$data["history"]
    ): ?><tr>
                <td colspan="7" class="text-center">No return history.</td>
              </tr><?php endif;
    ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <style>
      .return-reference-cell,
      .return-shared-cell {
        text-align: center;
        vertical-align: middle !important
      }

      .return-reference-cell {
        min-width: 150px
      }

      .return-number-box {
        display: block;
        padding: 6px 8px;
        border: 1px solid #0b7769;
        border-radius: 5px;
        background: #eef8f6;
        color: #075b50;
        font-weight: 700;
        overflow-wrap: anywhere
      }

      .return-pdf-button {
        display: inline-block;
        margin-top: 7px
      }

      .return-asset-subrow td {
        vertical-align: middle !important
      }
    </style>
  </div>
</div>
<script>
  (function() {
    var base = '<?= BASEURL ?>',
      search = document.getElementById('returnSearch'),
      issue = document.getElementById('returnIssue'),
      details = document.getElementById('return-details'),
      imageInput = document.getElementById('return-image-input'),
      imagePreview = document.getElementById('return-image-preview'),
      imageUrl = '',
      custodianRequest = null,
      issueMetadata = {},
      selectedReturnIds = <?= json_encode(
    array_map("strval", $data["selectedIssueIds"] ?? []),
) ?>;

    function selectedIssues() {
      return Array.prototype.filter.call(issue.options, function(o) {
        return o.selected && !o.disabled;
      });
    }

    function rememberIssue(item) {
      if (!item || item.id === undefined || item.id === null) return;
      issueMetadata[String(item.id)] = {
        id: String(item.id),
        asset: item.asset || '',
        description: item.description || '',
        category: item.category || '',
        status: item.status || '',
        image: item.image || '',
        type: item.type || '',
        holder: item.holder || '',
        custodian: item.custodian || ''
      };
    }

    function hydrateIssueOption(option) {
      var item = option && issueMetadata[String(option.value)];
      if (!item) return;
      option.dataset.description = item.description;
      option.dataset.category = item.category;
      option.dataset.status = item.status;
      option.dataset.image = item.image;
      option.dataset.asset = item.asset;
      option.dataset.type = item.type;
      option.dataset.holder = item.holder;
      option.dataset.custodian = item.custodian;
    }

    function addIssueOption(item, selected) {
      rememberIssue(item);
      var option = new Option(item.text, item.id, selected, selected);
      hydrateIssueOption(option);
      issue.appendChild(option);
    }

    // Preserve the server-rendered metadata before Select2 can rebuild options.
    Array.prototype.forEach.call(issue.options, function(option) {
      rememberIssue({
        id: option.value,
        asset: option.dataset.asset,
        description: option.dataset.description,
        category: option.dataset.category,
        status: option.dataset.status,
        image: option.dataset.image,
        type: option.dataset.type,
        holder: option.dataset.holder,
        custodian: option.dataset.custodian
      });
    });

    function destroyIssueSelector() {
      var $issue = jQuery(issue);
      if (typeof $issue.select2 === 'function' && $issue.data('select2')) {
        $issue.select2('destroy');
      }
    }

    // The available assets change when a custodian is selected. Rebuild only this
    // Select2 instance so its searchable result list matches the replaced options.
    function refreshIssueSelector(openChoices) {
      var $issue = jQuery(issue);
      if (typeof $issue.select2 === 'function') {
        if ($issue.data('select2')) {
          $issue.select2('destroy');
        }
        $issue.select2({
          width: '100%',
          placeholder: issue.getAttribute('data-placeholder') ||
            'Select assets for the same custodian',
          allowClear: true,
          closeOnSelect: false
        });
        $issue.trigger('change');
        if (openChoices && issue.options.length > selectedIssues().length) {
          $issue.select2('open');
        }
      } else {
        setIssue();
      }
    }

    function chooseCustodian() {
      var source = search.options[search.selectedIndex],
        owner = source && source.dataset.custodian || '',
        firstId = search.value,
        isManualChoice = selectedReturnIds.length === 0,
        requested = selectedReturnIds.length ? selectedReturnIds : [firstId];
      if (custodianRequest && typeof custodianRequest.abort === 'function') {
        custodianRequest.abort();
      }
      // Destroy Select2 before replacing its native options. Destroying it after
      // replacement can restore stale option nodes without their asset metadata.
      destroyIssueSelector();
      issue.innerHTML = '';
      issue.setAttribute('data-remote-extra', firstId);
      issue.disabled = true;
      if (window.jQuery) jQuery(issue).trigger('change.select2');
      if (!owner || !source || !source.value) {
        selectedReturnIds = [];
        setIssue();
        return;
      }

      custodianRequest = jQuery.getJSON(base + '/Asset/searchOpenIssues', {
        q: '',
        batchIssue: firstId
      }).done(function(response) {
        // The shared footer can initialize this selector while the request is in
        // flight, so destroy it once more before inserting the returned options.
        destroyIssueSelector();
        issue.innerHTML = '';
        var selected = {};
        requested.forEach(function(id) { selected[String(id)] = true; });
        (response.results || []).forEach(function(item) {
          addIssueOption(item, Boolean(selected[String(item.id)]));
        });
        issue.disabled = false;
        selectedReturnIds = [];
        refreshIssueSelector(isManualChoice);
        // Select2's programmatic change does not reliably invoke the native
        // listener. Render the batch summary and per-asset conditions now so
        // deep-linked or automatically selected assets never leave blank fields.
        setIssue();
      }).fail(function(_request, status) {
        if (status === 'abort') return;
        destroyIssueSelector();
        issue.innerHTML = '';
        issue.disabled = true;
        selectedReturnIds = [];
        if (window.jQuery) jQuery(issue).trigger('change.select2');
        setIssue();
        window.alert('Unable to load assigned assets for the selected custodian.');
      });
    }

    function applyConditionColor(select) {
      select.classList.remove('condition-good', 'condition-fair', 'condition-damaged',
        'condition-beyond-repair');
      select.classList.add('condition-' + select.value.toLowerCase().replace(/_/g, '-'));
    }

    function renderConditions(selected) {
      var list = document.getElementById('return-condition-list'),
        old = {};
      Array.prototype.forEach.call(list.querySelectorAll('select'), function(s) {
        old[s.dataset.issue] = s.value;
      });
      list.innerHTML = '';
      selected.forEach(function(o) {
        var group = document.createElement('div');
        group.className = 'col-md-4 form-group';
        var label = document.createElement('label');
        label.textContent = o.dataset.asset + ' Return Condition *';
        var select = document.createElement('select');
        select.name = 'conditionIn[' + o.value + ']';
        select.className = 'form-control condition-select';
        select.required = true;
        select.dataset.issue = o.value;
        [
          ['NEW', 'New'],
          ['GOOD', 'Good'],
          ['DAMAGED', 'Damaged']
        ].forEach(function(pair) {
          var option = document.createElement('option');
          option.value = pair[0];
          option.textContent = pair[1];
          if ((old[o.value] || 'GOOD') === pair[0]) option.selected = true;
          select.appendChild(option);
        });
        select.addEventListener('change', function() {
          applyConditionColor(select);
        });
        applyConditionColor(select);
        group.appendChild(label);
        group.appendChild(select);
        list.appendChild(group);
      });
    }

    function setIssue() {
      var selected = selectedIssues();
      if (selected.length > 8) {
        selected[selected.length - 1].selected = false;
        selected.pop();
        alert('A maximum of 8 assets can be returned at one time.');
        if (window.jQuery) jQuery(issue).trigger('change.select2');
      }
      selected.forEach(hydrateIssueOption);
      renderConditions(selected);
      var o = selected[0],
        on = selected.length > 0,
        image = o && o.dataset.image || '';
      details.classList.toggle('hide', !on);
      document.getElementById('returnAssetNum').value = selected.map(function(item) {
        return item.dataset.asset;
      }).join(', ');
      document.getElementById('returnTypeView').value = o && o.dataset.type || '';
      document.getElementById('returnHolder').value = o && o.dataset.holder || '';
      document.getElementById('returnCategory').value = selected.map(function(item) {
        return item.dataset.category;
      }).filter(function(value, index, list) {
        return value && list.indexOf(value) === index;
      }).join(', ');
      document.getElementById('returnStatus').value = selected.length ? selected.length +
        ' selected of ' + issue.options.length + ' available' : '';
      document.getElementById('return-asset-help').textContent = issue.options.length ?
        issue.options.length +
        ' eligible asset(s) found in this ISS batch. Select 1 to 8 assets for one RET transaction.' :
        'No eligible open assets were found in this ISS batch.';
      imagePreview.src = image ? base + '/Asset/media/' + encodeURIComponent(image) :
        base + '/public/img/no-image.png';
    }
    search.addEventListener('searchable-select-change', chooseCustodian);
    issue.addEventListener('change', setIssue);
    issue.addEventListener('searchable-select-change', setIssue);
    imageInput.addEventListener('change', function() {
      if (imageUrl) URL.revokeObjectURL(imageUrl);
      if (this.files && this.files[0]) {
        imageUrl = URL.createObjectURL(this.files[0]);
        imagePreview.src = imageUrl;
      } else setIssue();
    });
    document.getElementById('return-form').addEventListener('submit', function() {
      var b = this.querySelector('button[type=submit]');
      b.disabled = true;
      b.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
    });
    function initializeSelectedReturnBatch() {
      if (!selectedReturnIds.length) return;
      search.value = selectedReturnIds[0];
      chooseCustodian();
    }

    // jQuery and Select2 are loaded by the shared footer after this view.
    // Waiting for DOMContentLoaded prevents a deep-linked ISS batch from
    // stopping at its first asset before those dependencies are available.
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initializeSelectedReturnBatch, {
        once: true
      });
    } else {
      initializeSelectedReturnBatch();
    }
  })();
</script>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
