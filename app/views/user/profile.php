<div class="row">
  <div class="col-xs-12 col-sm-8 col-md-6">
    <?php Flasher::msgInfo(); ?>
    <div class="widget-box">
      <div class="widget-header">
        <h4 class="widget-title"><i class="ace-icon fa fa-user"></i> Account Details</h4>
      </div>
      <div class="widget-body">
        <div class="widget-main">
          <form action="<?= BASEURL ?>/User/updateProfile" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                $data["csrfToken"],
                ENT_QUOTES,
                "UTF-8",
            ) ?>">

            <div class="form-group">
              <label for="profileUsername">Username</label>
              <input type="text" id="profileUsername" class="form-control" value="<?= htmlspecialchars(
                  $data["userData"]["username"],
                  ENT_QUOTES,
                  "UTF-8",
              ) ?>" readonly>
              <p class="help-block">The login username cannot be changed here.</p>
            </div>

            <div class="form-group">
              <label for="profileName">Full Name</label>
              <input type="text" name="nama" id="profileName" class="form-control" maxlength="100"
                required value="<?= htmlspecialchars(
                  $data["userData"]["nama"],
                  ENT_QUOTES,
                  "UTF-8",
              ) ?>">
            </div>

            <div class="form-group">
              <label for="profileEmail">Email</label>
              <input type="email" name="email" id="profileEmail" class="form-control"
                maxlength="100" required value="<?= htmlspecialchars(
                  $data["userData"]["email"],
                  ENT_QUOTES,
                  "UTF-8",
              ) ?>">
            </div>

            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="profileDepartment">Department</label>
                  <input type="text" id="profileDepartment" class="form-control"
                    value="<?= htmlspecialchars(
                        (string) ($data["userData"]["employeeDepartment"] ?:
                            "Not assigned"),
                        ENT_QUOTES,
                        "UTF-8",
                    ) ?>" disabled>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="profilePosition">Position</label>
                  <input type="text" id="profilePosition" class="form-control"
                    value="<?= htmlspecialchars(
                        (string) ($data["userData"]["employeePosition"] ?:
                            "Not assigned"),
                        ENT_QUOTES,
                        "UTF-8",
                    ) ?>" disabled>
                </div>
              </div>
            </div>
            <p class="help-block">
              Department and position are maintained by Administrator/HR in the employee record.
            </p>

            <hr>
            <h5><strong>Change Password (optional)</strong></h5>
            <div class="form-group">
              <label for="currentPassword">Current Password</label>
              <input type="password" name="currentPassword" id="currentPassword"
                class="form-control profile-password" autocomplete="current-password"
                placeholder="Required only when changing password">
            </div>
            <div class="form-group">
              <label for="profilePassword">New Password</label>
              <input type="password" name="password" id="profilePassword"
                class="form-control profile-password" minlength="12" autocomplete="new-password"
                placeholder="Minimum 12 characters">
            </div>
            <div class="form-group">
              <label for="profilePasswordConfirmation">Confirm New Password</label>
              <input type="password" name="passwordConfirmation" id="profilePasswordConfirmation"
                class="form-control profile-password" minlength="12" autocomplete="new-password"
                placeholder="Retype the new password">
            </div>
            <div class="checkbox">
              <label><input type="checkbox" id="showProfilePasswords"> Show passwords</label>
            </div>

            <div class="form-group">
              <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update
                Profile</button>
              <button type="reset" class="btn btn-default"><i class="fa fa-undo"></i> Reset
                Form</button>
              <a href="<?= BASEURL ?>" class="btn btn-default">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    var toggle = document.getElementById('showProfilePasswords');
    if (!toggle) return;
    toggle.addEventListener('change', function() {
      var fields = document.querySelectorAll('.profile-password');
      for (var i = 0; i < fields.length; i++) fields[i].type = toggle.checked ? 'text' :
        'password';
    });
  })();
</script>
