<div class="row"><div class="col-xs-12">
  <?php Flasher::msgInfo(); ?>
  <div class="widget-box"><div class="widget-header"><h4 class="widget-title"><i class="fa fa-recycle"></i> Submit Asset for Scrap Approval</h4></div>
    <div class="widget-body"><div class="widget-main">
      <div class="alert alert-warning">Only returned/in-stock assets are available. Assigned assets must be returned before a scrap request can be submitted.</div>
      <form action="<?= BASEURL ?>/Scrap/store" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data["csrf"]) ?>">
        <div class="form-group"><label for="assetNum">Asset <span class="red">*</span></label>
          <select name="assetNum" id="assetNum" class="form-control searchable-select"
            data-placeholder="Type at least 2 characters"
            data-remote-url="<?= BASEURL ?>/Scrap/searchAvailableAssets" required>
            <option value=""></option><?php foreach ($data["assets"] as $asset): ?>
            <option value="<?= htmlspecialchars($asset["assetNum"]) ?>"><?= htmlspecialchars($asset["assetNum"] . " - " . $asset["Description"] . ($asset["serial_number"] ? " - " . $asset["serial_number"] : "") . " (" . ($asset["categoryName"] ?: "Uncategorized") . ")") ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label for="reason">Scrap Reason <span class="red">*</span></label><textarea name="reason" id="reason" class="form-control" rows="4" maxlength="500" required placeholder="Describe the condition, failure or business reason for scrapping"></textarea></div>
        <div class="text-right"><a href="<?= BASEURL ?>/Scrap" class="btn btn-default">Cancel</a> <button type="submit" class="btn btn-danger"><i class="fa fa-paper-plane"></i> Submit to IT Manager</button></div>
      </form>
    </div></div>
  </div>
</div></div>
