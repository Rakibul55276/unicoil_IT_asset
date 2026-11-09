<div class="page-content">
  <div class="container-fluid">
    <div><?php Flasher::msgInfo(); ?></div>
    <p><a href="<?= BASEURL ?>/Location/add" class="btn btn-primary"><i class="fa fa-plus"></i> Add
        Location</a></p>
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>#</th>
            <th>Location ID</th>
            <th>Location Name</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody><?php
$n = 0;
foreach ($data["locations"] as $row):
    $n++; ?><tr>
            <td><?= $n ?></td>
            <td><?= htmlspecialchars(
    $row["location"],
) ?></td>
            <td><?= htmlspecialchars(
    $row["description"],
) ?></td>
            <td><a class="btn btn-success btn-xs" href="<?= BASEURL ?>/Location/edit/<?= rawurlencode(
    $row["location"],
) ?>"><i class="fa fa-pencil"></i></a> <a class="btn btn-danger btn-xs" href="<?= BASEURL ?>/Location/delete/<?= rawurlencode(
    $row["location"],
) ?>" onclick="return confirm('Are you sure you want to delete this record?');"><i
                  class="fa fa-trash"></i></a></td>
          </tr><?php
endforeach;
if (!$n): ?><tr>
            <td colspan="4" class="text-center">No locations found.</td>
          </tr><?php endif;
?></tbody>
      </table>
    </div>
  </div>
</div>