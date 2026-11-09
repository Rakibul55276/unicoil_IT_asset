<?php $v = $data["vendor"] ?? [];
$edit = !empty($v);
?><div class="page-content">
  <div class="container-fluid">
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title"><?= $edit
    ? "Edit"
    : "Add" ?> Vendor</h4>
      </div>
      <div class="widget-body">
        <div class="widget-main">
          <form method="post" action="<?= htmlspecialchars(
     $data["post_url"],
 ) ?>"><?php if ($edit): ?><input type="hidden" name="vendorId" value="<?= (int) $v[
    "vendor_id"
] ?>"><?php endif; ?><div class="row">
              <div class="col-md-6 form-group"><label>Vendor Name <span
                    class="red">*</span></label><input name="vendorName" class="form-control"
                  maxlength="150" required value="<?= htmlspecialchars(
    $v["vendor_name"] ?? "",
) ?>"></div>
              <div class="col-md-6 form-group"><label>Contact Person</label><input
                  name="contactPerson" class="form-control" maxlength="120" value="<?= htmlspecialchars(
    $v["contact_person"] ?? "",
) ?>"></div>
              <div class="col-md-6 form-group"><label>Email</label><input type="email" name="email"
                  class="form-control" maxlength="190" value="<?= htmlspecialchars(
    $v["email"] ?? "",
) ?>"></div>
              <div class="col-md-6 form-group"><label>Phone</label><input name="phone"
                  class="form-control" maxlength="50" value="<?= htmlspecialchars(
    $v["phone"] ?? "",
) ?>"></div>
              <div class="col-md-6 form-group"><label>Tax / Registration Number</label><input
                  name="taxNumber" class="form-control" maxlength="80" value="<?= htmlspecialchars(
    $v["tax_number"] ?? "",
) ?>"></div>
              <div class="col-md-6 form-group"><label>Address</label><textarea name="address"
                  class="form-control" maxlength="500" rows="2"><?= htmlspecialchars(
    $v["address"] ?? "",
) ?></textarea></div>
              <div class="col-md-6 form-group"><label><input type="checkbox" name="active" value="1" <?= !isset(
    $v["active"],
) || $v["active"]
    ? "checked"
    : "" ?>> Active Vendor</label></div>
            </div><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i>
              Save</button> <a class="btn btn-default" href="<?= BASEURL ?>/Vendor">Back</a></form>
        </div>
      </div>
    </div>
  </div>
</div>