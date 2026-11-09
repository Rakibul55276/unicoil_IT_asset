<div class="page-content">
  <div class="container-fluid">

    <?php $no = 0; ?>
    <div class="row">
      <div id="msg-alert">
        <?php Flasher::msgInfo(); ?>
      </div>
      <div class="col-md-12">
        <a href="<?= BASEURL ?>/Department/new/department" class="btn btn-primary mb-3">Add New
          Department</a>
      </div>
      <div class="col-xs-12">
        <br>
        <div>
          <table id="dynamic-table" class="table table-striped table-bordered table-hover">
            <thead>
              <tr>
                <th width="50px;">No.</th>
                <th width="120px;">Deparment Id</th>
                <th width="100px;">Prefix</th>
                <th>Deparment Name</th>
                <th>Area</th>
                <th>Building</th>
                <th>Section Head</th>
                <th>Assigned Items</th>
                <th></th>
                <th style="display:none;"></th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($data["rdata"] as $rdata): ?>
              <?php $no++; ?>
              <tr>
                <td><?= $no ?></td>
                <td>
                  <a href="#"><?= $rdata["deptId"] ?></a>
                </td>
                <td><strong><?= htmlspecialchars($rdata["deptPrefix"] ?? "-") ?></strong></td>
                <td><?= $rdata["deptName"] ?></td>
                <td><?= htmlspecialchars($rdata["area"] ?: "-") ?></td>
                <td><?= htmlspecialchars($rdata["building"] ?: "-") ?></td>
                <td><?= htmlspecialchars($rdata["headName"] ?: "Not assigned") ?></td>
                <td><span class="badge badge-primary"><?= (int) ($rdata["assignedAssetCount"] ??
           0) ?></span><?php if (!empty($rdata["assignedAssetNums"])): ?><div><?php foreach (
    explode(",", $rdata["assignedAssetNums"])
    as $assetNum
): ?><button type="button" class="btn btn-link btn-xs asset-detail-link" data-asset="<?= htmlspecialchars(
    $assetNum,
) ?>"><?= htmlspecialchars(
    $assetNum,
) ?></button><?php endforeach; ?></div><?php else: ?> <span class="text-muted">No
                    assets</span><?php endif; ?></td>
                <td>
                  <div class="hidden-sm hidden-xs action-buttons">
                    <a class="green" href="<?= BASEURL ?>/Department/edit/<?= $rdata[
    "deptId"
] ?>"><i class="ace-icon fa fa-pencil bigger-130"></i></a>
                    <a class="red" href="<?= BASEURL ?>/Department/hapusData/<?= $rdata[
    "deptId"
] ?>" onclick="return confirm('Are you sure you want to delete this record?');">
                      <i class="ace-icon fa fa-trash-o bigger-130"></i>
                    </a>
                  </div>

                  <div class="hidden-md hidden-lg">
                    <div class="inline pos-rel">
                      <button class="btn btn-minier btn-yellow dropdown-toggle"
                        data-toggle="dropdown" data-position="auto">
                        <i class="ace-icon fa fa-caret-down icon-only bigger-120"></i>
                      </button>

                      <ul
                        class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close">

                        <li>
                          <a href="<?= BASEURL ?>/Department/hapusData/<?= $rdata[
    "deptId"
] ?>" class="tooltip-error" data-rel="tooltip" title="Delete"
                            onclick="return confirm('Are you sure you want to delete this record?');">
                            <span class="red">
                              <i class="ace-icon fa fa-trash-o bigger-120"></i>
                            </span>
                          </a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </td>

                <td style="display:none;"></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . "/../templates/asset_details_modal.php"; ?>
