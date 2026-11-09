<?php $no = 0; ?>
<div class="row">
  <div id="msg-alert">
    <?php Flasher::msgInfo(); ?>
  </div>
  <div class="col-md-12">
    <a href="<?= BASEURL ?>/User/create" class="btn btn-primary mb-3">Create New User</a>
  </div>
  <div class="col-xs-12">
    <br>
    <div>
      <table id="dynamic-table" class="table table-striped table-bordered table-hover">
        <thead>
          <tr>
            <th width="50px;">No.</th>
            <th width="120px;">Username</th>
            <th>Email</th>
            <th>Name</th>
            <th>Employee</th>
            <th>Workflow Roles</th>
            <th></th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($data["rdata"] as $rdata): ?>
          <?php $no++; ?>
          <tr>
            <td><?= $no ?></td>
            <td>
              <a href="#"><?= $rdata["username"] ?></a>
            </td>
            <td><?= $rdata["email"] ?></td>
            <td><?= $rdata["nama"] ?></td>
            <td><?= !empty($rdata["empId"])
                ? htmlspecialchars($rdata["empId"] . " - " . ($rdata["employeeName"] ?? ""))
                : "Not linked" ?></td>
            <td>
              <?php foreach (array_filter(explode(",", (string) ($rdata["roles"] ?? ""))) as $role): ?>
              <span class="label label-info" style="display:inline-block;margin:2px"><?= htmlspecialchars(str_replace("_", " ", $role)) ?></span>
              <?php endforeach; ?>
            </td>
            <td>
              <div class="hidden-sm hidden-xs action-buttons">

                <a class="green"
                  href="<?= BASEURL ?>/User/edit/<?= rawurlencode($rdata["username"]) ?>">
                  <i class="ace-icon fa fa-pencil bigger-130"></i>
                </a>

                <form action="<?= BASEURL ?>/User/hapusData" method="post"
                  style="display:inline" onsubmit="return confirm('Are you sure you want to delete this record?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                      (string) ($data["deleteCsrf"] ?? ""),
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>">
                  <input type="hidden" name="username" value="<?= htmlspecialchars(
                      (string) $rdata["username"],
                      ENT_QUOTES,
                      "UTF-8",
                  ) ?>">
                  <button type="submit" class="btn btn-link red" style="padding:0;border:0">
                    <i class="ace-icon fa fa-trash-o bigger-130"></i>
                  </button>
                </form>
              </div>

              <div class="hidden-md hidden-lg">
                <div class="inline pos-rel">
                  <button class="btn btn-minier btn-yellow dropdown-toggle" data-toggle="dropdown"
                    data-position="auto">
                    <i class="ace-icon fa fa-caret-down icon-only bigger-120"></i>
                  </button>

                  <ul
                    class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close">
                    <li>
                      <a href="<?= BASEURL ?>/User/edit/<?= $rdata[
    "username"
] ?>" class="tooltip-success" data-rel="tooltip" title="Edit">
                        <span class="green">
                          <i class="ace-icon fa fa-pencil-square-o bigger-120"></i>
                        </span>
                      </a>
                    </li>

                    <li>
                      <form action="<?= BASEURL ?>/User/hapusData" method="post"
                        onsubmit="return confirm('Are you sure you want to delete this record?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                            (string) ($data["deleteCsrf"] ?? ""),
                            ENT_QUOTES,
                            "UTF-8",
                        ) ?>">
                        <input type="hidden" name="username" value="<?= htmlspecialchars(
                            (string) $rdata["username"],
                            ENT_QUOTES,
                            "UTF-8",
                        ) ?>">
                        <button type="submit" class="btn btn-link tooltip-error" title="Delete">
                          <span class="red"><i class="ace-icon fa fa-trash-o bigger-120"></i></span>
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
