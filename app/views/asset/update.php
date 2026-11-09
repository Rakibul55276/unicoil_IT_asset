<div class="page-content">
  <div class="container-fluid">

    <div class="col-xs-12 col-sm-12">
      <div class="widget-box">
        <div class="widget-header">
          <h4 class="widget-title">Search & Update Asset</h4>
        </div>

        <form id="assetForm" action="<?= BASEURL ?>/Asset/update" method="post"
          enctype="multipart/form-data">

          <div class="row">

            <!-- LEFT COLUMN -->
            <div class="col-md-6">
              <div class="widget-body">
                <div class="widget-main">

                  <!-- ASSET ID -->
                  <div>
                    <label>Asset ID</label>
                    <div class="form-group">
                      <input type="text" name="assetId" id="assetId" class="form-control"
                        value="<?= $data["assetNum"] ?? "" ?>" readonly
                        data-url="<?= BASEURL ?>/Asset/getAssetById">
                    </div>
                  </div>

                  <!-- Asset Category -->
                  <div>
                    <label>Asset Category</label>
                    <div class="form-group">
                      <select name="assetKat" id="assetKat" class="form-control" readonly>
                        <option value=""></option>
                        <?php foreach ($data["asetkat"] as $asetkat): ?>
                        <option value="<?= $asetkat[
                                                        "assetKat"
                                                    ] ?>"><?= $asetkat["Description"] ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- Employee ID -->
                  <div>
                    <label>Employee ID</label>
                    <div class="form-group">
                      <input type="text" name="empId" id="empId" class="form-control" required>
                    </div>
                  </div>

                  <!-- Employee Name -->
                  <div>
                    <label>Employee Name</label>
                    <div class="form-group">
                      <input type="text" name="empName" id="empName" class="form-control" readonly>
                    </div>
                  </div>

                  <!-- Asset Description -->
                  <div>
                    <label>Asset Description</label>
                    <div class="form-group">
                      <input type="text" name="desc" id="desc" class="form-control" required>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-md-6">
              <div class="widget-body">
                <div class="widget-main">

                  <!-- Asset Location -->
                  <div>
                    <label>Asset Location</label>
                    <div class="form-group">
                      <select name="location" id="location" class="form-control" required>
                        <?php foreach ($data["location"] as $location): ?>
                        <option value="<?= $location[
                                                        "location"
                                                    ] ?>"><?= $location["description"] ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- Quantity -->
                  <div>
                    <label>Quantity</label>
                    <div class="form-group">
                      <input type="number" name="qty" id="qty" class="form-control" required
                        value="1">
                    </div>
                  </div>

                  <!-- Unit -->
                  <div>
                    <label>Unit</label>
                    <div class="form-group">
                      <select name="unit" id="unit" class="form-control" required>
                        <?php foreach ($data["runit"] as $runit): ?>
                        <option value="<?= $runit[
                                                        "unit"
                                                    ] ?>"><?= $runit["unit"] ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- Asset Status -->
                  <div>
                    <label>Asset Status</label>
                    <div class="form-group">
                      <select name="assetStatus" id="assetStatus" class="form-control" required>
                        <?php foreach ($data["stts"] as $stts): ?>
                        <option value="<?= $stts[
                                                        "assetStatus"
                                                    ] ?>"><?= $stts["Description"] ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- Asset Image -->
                  <div>
                    <label>Asset Image</label>
                    <div class="form-group">
                      <div
                        style="display:flex;align-items:center;justify-content:center;width:220px;height:135px;margin:8px 0;border:1px solid #d5dbe1;border-radius:6px;background:#f7f8fa;overflow:hidden">
                        <img id="legacy-asset-image-preview" src="<?= !empty(
                                                $data["asset"]["assetImage"]
                                            )
                                                ? BASEURL .
                                                    "/Asset/media/" .
                                                    rawurlencode($data["asset"]["assetImage"])
                                                : BASEURL .
                                                    "/public/img/no-image.png" ?>"
                          alt="Asset image preview"
                          style="width:100%;height:100%;object-fit:contain"
                          onerror="this.onerror=null;this.src='<?= BASEURL ?>/public/img/no-image.png';">
                      </div>
                      <input type="file" name="assetImage" id="assetImage" class="form-control"
                        accept="image/*">

                      <?php if (!empty($data["asset"]["assetImage"])): ?>
                      <small>
                        Current Image:
                        <a href="<?= BASEURL ?>/Asset/media/<?= rawurlencode($data[
    "asset"
]["assetImage"]) ?>" target="_blank" rel="noopener">
                          <?= $data["asset"]["assetImage"] ?>
                        </a>
                      </small>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div>
                    <button type="submit" id="submitBtn" class="btn btn-primary">Update
                      Asset</button>
                  </div>

                </div>
              </div>
            </div>

          </div>
        </form>
        <script>
          (function() {
            var input = document.getElementById('assetImage'),
              preview = document.getElementById('legacy-asset-image-preview'),
              url = '';
            if (!input || !preview) return;
            input.addEventListener('change', function() {
              if (url) URL.revokeObjectURL(url);
              if (this.files && this.files[0]) {
                url = URL.createObjectURL(this.files[0]);
                preview.src = url;
              } else preview.src = '<?= BASEURL ?>/public/img/no-image.png';
            });
          })();
        </script>
      </div>
    </div>
  </div>
</div>
?>
<!-- ==========================
     JAVASCRIPT SECTION
============================= -->
<script>
  document.addEventListener('DOMContentLoaded', function() {

    const assetIdInput = document.getElementById('assetId');
    const empIdInput = document.getElementById('empId');
    const empNameInput = document.getElementById('empName');
    const submitBtn = document.getElementById('submitBtn');
    let empFetchTimeout;

    function setInvalidEmployeeState(message) {
      empNameInput.value = '';
      empIdInput.classList.add('is-invalid');
      submitBtn.disabled = false;
      alert(message);
    }

    function setValidEmployeeState(name) {
      empNameInput.value = name;
      empIdInput.classList.remove('is-invalid');
      submitBtn.disabled = false;
    }

    // Fetch employee name by ID
    function fetchEmployeeName(empId) {
      if (!empId) {
        empNameInput.value = '';
        empIdInput.classList.remove('is-invalid');
        submitBtn.disabled = false;
        return;
      }

      fetch("<?= BASEURL ?>/Employee/getById/" + empId)
        .then(res => {
          if (!res.ok) return res.json().then(data => {
            throw data;
          });
          return res.json();
        })
        .then(data => {
          setValidEmployeeState(data.empName);
        })
        .catch(err => {
          setInvalidEmployeeState(err.message || 'Employee not available or inactive');
        });
    }

    // Load asset data on page load
    if (assetIdInput && assetIdInput.value) {
      const assetUrl = assetIdInput.dataset.url + '/' + assetIdInput.value;

      fetch(assetUrl)
        .then(res => res.json())
        .then(data => {
          if (!data || !data.assetNum) {
            alert('Asset not found');
            return;
          }

          document.getElementById('desc').value = data.Description ?? '';
          empIdInput.value = data.empId ?? '';
          document.getElementById('qty').value = data.quantity ?? 1;
          document.getElementById('unit').value = data.unit ?? '';
          document.getElementById('assetStatus').value = data.assetStat ?? '';
          document.getElementById('location').value = data.location ?? '';
          document.getElementById('assetKat').value = data.assetKat ?? '';

          // Disable category but still submit value
          document.getElementById('assetKat').disabled = true;
          const hiddenKat = document.createElement('input');
          hiddenKat.type = 'hidden';
          hiddenKat.name = 'assetKat';
          hiddenKat.value = data.assetKat;
          document.querySelector('form').appendChild(hiddenKat);

          // Fetch employee name for the loaded empId
          if (empIdInput.value && empIdInput.value.length >= 4) {
            fetchEmployeeName(empIdInput.value);
          }
        })
        .catch(err => {
          console.error(err);
          alert('Error loading asset');
        });
    }

    // Live employee validation as user types
    empIdInput.addEventListener('input', function() {
      const empId = empIdInput.value.trim();

      if (empFetchTimeout) clearTimeout(empFetchTimeout);

      if (empId.length >= 4) {
        empFetchTimeout = setTimeout(() => fetchEmployeeName(empId), 300);
      } else {
        empNameInput.value = '';
        empIdInput.classList.remove('is-invalid');
        submitBtn.disabled = false;
      }
    });

  });
</script>
