<div class="page-content">
  <div class="container-fluid">

    <div class="col-xs-12 col-sm-12">
      <div id="msg-alert">
        <?php Flasher::msgInfo(); ?>
      </div>

      <div class="widget-box">
        <div class="widget-header">
          <h4 class="widget-title">
            <?= !empty($data["department"]) ? "Edit" : "Create" ?> Department
          </h4>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="widget-body">

              <form action="<?= htmlspecialchars($data["post_url"]) ?>" method="post">

                <?php if (!empty($data["department"])): ?>
                <input type="hidden" name="deptId" value="<?= htmlspecialchars(
                                            $data["department"]["deptId"],
                                        ) ?>">
                <?php endif; ?>

                <div class="widget-main">

                  <!-- Department name -->
                  <div>
                    <label for="deptName">Department Name</label>

                    <div class="form-group">
                      <input type="text" id="deptName" name="deptName" class="form-control" required
                        maxlength="50" value="<?= htmlspecialchars(
                                                    $data["department"]["deptName"] ?? "",
                                                ) ?>">
                      <p class="help-block">Maximum 50 characters.</p>
                    </div>
                  </div>

                  <div>
                    <label for="deptPrefix">Department Prefix</label>
                    <div class="form-group">
                      <input type="text" id="deptPrefix" name="deptPrefix" class="form-control"
                        required minlength="2" maxlength="10" pattern="[A-Za-z][A-Za-z0-9-]{1,9}"
                        style="text-transform:uppercase" value="<?= htmlspecialchars(
                                                    $data["department"]["deptPrefix"] ?? "",
                                                ) ?>">
                      <p class="help-block">Unique code used on PDF forms, for example IT, COM, or
                        FIN.</p>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="area">Area</label>
                    <input type="text" id="area" name="area" class="form-control"
                      maxlength="100" placeholder="For example: Main Plant"
                      value="<?= htmlspecialchars($data["department"]["area"] ?? "") ?>">
                  </div>

                  <div class="form-group">
                    <label for="building">Building</label>
                    <input type="text" id="building" name="building" class="form-control"
                      maxlength="100" placeholder="For example: Administration Building"
                      value="<?= htmlspecialchars($data["department"]["building"] ?? "") ?>">
                  </div>

                  <?php if (!empty($data["department"])): ?>
                  <div>
                    <label for="headEmpId">Section Head</label>

                    <div class="form-group">
                      <select name="headEmpId" id="headEmpId"
                        class="form-control searchable-select"
                        data-placeholder="Select one section head">
                        <option value="">No section head assigned</option>

                        <?php foreach (
                                                    $data["employees"] ?? []
                                                    as $employee
                                                ): ?>
                        <?php
                                                    $isSelected =
                                                        (string) $employee["empId"] ===
                                                        (string) ($data["department"]["selectedHeadEmpId"] ?? "");
                                                    ?>

                        <option value="<?= (int) $employee["empId"] ?>"
                          <?= $isSelected ? "selected" : "" ?>>
                          <?= htmlspecialchars(
                                                            $employee["empId"] .
                                                                " - " .
                                                                $employee["fullname"] .
                                                                (!empty($employee["department"])
                                                                    ? " (" .
                                                                        $employee["department"] .
                                                                        ")"
                                                                    : ""),
                                                        ) ?>
                        </option>
                        <?php endforeach; ?>
                      </select>

                      <p class="help-block">
                        Select one active employee from this department. Multiple section heads are
                        currently disabled.
                      </p>
                    </div>
                  </div>
                  <?php /* Multiple Section Heads and Organizational Superior Manager
                     are disabled for the current approval flow. */ ?>
                  <?php endif; ?>

                  <div>
                    <button type="submit" class="btn btn-primary">
                      Save
                    </button>

                    <a href="<?= BASEURL ?>/Department" class="btn btn-default">
                      Back
                    </a>
                  </div>

                </div>
              </form>

            </div>
          </div>

          <div class="col-md-6"></div>
        </div>
      </div>
    </div>

  </div>
</div>
