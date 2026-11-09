<?php $row = $data["locationData"] ?? [];
$edit = !empty($row);
?><div class="page-content">
  <div class="container-fluid">
    <div class="widget-box">
      <div class="widget-header">
        <h4><?= $edit
    ? "Edit"
    : "Add" ?> Location</h4>
      </div>
      <div class="widget-body">
        <div class="widget-main">
          <form action="<?= $data[
     "post_url"
 ] ?>" method="post"><?php if (
    $edit
): ?><input type="hidden" name="location" value="<?= htmlspecialchars(
    $row["location"],
) ?>"><?php endif; ?><div class="form-group"><label>Location Name</label><input name="Description"
                class="form-control" maxlength="100" required value="<?= htmlspecialchars(
    $row["description"] ?? "",
) ?>"></div><button class="btn btn-primary" type="submit"><?= $edit
    ? "Update"
    : "Save" ?></button> <a class="btn btn-default" href="<?= BASEURL ?>/Location">Back</a></form>
        </div>
      </div>
    </div>
  </div>
</div>