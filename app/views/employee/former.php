<div class="page-content">
  <div class="container-fluid">

    <!-- Search -->
    <div class="row mb-3">
      <div class="col-md-6 col-12">
        <input type="text" id="employee-search" class="form-control"
          placeholder="Search Former Employees...">
      </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-warning text-white">
        <h5 class="mb-0">Former Employee List</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-bordered table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>No.</th>
                <th>Employee Id</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Active Status</th>
                <th>Asset Numbers</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="employee-table-body">
              <?php if (!empty($former) && is_array($former)): ?>
              <?php
                            $no = 0;
                            foreach ($former as $rdata):
                                $no++; ?>
              <tr>
                <td><?= $no ?></td>
                <td><?= $rdata["empId"] ?></td>
                <td><?= $rdata["firstname"] ?? "-" ?></td>
                <td><?= $rdata["lastname"] ?? "-" ?></td>
                <td><?= $rdata["fullname"] ?? "-" ?></td>
                <td><?= $rdata["department"] ?? "-" ?></td>
                <td><?= isset($rdata["aktif"])
                                    ? ($rdata["aktif"]
                                        ? "Active"
                                        : "Inactive")
                                    : "-" ?></td>
                <td><?php if (!empty($rdata["assetNums"]) && $rdata["assetNums"] !== "-"): ?><?php foreach (explode(",", $rdata["assetNums"]) as $assetNum): ?><button type="button" class="btn btn-link btn-xs" data-asset-details="<?= htmlspecialchars(trim($assetNum), ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars(trim($assetNum)) ?></button><?php endforeach; ?><?php else: ?>-<?php endif; ?></td>
                <td>
                  <a class="green" href="<?= BASEURL ?>/Employee/edit/<?= $rdata[
    "empId"
] ?>">
                    <i class="ace-icon fa fa-pencil bigger-130"></i>
                  </a>
                  <a class="red" href="<?= BASEURL ?>/Employee/hapusData/<?= $rdata[
    "empId"
] ?>" onclick="return confirm('Are you sure you want to delete this record?');">
                    <i class="ace-icon fa fa-trash-o bigger-130"></i>
                  </a>
                </td>
              </tr>
              <?php
                            endforeach;
                            ?>
              <?php else: ?>
              <tr>
                <td colspan="9" class="text-center">No former employees found.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  $(document).ready(function() {
    $('#employee-search').on('keyup', function() {
      let value = $(this).val().toLowerCase();
      $('#employee-table-body tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
      });
    });
  });
</script>
