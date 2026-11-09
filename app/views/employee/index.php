<div class="page-content">
  <div class="container-fluid">

    <!-- Success / Error Messages -->
    <div id="formSuccess" class="alert alert-success" style="display:none;"></div>
    <div id="formError" class="alert alert-danger" style="display:none;"></div>

    <form id="employeeForm" action="<?= $data["post_url"] ?>" method="post">
      <?php if (isset($data["empData"]["empId"])): ?>
      <input type="hidden" name="originalEmpId" value="<?= (int) $data["empData"][
          "empId"
      ] ?>">
      <?php endif; ?>
      <div class="row">
        <div class="col-md-6">
          <div class="widget-body">
            <div class="widget-main">

              <!-- Employee ID -->
              <div>
                <label for="empId">Employee ID</label>
                <div class="form-group">
                  <input type="number" name="empId" id="empId" value="<?= htmlspecialchars(
                                               $data["empData"]["empId"] ?? "",
                                           ) ?>" class="form-control" min="1" max="2147483647" required <?= isset(
                                               $data["empData"]["empId"],
                                           ) && empty($data["canEditEmployeeId"])
                                               ? "readonly"
                                               : "" ?>>
                  <?php if (
                      isset($data["empData"]["empId"]) &&
                      !empty($data["canEditEmployeeId"])
                  ): ?>
                  <p class="help-block"><i class="fa fa-shield"></i> System Administrator only.
                    Changing this ID also updates linked asset and workflow records.</p>
                  <?php endif; ?>
                </div>
              </div>

              <!-- First Name -->
              <div>
                <label for="fName">First Name</label>
                <div class="form-group">
                  <input type="text" name="fName" value="<?= htmlspecialchars(
                                               $data["empData"]["firstname"] ?? "",
                                           ) ?>" class="form-control" required>
                </div>
              </div>

              <!-- Last Name -->
              <div>
                <label for="lName">Last Name</label>
                <div class="form-group">
                  <input type="text" name="lName" value="<?= htmlspecialchars(
                                               $data["empData"]["lastname"] ?? "",
                                           ) ?>" class="form-control" required>
                </div>
              </div>

              <!-- Email -->
              <div>
                <label for="email">Email Address</label>
                <div class="form-group">
                  <input type="email" name="email" id="email" value="<?= htmlspecialchars(
                                               $data["empData"]["email"] ?? "",
                                           ) ?>" class="form-control" maxlength="190"
                    placeholder="employee@company.com" required>
                  <p class="help-block">Asset assignment details and photos will be sent here.</p>
                </div>
              </div>

              <div class="form-group"><label for="position">Position</label><input type="text"
                  name="position" id="position" class="form-control" maxlength="100" value="<?= htmlspecialchars(
                                $data["empData"]["position"] ?? "",
                            ) ?>"></div>
              <div class="form-group"><label for="area">Area</label><input type="text" name="area"
                  id="area" class="form-control" maxlength="100" value="<?= htmlspecialchars(
                                $data["empData"]["area"] ?? "",
                            ) ?>"></div>
              <div class="form-group"><label for="building">Building</label><input type="text"
                  name="building" id="building" class="form-control" maxlength="100" value="<?= htmlspecialchars(
                                $data["empData"]["building"] ?? "",
                            ) ?>"></div>
              <div class="form-group"><label for="extensionNumber">Extension Number</label><input
                  type="text" name="extensionNumber" id="extensionNumber" class="form-control"
                  maxlength="30" value="<?= htmlspecialchars(
                                $data["empData"]["extension_number"] ?? "",
                            ) ?>"></div>
              <?php
/* Section is temporarily hidden; its stored data is retained.
                            <div class="form-group"><label for="section">Section</label><input type="text" name="section" id="section" class="form-control" maxlength="100" value="<?= htmlspecialchars($data['empData']['section']??''); ?>">
            </div>
            */
            ?>

            <!-- Department -->
            <div class="form-group">
              <label for="department">Department</label>
              <select name="department" id="department" class="form-control" required>
                <?php
                                    $deptSelected = $data["empData"]["deptId"] ?? "";
                                    usort($data["departments"], function ($a, $b) {
                                        return strcasecmp($a["deptName"], $b["deptName"]);
                                    });
                                    ?>
                <option value="">Select Department</option>
                <?php foreach ($data["departments"] as $dept): ?>
                <option value="<?= $dept["deptId"] ?>" <?= $deptSelected ==
$dept["deptId"]
    ? "selected"
    : "" ?>>
                  <?= htmlspecialchars($dept["deptName"]) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php /* Immediate Superior Manager field hidden from the employee form.
            Existing supervisor data is retained in the database for future use. */ ?>

            <!-- Employee Status -->
            <div>
              <label for="status">Employee Status</label>
              <div class="form-group">
                <?php $status = $data["empData"]["aktif"] ?? "1"; ?>
                <select name="status" id="status" class="form-control" required>
                  <option value="1" <?= $status == "1"
                                            ? "selected"
                                            : "" ?>>Active</option>
                  <option value="0" <?= $status == "0"
                                            ? "selected"
                                            : "" ?>>Inactive</option>
                </select>
              </div>
            </div>

            <!-- Buttons -->
            <div>
              <button type="submit" class="btn btn-primary">
                Save <i class="menu-icon fa fa-save"></i>
              </button>
              <a href="<?= BASEURL ?>/Employee/list" class="btn btn-primary">Employee List</a>
            </div>

          </div>
        </div>
      </div>
  </div>
  </form>
</div>
</div>

<!-- ========================= -->
<!-- AJAX JS for form submit -->
<!-- ========================= -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('employeeForm');
    const errorDiv = document.getElementById('formError');
    const successDiv = document.getElementById('formSuccess');

    form.addEventListener('submit', function(e) {
      e.preventDefault(); // Stop default submit

      // Clear previous messages
      errorDiv.style.display = 'none';
      errorDiv.innerHTML = '';
      successDiv.style.display = 'none';
      successDiv.innerHTML = '';

      const formData = new FormData(form);

      fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            errorDiv.style.display = 'block';
            errorDiv.innerHTML = data.error;
          } else if (data.success) {
            successDiv.style.display = 'block';
            successDiv.innerHTML = 'Employee saved successfully!';
            // Redirect after 2 seconds
            setTimeout(() => {
              window.location.href = "<?= BASEURL ?>/Employee/list";
            }, 2000);
          } else {
            errorDiv.style.display = 'block';
            errorDiv.innerHTML = 'Unexpected response. Please try again.';
          }
        })
        .catch(err => {
          console.error(err);
          errorDiv.style.display = 'block';
          errorDiv.innerHTML = 'An error occurred while saving employee.';
        });
    });
  });
</script>
