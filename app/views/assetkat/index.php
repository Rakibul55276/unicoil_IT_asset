<div class="page-content">
  <div class="container-fluid">
    <?php $no = 0; ?>
    <div class="row">
      <div id="msg-alert">
        <?php Flasher::msgInfo(); ?>
      </div>
      <div class="col-md-12">
        <a href="<?= BASEURL ?>/Assetkat/add" class="btn btn-primary mb-3">Add New Asset
          Category</a>
      </div>
      <div class="col-xs-12">
        <br>
        <div>
          <table id="dynamic-table" class="table table-striped table-bordered table-hover">
            <thead>
              <tr>
                <th width="50px;">No.</th>
                <th width="120px;">Category Id</th>
                <th>Category Name</th>
                <th>Asset Prefix</th>
                <th style="display: none;"></th>
                <th></th>

              </tr>
            </thead>

            <tbody>
              <?php foreach ($data["rdata"] as $rdata): ?>
              <?php $no++; ?>
              <tr>
                <td><?= $no ?></td>
                <td>
                  <a href="#"><?= $rdata["assetKat"] ?></a>
                </td>
                <td><?= $rdata["Description"] ?></td>
                <td><strong><?= htmlspecialchars($rdata["asset_prefix"] ?? "-") ?></strong></td>
                <td style="display: none;"></td>
                <td>
                  <div class="hidden-sm hidden-xs action-buttons">

                    <a class="green" href="<?= BASEURL ?>/Assetkat/edit/<?= $rdata["assetKat"] ?>">
                      <i class="ace-icon fa fa-pencil bigger-130"></i>
                    </a>

                    <form method="post" style="display:inline"
                      action="<?= BASEURL ?>/Assetkat/hapusData/<?= rawurlencode((string) $rdata["assetKat"]) ?>"
                      onsubmit="return confirm('Delete this category? Categories used by assets cannot be deleted.');">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                          (string) ($data["deleteCsrf"] ?? ""),
                          ENT_QUOTES,
                          "UTF-8",
                      ) ?>">
                      <button type="submit" class="red" title="Delete"
                        style="border:0;background:transparent;padding:0">
                        <i class="ace-icon fa fa-trash-o bigger-130"></i>
                      </button>
                    </form>
                  </div>

                  <div class="hidden-md hidden-lg">
                    <div class="inline pos-rel">
                      <button class="btn btn-minier btn-yellow dropdown-toggle"
                        data-toggle="dropdown" data-position="auto">
                        <i class="ace-icon fa fa-caret-down icon-only bigger-120"></i>
                      </button>

                      <ul
                        class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close">
                        <!-- <li>
												<a href="<?= BASEURL ?>/assetkat/edit/<?= $rdata[
    "assetKat"
] ?>" class="tooltip-success" data-rel="tooltip" title="Edit">
													<span class="green">
														<i class="ace-icon fa fa-pencil-square-o bigger-120"></i>
													</span>
												</a>
											</li> -->

                        <li>
                          <form method="post"
                            action="<?= BASEURL ?>/Assetkat/hapusData/<?= rawurlencode((string) $rdata["assetKat"]) ?>"
                            onsubmit="return confirm('Delete this category? Categories used by assets cannot be deleted.');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                                (string) ($data["deleteCsrf"] ?? ""),
                                ENT_QUOTES,
                                "UTF-8",
                            ) ?>">
                            <button type="submit" class="tooltip-error" data-rel="tooltip"
                              title="Delete" style="border:0;background:transparent">
                              <span class="red"><i
                                  class="ace-icon fa fa-trash-o bigger-120"></i></span>
                            </button>
                          </form>
                        </li>
                      </ul>
                    </div>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
