<div class="row">
  <div class="col-xs-12 col-sm-12">
    <?php Flasher::msgInfo(); ?>
    <div class="widget">
      <!-- <div class="widget-header">
				<h4 class="widget-title"></h4>
			</div> -->
      <form action="<?= $data["post_url"] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
            (string) ($data["csrfToken"] ?? ""),
            ENT_QUOTES,
            "UTF-8",
        ) ?>">
        <div class="col-md-6">
          <div class="form-group">
            <label for="empId">Linked Employee</label>
            <select name="empId" id="empId" class="form-control searchable-select"
              data-placeholder="Search employee ID or name"
              data-current-username="<?= htmlspecialchars(
                  (string) ($data["userData"]["username"] ?? ""),
                  ENT_QUOTES,
                  "UTF-8",
              ) ?>"
              onchange="if(window.syncUserEmployeeEmail){window.syncUserEmployeeEmail();}"
              <?= $data["userData"] ? "" : "required" ?>>
              <option value="">Not linked</option>
              <?php foreach ($data["employees"] ?? [] as $employee): ?>
              <option value="<?= (int) $employee["empId"] ?>" data-email="<?= htmlspecialchars(
    (string) ($employee["email"] ?? ""),
    ENT_QUOTES,
    "UTF-8",
) ?>" data-fullname="<?= htmlspecialchars(
    (string) ($employee["fullname"] ?? ""),
    ENT_QUOTES,
    "UTF-8",
) ?>" data-linked-user="<?= htmlspecialchars(
    (string) ($employee["linkedUsername"] ?? ""),
    ENT_QUOTES,
    "UTF-8",
) ?>" <?= (string) ($data["userData"]["empId"] ?? "") === (string) $employee["empId"] ? "selected" : "" ?>>
                <?= htmlspecialchars($employee["empId"] . " - " . $employee["fullname"] . (!empty($employee["deptName"]) ? " (" . $employee["deptName"] . ")" : "")) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <p class="help-block" id="employeeHelp">Required for self-service requests and receiver acceptance. One
              employee can be linked to only one login.</p>
          </div>
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" class="form-control"
              placeholder="Input Username" required value="<?= htmlspecialchars(
          $data["userData"]["username"] ?? "",
      ) ?>" <?= $data["userData"] ? "readonly" : "" ?>>
          </div>
          <div class="form-group">
            <label for="nama">Name</label>
            <input type="text" name="nama" id="nama" class="form-control"
              placeholder="Input Full Name" required value="<?= htmlspecialchars(
          $data["userData"]["nama"] ?? "",
      ) ?>">
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control"
              placeholder="Input Email" required value="<?= htmlspecialchars(
                  $data["userData"]["email"] ?? "",
                  ENT_QUOTES,
                  "UTF-8",
              ) ?>">
            <p class="help-block" id="emailHelp">
              Select an employee to load their profile email automatically.
            </p>
          </div>
          <div class="form-group">
            <label>Access Role</label>
            <?php
            $allRoleLabels = [
                "ADMINISTRATOR" => "Administrator (full system access)",
                "HR_REQUESTER" => "HR Requester (own and other employees)",
                "DEPARTMENT_MANAGER" => "Department Manager (own and department)",
                "IT_MANAGER" => "IT Manager",
                "ISSUER" => "Issuer",
            ];
            $selectedRoles = $data["userData"]["roles"] ?? [];
            $workflowUiEnabled = defined("WORKFLOW_UI_ENABLED")
                ? WORKFLOW_UI_ENABLED
                : true;
            $issuerUiEnabled = defined("ISSUER_UI_ENABLED")
                ? ISSUER_UI_ENABLED
                : false;
            $roleLabels = ["ADMINISTRATOR" => $allRoleLabels["ADMINISTRATOR"]];
            if ($workflowUiEnabled) {
                $roleLabels = $allRoleLabels;
            } elseif ($issuerUiEnabled) {
                $roleLabels["ISSUER"] = $allRoleLabels["ISSUER"];
            }
            $preservedWorkflowRoles = array_values(
                array_diff($selectedRoles, array_keys($roleLabels)),
            );
            ?>
            <?php if ($workflowUiEnabled): ?>
            <p class="help-block">Every active account linked to an employee can request and accept equipment. Assign only the additional workflow roles below.</p>
            <?php elseif ($issuerUiEnabled): ?>
            <p class="help-block">Administrator and the existing Issuer role can be assigned. Other request and approval roles are hidden temporarily; existing assignments are preserved.</p>
            <?php else: ?>
            <p class="help-block">Only Administrator access can currently be assigned. Request and approval roles are hidden temporarily; existing assignments are preserved.</p>
            <?php endif; ?>
            <?php if (!$workflowUiEnabled): ?>
            <?php foreach ($preservedWorkflowRoles as $preservedRole): ?>
            <input type="hidden" name="roles[]" value="<?= htmlspecialchars(
                $preservedRole,
                ENT_QUOTES,
                "UTF-8",
            ) ?>">
            <?php endforeach; ?>
            <?php endif; ?>
            <?php foreach ($roleLabels as $roleCode => $roleLabel): ?>
            <div class="checkbox"><label>
              <input type="checkbox" name="roles[]" value="<?= $roleCode ?>" data-workflow-role="<?= $roleCode ?>" <?= in_array($roleCode, $selectedRoles, true) ? "checked" : "" ?>>
              <?= htmlspecialchars($roleLabel) ?>
            </label></div>
            <?php endforeach; ?>
          </div>
          <div class="form-group">
            <label for="password"><?= $data["userData"] ? "New Password" : "Password" ?></label>
            <input type="password" name="password" id="password" class="form-control" minlength="12"
              autocomplete="new-password" placeholder="<?= $data[
          "userData"
      ]
          ? "Leave blank to keep current password"
          : "Minimum 12 characters" ?>" <?= $data["userData"] ? "" : "required" ?>>
          </div>
          <?php if (
         $data["userData"]
     ): ?><div class="form-group"><label for="passwordConfirmation">Confirm New
              Password</label><input type="password" name="passwordConfirmation"
              id="passwordConfirmation" class="form-control" minlength="12"
              autocomplete="new-password" placeholder="Retype the new password">
            <p class="help-block">Complete both password fields only when changing the password.</p>
          </div><?php endif; ?>
          <div class="form-group"><label><input type="checkbox" id="showUserPasswords"> Show password<?= $data[
         "userData"
     ]
         ? "s"
         : "" ?></label></div>
          <?php if (
         $data["userData"]
     ): ?><div class="form-group"><label><input type="checkbox" name="active" value="1" <?= $data[
    "userData"
]["active"]
    ? "checked"
    : "" ?>> Active</label></div><?php endif; ?>

          <div class="form-group">
            <button type="submit"
              class="btn btn-primary"><?= $data["userData"] ? "Update" : "Save" ?></button>
            <a href="<?= BASEURL ?>/User" class="btn btn-primary">Back</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  (function() {
    var toggle = document.getElementById('showUserPasswords');
    if (toggle) {
      toggle.addEventListener('change', function() {
        ['password', 'passwordConfirmation'].forEach(function(id) {
          var field = document.getElementById(id);
          if (field) field.type = toggle.checked ? 'text' : 'password';
        });
      });
    }

    var employee = document.getElementById('empId');
    var email = document.getElementById('email');
    var emailHelp = document.getElementById('emailHelp');
    var missingEmailAlert = document.getElementById('missingEmailAlert');
    var employeeHelp = document.getElementById('employeeHelp');
    var submitButton = document.querySelector('form button[type="submit"]');
    var originalEmail = email ? email.value : '';
    var originalEmployee = employee ? employee.value : '';
    var currentUsername = <?= json_encode(
        (string) ($data["userData"]["username"] ?? ""),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
    ) ?>;

    function syncEmployeeEmail() {
      if (!employee || !email) return;
      var option = employee.options[employee.selectedIndex];
      var linked = employee.value !== '';
      var employeeEmail = linked && option ? (option.getAttribute('data-email') || '').trim() : '';
      var linkedUsername = linked && option
        ? (option.getAttribute('data-linked-user') || '').trim()
        : '';
      var belongsToAnotherUser = linkedUsername !== '' && linkedUsername !== currentUsername;

      if (employeeHelp) {
        employeeHelp.className = belongsToAnotherUser ? 'help-block text-danger' : 'help-block';
        employeeHelp.textContent = belongsToAnotherUser
          ? 'This employee is already linked to user ID ' + linkedUsername +
            '. Each employee can have only one user ID.'
          : 'Required for self-service requests and receiver acceptance. One employee can be linked to only one login.';
      }
      if (submitButton) submitButton.disabled = belongsToAnotherUser;

      if (employeeEmail) {
        email.value = employeeEmail;
        email.readOnly = true;
        email.required = true;
        if (missingEmailAlert) missingEmailAlert.classList.add('hide');
        email.classList.remove('input-validation-error');
        if (emailHelp) {
          emailHelp.className = 'help-block text-success';
          emailHelp.textContent = 'Email loaded from the linked employee profile.';
        }
      } else {
        email.readOnly = false;
        email.required = true;
        if (missingEmailAlert) {
          missingEmailAlert.classList.toggle('hide', !linked);
        }
        if (!linked || employee.value === originalEmployee) {
          email.value = originalEmail;
        } else {
          email.value = '';
        }
        if (emailHelp) {
          emailHelp.className = 'help-block';
          emailHelp.textContent = '';
        }
      }
    }
    window.syncUserEmployeeEmail = syncEmployeeEmail;
    if (employee) {
      employee.addEventListener('change', syncEmployeeEmail);
      employee.addEventListener('searchable-select-change', syncEmployeeEmail);
      syncEmployeeEmail();
    }

    // Select2 is initialized later in the shared footer. Bind after the page is
    // ready as well, because some older browser/plugin combinations do not
    // forward their selection through the native change listener consistently.
    window.addEventListener('load', function() {
      if (window.jQuery && employee) {
        window.jQuery(employee)
          .off('.userEmployeeEmail')
          .on(
            'change.userEmployeeEmail select2:select.userEmployeeEmail select2:clear.userEmployeeEmail',
            syncEmployeeEmail
          );
      }
      syncEmployeeEmail();
    });

    var administrator = document.querySelector('[data-workflow-role="ADMINISTRATOR"]');
    var workflowRoles = document.querySelectorAll('[data-workflow-role]');
    function syncAdministratorRole() {
      if (!administrator) return;
      Array.prototype.forEach.call(workflowRoles, function(role) {
        if (role === administrator) return;
        if (administrator.checked) role.checked = false;
        role.disabled = administrator.checked;
      });
    }
    if (administrator) {
      administrator.addEventListener('change', syncAdministratorRole);
      syncAdministratorRole();
    }
  })();
</script>
