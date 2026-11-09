<div class="page-content">
  <div class="container-fluid">

    <!-- Search Row -->
    <div class="row mb-3">
      <div class="col-md-5 col-12">
        <input type="text" id="employee-search" class="form-control"
          placeholder="Search Employees...">
      </div>
      <div class="col-md-4 col-12">
        <select id="employee-department-filter" class="form-control">
          <option value="ALL">All Departments</option>
          <?php foreach ($data["departments"] as $department): ?>
          <option value="<?= htmlspecialchars(
                            $department["deptName"],
                        ) ?>"><?= htmlspecialchars($department["deptName"]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 col-12">
        <select id="employee-sort" class="form-control" aria-label="Sort employees">
          <option value="department_asc">Department A-Z</option>
          <option value="department_desc">Department Z-A</option>
          <option value="name_asc">Employee Name A-Z</option>
          <option value="id_asc">Employee ID</option>
        </select>
      </div>
    </div>

    <!-- Employee Table Card -->
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Employee List</h5>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="dynamic-table" data-no-pdf="1"
            class="table table-striped table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>No.</th>
                <th>Employee Id</th>
                <th>Employee Name</th>
                <th>Email</th>
                <th>Position</th>
                <th>Area</th>
                <th>Building</th>
                <th>Extension</th>
                <?php
/* Section column temporarily hidden. ?><th>Section</th><?php */
?>
                <th>Department</th>
                <th>Status</th>
                <th>Current Assigned Assets</th>
                <th>Asset Numbers</th>
                <th>Asset Descriptions</th>
                <th>Asset Categories</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="employee-table-body">
              <?php
$no = 0;
foreach ($data["rdata"] as $rdata):
    $no++; ?>
              <tr class="employee-row"
                data-department="<?= htmlspecialchars($rdata["department"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                data-employee-name="<?= htmlspecialchars($rdata["fullname"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                data-employee-id="<?= (int) $rdata["empId"] ?>">
                <td><?= $no ?></td>
                <td data-order="<?= (int) $rdata["empId"] ?>"><button type="button" class="btn btn-link btn-xs employee-detail-link"
                    data-employee="<?= (int) $rdata[
        "empId"
    ] ?>" title="View employee details and asset transactions"><?= (int) $rdata[
    "empId"
] ?></button></td>
                <td><?= htmlspecialchars(
                    (string) ($rdata["fullname"] ?? ""),
                    ENT_QUOTES,
                    "UTF-8",
                ) ?></td>
                <td><?= htmlspecialchars($rdata["email"] ?? "-") ?></td>
                <td><?= htmlspecialchars($rdata["position"] ?? "-") ?></td>
                <td><?= htmlspecialchars($rdata["area"] ?? "-") ?></td>
                <td><?= htmlspecialchars($rdata["building"] ?? "-") ?></td>
                <td><?= htmlspecialchars($rdata["extension_number"] ?? "-") ?></td>
                <?php
    /* Section column temporarily hidden. ?><td><?= htmlspecialchars($rdata['section']??'-'); ?>
                </td><?php */
    ?>
                <td><?= !empty($rdata["department"]) ? $rdata["department"] : "-" ?></td>

                <td>
                  <?php if (!empty($rdata["employeeStatus"])): ?>
                  <span class="label <?= $rdata["employeeStatus"] === "Active"
              ? "label-success"
              : "label-danger" ?>">
                    <?= $rdata["employeeStatus"] ?>
                  </span>
                  <?php else: ?>
                  -
                  <?php endif; ?>
                </td>
                <td><span class="badge badge-primary"><?= (int) ($rdata["assignedAssetCount"] ??
        0) ?></span></td>
                <td><?php if (!empty($rdata["assetNums"]) && $rdata["assetNums"] !== "-"):
        foreach (explode(",", $rdata["assetNums"]) as $assetNum): ?><button type="button"
                    class="btn btn-link btn-xs asset-detail-link" data-asset="<?= htmlspecialchars(
             $assetNum,
         ) ?>">
                    <?= htmlspecialchars($assetNum) ?></button><?php endforeach;
    else:
         ?>-<?php
    endif; ?></td>
                <td><?= !empty($rdata["assetDescriptions"]) ? $rdata["assetDescriptions"] : "-" ?>
                </td>
                <td><?= !empty($rdata["assetCategories"])
        ? htmlspecialchars($rdata["assetCategories"])
        : "-" ?></td>
                <td>
                  <a class="green" href="<?= BASEURL ?>/Employee/edit/<?= $rdata["empId"] ?>">
                    <i class="ace-icon fa fa-pencil bigger-130"></i>
                  </a>
                  <a class="red" href="<?= BASEURL ?>/Employee/hapusData/<?= $rdata["empId"] ?>"
                    onclick="return confirm('Are you sure you want to delete this record?');">
                    <i class="ace-icon fa fa-trash-o bigger-130"></i>
                  </a>
                </td>
              </tr>
              <?php
endforeach;
?>
            </tbody>

          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<div class="modal fade" id="employeeDetailsModal" tabindex="-1" role="dialog"
  aria-labelledby="employeeDetailsTitle">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal"
          aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="employeeDetailsTitle">Employee Details</h4>
      </div>
      <div class="modal-body">
        <div id="employeeDetailsLoading" class="text-center"><i class="fa fa-spinner fa-spin"></i>
          Loading...</div>
        <div id="employeeDetailsError" class="alert alert-danger hide"></div>
        <div id="employeeDetailsContent" class="hide">
          <div class="table-responsive">
            <table class="table table-bordered">
              <tbody id="employeeDetailsBody"></tbody>
            </table>
          </div>
          <h4><i class="fa fa-exchange"></i> Asset Receive and Return Transactions</h4>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Asset</th>
                  <th>Description</th>
                  <th>Received / Assigned</th>
                  <th>Condition Out</th>
                  <th>Returned</th>
                  <th>Condition In</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="employeeTransactionsBody"></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default"
          data-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>


<script>
  document.addEventListener('DOMContentLoaded', function() {
    const $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.dataTable) {
      console.error('Employee List requires jQuery and DataTables.');
      return;
    }

    const employeeTableElement = $('#dynamic-table');

    function connectEmployeeTable(settings) {
      const employeeTable = new $.fn.dataTable.Api(settings);
      const departmentColumn = 8;

      function applyEmployeeFilters() {
        const searchValue = String($('#employee-search').val() || '');
        const department = String($('#employee-department-filter').val() || 'ALL');
        const escapedDepartment = $.fn.dataTable.util.escapeRegex(department);

        employeeTable.search(searchValue);
        employeeTable.column(departmentColumn).search(
          department === 'ALL' ? '' : '^' + escapedDepartment + '$',
          true,
          false
        );
        employeeTable.draw();
      }

      function applyEmployeeSort() {
        const sort = $('#employee-sort').val();
        const order = {
          department_asc: [[departmentColumn, 'asc'], [2, 'asc']],
          department_desc: [[departmentColumn, 'desc'], [2, 'asc']],
          name_asc: [[2, 'asc']],
          id_asc: [[1, 'asc']]
        };

        employeeTable.order(order[sort] || order.department_asc).draw();
      }

      $('#employee-search')
        .off('.employeeList')
        .on('input.employeeList', applyEmployeeFilters);
      $('#employee-department-filter')
        .off('.employeeList')
        .on('change.employeeList', applyEmployeeFilters);
      $('#employee-sort')
        .off('.employeeList')
        .on('change.employeeList', applyEmployeeSort);

      applyEmployeeFilters();
      applyEmployeeSort();
    }

    // The shared footer initializes DataTables after this view is rendered.
    // Connect to that single instance instead of maintaining a competing DOM
    // row sorter, which breaks as soon as DataTables paginates or redraws.
    employeeTableElement.on('init.dt.employeeList', function(event, settings) {
      connectEmployeeTable(settings);
    });

    if ($.fn.dataTable.isDataTable(employeeTableElement)) {
      connectEmployeeTable(employeeTableElement.DataTable().settings()[0]);
    }

    function esc(value) {
      return $('<div>').text(value == null || value === '' ? '-' : String(value)).html();
    }
    $('.employee-detail-link').on('click', function() {
      let id = String($(this).data('employee') || '');
      $('#employeeDetailsTitle').text('Employee Details - ' + id);
      $('#employeeDetailsLoading').removeClass('hide');
      $('#employeeDetailsError,#employeeDetailsContent').addClass('hide');
      $('#employeeDetailsModal').modal('show');
      $.getJSON('<?= BASEURL ?>/Employee/details/' + encodeURIComponent(id)).done(function(
        data) {
        let e = data.employee || {},
          status = String(e.aktif) === '1' ? 'Active' : 'Inactive';
        $('#employeeDetailsBody').html('<tr><th>Employee ID</th><td>' + esc(e.empId) +
          '</td><th>Name</th><td>' + esc(e.fullname) +
          '</td></tr><tr><th>Email</th><td>' + esc(e.email) +
          '</td><th>Department</th><td>' + esc(e.departmentName) +
          '</td></tr><tr><th>Position</th><td>' + esc(e.position) +
          '</td><th>Status</th><td>' + esc(status) +
          '</td></tr><tr><th>Extension</th><td>' + esc(e.extension_number) +
          '</td><th>Area</th><td>' + esc(e.area) +
          '</td></tr><tr><th>Building</th><td colspan="3">' + esc(e.building) +
          '</td></tr>');
        let rows = (data.transactions || []).map(function(t) {
          let returned = t.return_id ? esc(t.return_no) + '<br><small>' + esc(t
            .return_date) + '</small>' : '-';
          let state = t.return_id ? 'Returned' : 'Currently Assigned';
          return '<tr><td><button type="button" class="btn btn-link btn-xs" data-asset-details="' + esc(t.assetNum) + '"><strong>' + esc(t.assetNum) + '</strong></button><br><small>' +
            esc(t.category_name) + '</small></td><td>' + esc(t.asset_description) +
            (t.serial_number ? '<br><small>SN: ' + esc(t.serial_number) +
              '</small>' : '') + '</td><td>' + esc(t.issue_no) + '<br><small>' +
            esc(t.issue_date) + '</small></td><td>' + esc(t.condition_out) +
            '</td><td>' + returned + '</td><td>' + esc(t.condition_in) +
            '</td><td>' + esc(state) + '</td></tr>';
        }).join('');
        $('#employeeTransactionsBody').html(rows ||
          '<tr><td colspan="7" class="text-center">No asset transactions found for this employee.</td></tr>'
          );
        $('#employeeDetailsLoading').addClass('hide');
        $('#employeeDetailsContent').removeClass('hide');
      }).fail(function(xhr) {
        let message = (xhr.responseJSON && xhr.responseJSON.error) ||
          'Unable to load employee details.';
        $('#employeeDetailsLoading').addClass('hide');
        $('#employeeDetailsError').text(message).removeClass('hide');
      });
    });
  });
</script>
<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
