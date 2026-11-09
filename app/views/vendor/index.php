<div class="page-content">
  <div class="container-fluid">
    <div><?php Flasher::msgInfo(); ?></div>
    <p><a href="<?= BASEURL ?>/Vendor/add" class="btn btn-primary"><i class="fa fa-plus"></i> Add
        Vendor</a></p>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>#</th>
            <th>Vendor</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Tax Number</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody><?php
$n = 0;
foreach ($data["vendors"] as $v):
    $n++; ?><tr>
            <td><?= $n ?></td>
            <td><?= htmlspecialchars(
    $v["vendor_name"],
) ?></td>
            <td><?= htmlspecialchars($v["contact_person"] ?? "-") ?></td>
            <td><?= htmlspecialchars(
    $v["email"] ?? "-",
) ?></td>
            <td><?= htmlspecialchars($v["phone"] ?? "-") ?></td>
            <td><?= htmlspecialchars(
    $v["tax_number"] ?? "-",
) ?></td>
            <td><span class="label label-<?= $v["active"] ? "success" : "default" ?>"><?= $v["active"]
    ? "Active"
    : "Inactive" ?></span></td>
            <td><a class="btn btn-success btn-xs" href="<?= BASEURL ?>/Vendor/edit/<?= (int) $v[
    "vendor_id"
] ?>"><i class="fa fa-pencil"></i> Edit</a>
              <form method="post" action="<?= BASEURL ?>/Vendor/delete" style="display:inline"
                onsubmit="return confirm('Are you sure you want to delete this vendor?');"><input
                  type="hidden" name="csrf_token" value="<?= htmlspecialchars(
    $data["deleteCsrf"],
    ENT_QUOTES,
    "UTF-8",
) ?>"><input type="hidden" name="vendorId" value="<?= (int) $v[
    "vendor_id"
] ?>"><button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i>
                  Delete</button></form>
            </td>
          </tr><?php
endforeach;
if (!$n): ?><tr>
            <td colspan="8" class="text-center">No vendors found.</td>
          </tr><?php endif;
?></tbody>
      </table>
    </div>
  </div>
</div>