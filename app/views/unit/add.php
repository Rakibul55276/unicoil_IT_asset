<div class="col-xs-12 col-sm-12">
  <div id="msg-alert">
    <?php Flasher::msgInfo(); ?>
  </div>
  <div class="widget-box">
    <div class="widget-header">
      <h4 class="widget-title"><?= $data["unitData"] ? "Edit" : "Create" ?> Asset Unit</h4>
    </div>
    <div class="row">
      <div class="col-md-6">
        <div class="widget-body">
          <form action="<?= $data["post_url"] ?>" method="post">
            <div class="widget-main">
              <?php if (!empty($data["unitData"])): ?>
              <input type="hidden" name="original_unit" value="<?= htmlspecialchars(
                  (string) $data["unitData"]["unit"],
                  ENT_QUOTES,
                  "UTF-8",
              ) ?>">
              <?php endif; ?>
              <div>
                <label for="form-field-mask-1">
                  Unit Code
                </label>

                <div class="form-group">
                  <input type="text" name="unit" class="form-control" maxlength="10" required value="<?= htmlspecialchars(
             $data["unitData"]["unit"] ?? "",
         ) ?>">
                  <?php if (!empty($data["unitData"])): ?>
                  <p class="help-block"><i class="fa fa-shield"></i> Only an Administrator can change this code. Existing asset records will be updated automatically.</p>
                  <?php endif; ?>
                </div>
              </div>
              <div>
                <label for="form-field-mask-1">
                  Description
                </label>

                <div class="form-group">
                  <input type="text" name="Description" class="form-control" maxlength="50" required value="<?= htmlspecialchars(
             $data["unitData"]["description"] ?? "",
         ) ?>">
                </div>
              </div>
              <div>
                <button type="submit"
                  class="btn btn-primary"><?= $data["unitData"] ? "Update" : "Save" ?></button>
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
