<div class="page-content">
  <div class="container-fluid">

    <div class="col-xs-12 col-sm-12">
      <div id="msg-alert">
        <?php Flasher::msgInfo(); ?>
      </div>
      <div class="widget-box">
        <div class="widget-header">
          <h4 class="widget-title"><?= $data["category"] ? "Edit" : "Create" ?> Asset Category</h4>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="widget-body">
              <form action="<?= $data["post_url"] ?>" method="post">
                <?php if ($data["category"]): ?><input type="hidden" name="assetKat" value="<?= htmlspecialchars(
    $data["category"]["assetKat"],
) ?>"><?php endif; ?>
                <div class="widget-main">
                  <div>
                    <label for="form-field-mask-1">
                      Asset Category Name
                    </label>

                    <div class="form-group">
                      <input type="text" id="category-name" name="Description" class="form-control"
                        required value="<?= htmlspecialchars(
             $data["category"]["Description"] ?? "",
         ) ?>">
                    </div>
                  </div>
                  <div>
                    <label for="category-prefix">Asset Number Prefix</label>
                    <div class="form-group">
                      <input type="text" id="category-prefix" name="asset_prefix"
                        class="form-control" minlength="2" maxlength="6"
                        pattern="[A-Za-z][A-Za-z0-9]{1,5}" required value="<?= htmlspecialchars(
             $data["category"]["asset_prefix"] ?? "",
         ) ?>" placeholder="Example: LT">
                      <p class="help-block">New assets use this prefix, for example
                        <strong>LT0001</strong>. Changing it affects only newly received assets.</p>
                    </div>
                  </div>
                  <div>
                    <button type="submit"
                      class="btn btn-primary"><?= $data["category"] ? "Update" : "Save" ?></button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <div class="col-md-6">

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  // window.setInterval(hideAlert, 3500);
  // function hideAlert() { 
  // 	$('#msg-alert').html(content);
  // }
</script>
<script>
  (function() {
    var name = document.getElementById('category-name'),
      prefix = document.getElementById('category-prefix'),
      editing = <?= $data[
      "category"
  ]
      ? "true"
      : "false" ?>;
    var known = {
      laptop: 'LT',
      desktop: 'DT',
      'desktop computer': 'DT',
      monitor: 'MN',
      printer: 'PR',
      server: 'SV',
      network: 'NW',
      projector: 'PJ'
    };
    name.addEventListener('input', function() {
      if (editing || prefix.dataset.changed === '1') return;
      var clean = this.value.trim().toLowerCase(),
        words = clean.match(/[a-z0-9]+/g) || [];
      prefix.value = known[clean] || (words.length > 1 ? (words[0][0] + words[1][0]) : ((
        words[0] || 'AS').slice(0, 2))).toUpperCase();
    });
    prefix.addEventListener('input', function() {
      this.dataset.changed = '1';
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
    });
  })();
</script>