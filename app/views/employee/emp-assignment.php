<div class="row">
  <div class="col-sm-12" id="msg-info"></div>

  <!-- Back button if employee data exists -->
  <?php if (isset($data["empdt"]["empId"]) && $data["empdt"]["empId"]): ?>
  <div class="col-md-12">
    <a href="<?= BASEURL ?>/Employee/department" class="btn btn-primary mb-3">BACK</a>
  </div>
  <?php endif; ?>

  <div class="col-md-12">
    <br>
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title">Assign User Department</h4>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="widget-body">
            <div class="col-md-6">
              <input type="hidden" name="id" id="id">

              <!-- Employee ID -->
              <div class="form-group">
                <label for="empId">Employee ID</label>
                <input type="text" name="empId" id="empId" class="form-control"
                  placeholder="Input Employee Id" required
                  data-url="<?= BASEURL ?>/Employee/getEmpById" value="<?= isset($data["empdt"]["empId"])
                                           ? htmlspecialchars($data["empdt"]["empId"])
                                           : "" ?>">
              </div>

              <!-- Employee Name -->
              <div class="form-group">
                <label for="empName">Personal Name</label>
                <input type="text" name="empName" id="empName" class="form-control" readonly
                  required value="<?= isset($data["empdt"]["fullname"])
                                           ? htmlspecialchars($data["empdt"]["fullname"])
                                           : "" ?>">
              </div>

              <!-- Select Department -->
              <div class="form-group">
                <label for="detpId">Select Department</label>
                <select name="detpId" id="detpId" class="form-control">
                  <?php if (
                                        isset($data["empdt"]["deptId"]) &&
                                        isset($data["empdt"]["deptName"])
                                    ): ?>
                  <option value="<?= htmlspecialchars(
                                            $data["empdt"]["deptId"],
                                        ) ?>">
                    <?= htmlspecialchars($data["empdt"]["deptName"]) ?>
                  </option>
                  <?php else: ?>
                  <option value="">-- Select Department --</option>
                  <?php endif; ?>

                  <?php if (
                                        isset($data["deptdt"]) &&
                                        is_array($data["deptdt"])
                                    ): ?>
                  <?php foreach ($data["deptdt"] as $rdata): ?>
                  <option value="<?= htmlspecialchars(
                                                $rdata["deptId"],
                                            ) ?>">
                    <?= htmlspecialchars($rdata["deptName"]) ?>
                  </option>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>

              <!-- Action Buttons -->
              <div class="form-group">
                <button class="btn btn-primary" id="btn-register"
                  data-url="<?= BASEURL ?>/Employee/setEmpDept">Assign</button>
                <a href="<?= BASEURL ?>/Employee/department" class="btn btn-primary">Show Employee
                  Department</a>
              </div>

            </div> <!-- end col-md-6 -->
          </div> <!-- end widget-body -->
        </div> <!-- end col-md-12 -->
      </div> <!-- end row -->
    </div> <!-- end widget-box -->
  </div> <!-- end col-md-12 -->
</div> <!-- end row -->