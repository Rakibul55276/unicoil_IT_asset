<!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
</div><!-- /.row -->
</div><!-- /.page-content -->
</div>
</div><!-- /.main-content -->

<div class="footer">
  <div class="footer-inner">
    <div class="footer-content">
      <span class="bigger-120">
        <span class="blue bolder">| IT Asset</span>
        Application &copy; 2026
        <span style="margin-left:8px;font-size:10px;font-weight:500;letter-spacing:.02em;color:#4b5563;opacity:.85;">
          | Powered by IT (Rakib)
        </span>
      </span>

      &nbsp; &nbsp;
      <span class="action-buttons">
        <a href="#">
          <i class="ace-icon fa fa-twitter-square light-blue bigger-150"></i>
        </a>

        <a href="#">
          <i class="ace-icon fa fa-facebook-square text-primary bigger-150"></i>
        </a>

        <a href="#">
          <i class="ace-icon fa fa-rss-square orange bigger-150"></i>
        </a>
      </span>
    </div>
  </div>
</div>

<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
  <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
</a>
</div>
<?php
// Detailed asset, assignment, and transaction information is restricted to
// Administrator, Issuer, and IT Manager accounts.
$footerRoles = array_values(array_filter($_SESSION["usr"]["roles"] ?? []));
$footerUsername = strtolower(trim((string) ($_SESSION["usr"]["user"] ?? "")));
$footerCanViewAssetDetails =
    $footerUsername === "admin" ||
    (bool) array_intersect(
        $footerRoles,
        ["ADMINISTRATOR", "ISSUER", "IT_MANAGER"],
    );
if ($footerCanViewAssetDetails) {
    require_once __DIR__ . "/asset_details_modal.php";
}
?>
<?php if (!$footerCanViewAssetDetails): ?>
<script>
  // Ordinary users see asset numbers as text, without a misleading modal action.
  document.addEventListener('DOMContentLoaded', function() {
    function removeDetailActions(root) {
      var buttons = [];
      if (root.matches && root.matches('.asset-detail-link, [data-asset-details]')) {
        buttons.push(root);
      }
      if (root.querySelectorAll) {
        buttons = buttons.concat(
          Array.prototype.slice.call(
            root.querySelectorAll('.asset-detail-link, [data-asset-details]')
          )
        );
      }
      buttons.forEach(function(button) {
        var label = document.createElement('span');
        label.className = 'asset-number';
        label.innerHTML = button.innerHTML;
        button.parentNode.replaceChild(label, button);
      });
    }

    removeDetailActions(document);
    new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        Array.prototype.forEach.call(mutation.addedNodes, function(node) {
          if (node.nodeType === 1) removeDetailActions(node);
        });
      });
    }).observe(document.body, {
      childList: true,
      subtree: true
    });
  });
</script>
<?php endif; ?>
<script src="<?= BASEURL ?>/assets/js/jquery-2.1.4.min.js"></script>
<script type="text/javascript">
  if ('ontouchstart' in document.documentElement) document.write(
    "<script src='<?= BASEURL ?>/assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
</script>
<script src="<?= BASEURL ?>/assets/js/bootstrap.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/select2.min.js"></script>
<script>
  jQuery(function($) {
    $('.searchable-select').each(function() {
      var $select = $(this);
      if (!$select.data('select2')) {
        var options = {
          width: '100%',
          placeholder: $select.data('placeholder') || 'Type to search',
          allowClear: true
        };
        var remoteUrl = $select.data('remote-url');
        if (remoteUrl) {
          options.minimumInputLength = 2;
          options.ajax = {
            url: remoteUrl,
            dataType: 'json',
            delay: 300,
            cache: true,
            data: function(params) {
              return {
                q: params.term || '',
                custodian: $select.attr('data-remote-extra') || ''
              };
            },
            processResults: function(response) {
              return response;
            }
          };
        }
        $select.select2(options);
        $select.on('select2:select', function(event) {
          var item = event.params && event.params.data ? event.params.data : {};
          Array.prototype.forEach.call(this.options, function(option) {
            if (String(option.value) !== String(item.id)) return;
            option.dataset.description = item.description || '';
            option.dataset.category = item.category || '';
            option.dataset.status = item.status || '';
            option.dataset.condition = item.condition || '';
            option.dataset.image = item.image || '';
            option.dataset.asset = item.asset || item.id || '';
            option.dataset.type = item.type || '';
            option.dataset.holder = item.holder || '';
            option.dataset.custodian = item.custodian || '';
          });
        });
        $select.on('select2:select select2:unselect select2:clear', function() {
          this.dispatchEvent(new CustomEvent('searchable-select-change'));
        });
      }
    });
  });
</script>
<script src="<?= BASEURL ?>/assets/js/jquery.dataTables.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/jquery.dataTables.bootstrap.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/dataTables.buttons.min.js"></script>

<script src="<?= BASEURL ?>/assets/js/jquery-ui.custom.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/jquery.ui.touch-punch.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/jquery.sparkline.index.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/jquery.flot.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/jquery.flot.pie.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/jquery.flot.resize.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/ace-elements.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/ace.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/sidebar-collapsible.js?v=<?= rawurlencode(
    (string) filemtime(
        dirname(__DIR__, 3) . "/assets/js/sidebar-collapsible.js",
    ),
) ?>"></script>

<script src="<?= BASEURL ?>/assets/js/jquery.jqGrid.min.js"></script>
<script src="<?= BASEURL ?>/assets/js/grid.locale-en.js"></script>

<script>
window.__oldFormInput = <?= Flasher::pullOldInputJson() ?>;
</script>
<script src="<?= BASEURL ?>/js/script.js?v=<?= rawurlencode(
    (string) filemtime(dirname(__DIR__, 3) . "/js/script.js"),
) ?>"></script>



<script type="text/javascript">
  jQuery(function($) {
    if (!$('#dynamic-table').length) return;
    var myTable = $('#dynamic-table').DataTable({
      bAutoWidth: false,
      columnDefs: [{
        orderable: false,
        targets: [0, -1]
      }],
      "aaSorting": [],

      select: {
        style: 'multi'
      }
    });

    // Some lightweight pages load DataTables without the optional Buttons
    // extension. The table remains usable, so skip only the export toolbar.
    if (
      !$.fn.dataTable.Buttons ||
      !$.fn.dataTable.Buttons.defaults ||
      !$.fn.dataTable.Buttons.defaults.dom
    ) return;

    $.fn.dataTable.Buttons.defaults.dom.container.className =
      'dt-buttons btn-overlap btn-group btn-overlap';

    var tableButtons = [{
        "extend": "colvis",
        "text": "<i class='fa fa-search bigger-110 blue'></i> <span class='hidden'>Show/hide columns</span>",
        "className": "btn btn-white btn-primary btn-bold",
        columns: ':not(:first):not(:last)'
      },
      {
        "extend": "copy",
        "text": "<i class='fa fa-copy bigger-110 pink'></i> <span class='hidden'>Copy to clipboard</span>",
        "className": "btn btn-white btn-primary btn-bold"
      },
      {
        "extend": "csv",
        "text": "<i class='fa fa-database bigger-110 orange'></i> <span class='hidden'>Export to CSV</span>",
        "className": "btn btn-white btn-primary btn-bold"
      },
      {
        "extend": "excel",
        "text": "<i class='fa fa-file-excel-o bigger-110 green'></i> <span class='hidden'>Export to Excel</span>",
        "className": "btn btn-white btn-primary btn-bold"
      },
    ];
    if (!$('#dynamic-table').is('[data-no-pdf="1"]')) tableButtons.splice(4, 0, {
      "extend": "pdf",
      "text": "<i class='fa fa-file-pdf-o bigger-110 red'></i> <span class='hidden'>Export to PDF</span>",
      "className": "btn btn-white btn-primary btn-bold"
    });
    tableButtons.push({
      "extend": "print",
      "text": "<i class='fa fa-print bigger-110 grey'></i> <span class='hidden'>Print</span>",
      "className": "btn btn-white btn-primary btn-bold",
      autoPrint: false,
      message: 'This print was produced using the Print button for DataTables'
    });
    new $.fn.dataTable.Buttons(myTable, {
      buttons: tableButtons
    });

    myTable.buttons().container().appendTo($('.tableTools-container'));

    var defaultCopyAction = myTable.button(1).action();
    myTable.button(1).action(function(e, dt, button, config) {
      defaultCopyAction(e, dt, button, config);
      $('.dt-button-info').addClass(
        'gritter-item-wrapper gritter-info gritter-center white');
    });


    var defaultColvisAction = myTable.button(0).action();
    myTable.button(0).action(function(e, dt, button, config) {

      defaultColvisAction(e, dt, button, config);


      if ($('.dt-button-collection > .dropdown-menu').length == 0) {
        $('.dt-button-collection')
          .wrapInner(
            '<ul class="dropdown-menu dropdown-light dropdown-caret dropdown-caret" />')
          .find('a').attr('href', '#').wrap("<li />")
      }
      $('.dt-button-collection').appendTo('.tableTools-container .dt-buttons')
    });

    setTimeout(function() {
      $($('.tableTools-container')).find('a.dt-button').each(function() {
        var div = $(this).find(' > div').first();
        if (div.length == 1) div.tooltip({
          container: 'body',
          title: div.parent().text()
        });
        else $(this).tooltip({
          container: 'body',
          title: $(this).text()
        });
      });
    }, 500);

    myTable.on('select', function(e, dt, type, index) {
      if (type === 'row') {
        $(myTable.row(index).node()).find('input:checkbox').prop('checked', true);
      }
    });
    myTable.on('deselect', function(e, dt, type, index) {
      if (type === 'row') {
        $(myTable.row(index).node()).find('input:checkbox').prop('checked', false);
      }
    });

    $('th input[type=checkbox], td input[type=checkbox]').prop('checked', false);

    $('#dynamic-table > thead > tr > th input[type=checkbox], #dynamic-table_wrapper input[type=checkbox]')
      .eq(0).on('click', function() {
        var th_checked = this.checked;

        $('#dynamic-table').find('tbody > tr').each(function() {
          var row = this;
          if (th_checked) myTable.row(row).select();
          else myTable.row(row).deselect();
        });
      });

    $('#dynamic-table').on('click', 'td input[type=checkbox]', function() {
      var row = $(this).closest('tr').get(0);
      if (this.checked) myTable.row(row).deselect();
      else myTable.row(row).select();
    });


    $(document).on('click', '#dynamic-table .dropdown-toggle', function(e) {
      e.stopImmediatePropagation();
      e.stopPropagation();
      e.preventDefault();
    });
  })
</script>
</body>

</html>
