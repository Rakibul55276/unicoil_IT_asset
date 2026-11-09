<?php
// Some legacy pages include this partial directly. Keep the authorization guard
// here as well as in the footer so the modal cannot leak through those includes.
$assetModalRoles = array_values(array_filter($_SESSION["usr"]["roles"] ?? []));
$assetModalUsername = strtolower(trim((string) ($_SESSION["usr"]["user"] ?? "")));
$assetModalAllowed =
    $assetModalUsername === "admin" ||
    (bool) array_intersect(
        $assetModalRoles,
        ["ADMINISTRATOR", "ISSUER", "IT_MANAGER"],
    );
$assetModalCanOperate =
    $assetModalUsername === "admin" ||
    in_array("ADMINISTRATOR", $assetModalRoles, true);
if (!$assetModalAllowed) {
    return;
}

if (empty($_SESSION["csrf_media_delete"])) {
    $_SESSION["csrf_media_delete"] = bin2hex(random_bytes(32));
} ?>
<style>
  /* Main asset details modal */
  #sharedAssetDetailModal .modal-dialog {
    width: 92%;
    max-width: 1050px;
    margin: 30px auto;
  }

  #sharedAssetDetailModal .modal-content {
    border: 0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.25);
  }

  #sharedAssetDetailModal .modal-header {
    position: relative;
    padding: 18px 55px 18px 22px;
    border-bottom: 0;
    background: linear-gradient(135deg, #337ab7, #1d5b8f);
    color: #fff;
  }

  #sharedAssetDetailModal .modal-title {
    margin: 0;
    font-size: 21px;
    font-weight: 600;
  }

  #sharedAssetDetailModal .modal-header .close {
    position: absolute;
    top: 12px;
    right: 16px;
    width: 36px;
    height: 36px;
    margin: 0;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    font-size: 27px;
    line-height: 34px;
    text-align: center;
    opacity: 1;
    text-shadow: none;
    transition: all 0.2s ease;
  }

  #sharedAssetDetailModal .modal-header .close:hover {
    background: rgba(255, 255, 255, 0.30);
    transform: rotate(90deg);
  }

  #sharedAssetDetailModal .modal-body {
    padding: 22px;
    background: #f5f7fa;
    min-height: 240px;
  }

  #sharedAssetDetailModal .modal-footer {
    padding: 13px 20px;
    border-top: 1px solid #e8edf2;
    background: #fff;
  }

  /* Loading section */
  .asset-loading-box {
    padding: 70px 20px;
    text-align: center;
    color: #667085;
  }

  .asset-loading-box .fa {
    display: block;
    margin-bottom: 12px;
    color: #337ab7;
    font-size: 38px;
  }

  /* Content panels */
  .asset-detail-panel {
    height: 100%;
    padding: 18px;
    margin-bottom: 18px;
    border: 1px solid #e2e7ec;
    border-radius: 9px;
    background: #fff;
    box-shadow: 0 3px 12px rgba(31, 45, 61, 0.06);
  }

  #sharedAssetDetailModal .asset-current-condition-row th,
  #sharedAssetDetailModal .asset-current-condition-row td {
    background: #f8fbfa;
    vertical-align: middle;
  }

  #sharedAssetDetailModal .asset-current-condition {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 15px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .2px;
    text-transform: capitalize;
  }

  #sharedAssetDetailModal .asset-condition-source {
    display: block;
    margin-top: 6px;
    color: #667085;
    font-size: 11px;
  }

  .asset-section-title {
    position: relative;
    margin: 0 0 17px;
    padding: 0 0 10px 14px;
    border-bottom: 1px solid #edf0f3;
    color: #344054;
    font-size: 17px;
    font-weight: 600;
  }

  .asset-section-title:before {
    position: absolute;
    top: 1px;
    bottom: 10px;
    left: 0;
    width: 4px;
    border-radius: 4px;
    background: #337ab7;
    content: "";
  }

  /* Image groups */
  .asset-gallery-group {
    margin-bottom: 24px;
  }

  .asset-gallery-group:last-child {
    margin-bottom: 0;
  }

  .asset-gallery-heading {
    display: flex;
    align-items: center;
    margin: 0 0 11px;
    color: #475467;
    font-size: 14px;
    font-weight: 600;
  }

  .asset-gallery-heading i {
    margin-right: 7px;
    color: #337ab7;
  }

  .asset-gallery-count {
    display: inline-block;
    min-width: 22px;
    margin-left: 7px;
    padding: 2px 7px;
    border-radius: 12px;
    background: #eaf3fb;
    color: #337ab7;
    font-size: 11px;
    text-align: center;
  }

  .asset-image-column {
    margin-bottom: 15px;
  }

  .asset-image-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #dde4ea;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 3px 10px rgba(33, 43, 54, 0.08);
    transition: all 0.2s ease;
  }

  .asset-image-card:hover {
    border-color: #9dc4e5;
    box-shadow: 0 7px 18px rgba(33, 43, 54, 0.15);
    transform: translateY(-2px);
  }

  .asset-image-preview {
    position: relative;
    height: 165px;
    overflow: hidden;
    background: #eef2f6;
    cursor: zoom-in;
  }

  .asset-image-preview img {
    width: 100%;
    height: 100%;
    border: 0;
    object-fit: cover;
    transition: transform 0.3s ease;
  }

  .asset-image-card:hover .asset-image-preview img {
    transform: scale(1.04);
  }

  .asset-image-overlay {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    padding: 25px 10px 9px;
    background: linear-gradient(to top,
        rgba(0, 0, 0, 0.60),
        rgba(0, 0, 0, 0));
    color: #fff;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  .asset-image-card:hover .asset-image-overlay {
    opacity: 1;
  }

  /* Cross button at top-right of image */
  .asset-image-close {
    position: absolute;
    top: 7px;
    right: 7px;
    z-index: 20;
    width: 29px;
    height: 29px;
    padding: 0;
    border: 2px solid rgba(255, 255, 255, 0.90);
    border-radius: 50%;
    background: rgba(211, 47, 47, 0.92);
    color: #fff;
    font-size: 20px;
    font-weight: 400;
    line-height: 24px;
    text-align: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.28);
    transition: all 0.2s ease;
  }

  .asset-image-close:hover,
  .asset-image-close:focus {
    outline: none;
    background: #b71c1c;
    color: #fff;
    transform: scale(1.10);
  }

  .asset-image-info {
    min-height: 49px;
    padding: 8px 10px;
    color: #667085;
    font-size: 11px;
    line-height: 16px;
  }

  .asset-image-name {
    display: block;
    overflow: hidden;
    margin-bottom: 2px;
    color: #344054;
    font-size: 12px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .asset-no-image {
    padding: 35px 15px;
    border: 2px dashed #d7dee5;
    border-radius: 8px;
    background: #fafbfc;
    color: #98a2b3;
    text-align: center;
  }

  .asset-no-image i {
    display: block;
    margin-bottom: 9px;
    font-size: 35px;
  }

  .asset-no-image img {
    display: block;
    width: 220px;
    max-width: 100%;
    height: 130px;
    margin: 0 auto 10px;
    border: 1px solid #e1e6eb;
    border-radius: 6px;
    background: #fff;
    object-fit: contain;
  }

  /* Details table */
  .asset-info-table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
  }

  .asset-info-table tr:first-child th {
    border-top-left-radius: 6px;
  }

  .asset-info-table tr:first-child td {
    border-top-right-radius: 6px;
  }

  .asset-info-table tr:last-child th {
    border-bottom-left-radius: 6px;
  }

  .asset-info-table tr:last-child td {
    border-bottom-right-radius: 6px;
  }

  .asset-info-table th,
  .asset-info-table td {
    padding: 11px 12px !important;
    vertical-align: middle !important;
    border-color: #e4e9ee !important;
  }

  .asset-info-table th {
    width: 39%;
    background: #f7f9fb;
    color: #475467;
    font-size: 13px;
    font-weight: 600;
  }

  .asset-info-table td {
    background: #fff;
    color: #252f3f;
    font-size: 13px;
    word-break: break-word;
  }

  .asset-number-value {
    color: #337ab7;
    font-weight: 700;
  }

  .asset-status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 14px;
    background: #e8f5e9;
    color: #2e7d32;
    font-size: 12px;
    font-weight: 600;
  }

  .asset-detail-subheading {
    padding: 8px 12px;
    background: #eaf3fb !important;
    color: #245b87 !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .asset-history-table {
    margin-bottom: 0;
    background: #fff;
  }

  .asset-history-table th {
    background: #f7f9fb;
    color: #475467;
    white-space: nowrap;
  }

  .asset-history-table th,
  .asset-history-table td {
    padding: 9px 10px !important;
    vertical-align: middle !important;
  }

  #sharedAssetDetailModal .audit-date-stack {
    min-width: 116px;
    white-space: nowrap;
  }

  #sharedAssetDetailModal .audit-date-stack strong,
  #sharedAssetDetailModal .audit-date-stack span {
    display: block;
  }

  #sharedAssetDetailModal .audit-date-stack span {
    margin-top: 3px;
    color: #667085;
    font-size: 11px;
  }

  #sharedAssetDetailModal .audit-event-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 12px;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    text-transform: uppercase;
  }

  #sharedAssetDetailModal .audit-event-receipt { background: #3178a9 }
  #sharedAssetDetailModal .audit-event-update { background: #6f5aa7 }
  #sharedAssetDetailModal .audit-event-assign { background: #27845f }
  #sharedAssetDetailModal .audit-event-return { background: #d4811f }
  #sharedAssetDetailModal .audit-event-approved { background: #438b45 }
  #sharedAssetDetailModal .audit-event-danger { background: #c94b50 }
  #sharedAssetDetailModal .audit-event-default { background: #667085 }

  .asset-history-empty {
    margin: 0;
    padding: 18px;
    border: 1px dashed #d7dee5;
    border-radius: 6px;
    background: #fafbfc;
    color: #667085;
    text-align: center;
  }

  /* Image lightbox */
  #assetImageLightbox {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 99999;
    display: none;
    padding: 35px;
    background: rgba(12, 18, 28, 0.92);
    text-align: center;
  }

  #assetImageLightbox.active {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #assetImageLightbox img {
    max-width: 92%;
    max-height: 88vh;
    border: 4px solid #fff;
    border-radius: 7px;
    background: #fff;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.5);
    object-fit: contain;
  }

  .asset-lightbox-close {
    position: absolute;
    top: 18px;
    right: 24px;
    width: 43px;
    height: 43px;
    padding: 0;
    border: 2px solid rgba(255, 255, 255, 0.8);
    border-radius: 50%;
    background: rgba(211, 47, 47, 0.95);
    color: #fff;
    font-size: 30px;
    line-height: 36px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .asset-lightbox-close:hover {
    background: #b71c1c;
    transform: rotate(90deg);
  }

  @media (max-width: 767px) {
    #sharedAssetDetailModal .modal-dialog {
      width: auto;
      margin: 10px;
    }

    #sharedAssetDetailModal .modal-body {
      padding: 12px;
    }

    .asset-detail-panel {
      padding: 13px;
    }

    .asset-image-preview {
      height: 190px;
    }

    .asset-info-table th {
      width: 42%;
    }

    #assetImageLightbox {
      padding: 15px;
    }

    #assetImageLightbox img {
      max-width: 100%;
      max-height: 85vh;
    }
  }
</style>

<!-- Asset Details Modal -->
<div class="modal fade" id="sharedAssetDetailModal" tabindex="-1" role="dialog"
  aria-labelledby="sharedAssetDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h4 class="modal-title" id="sharedAssetDetailModalLabel">
          <i class="fa fa-desktop"></i>
          Asset Details
        </h4>

        <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body" id="shared-asset-detail">
        <div class="asset-loading-box">
          <i class="fa fa-spinner fa-spin"></i>
          <div>Loading asset information...</div>
        </div>
      </div>

      <div class="modal-footer">
        <a id="assetStickerLink" class="btn btn-primary" target="_blank" rel="noopener" href="#">
          <i class="fa fa-qrcode"></i> Print Asset Sticker
        </a>
        <button type="button" class="btn btn-default" data-dismiss="modal">
          <i class="fa fa-times"></i>
          Close
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Full-size Image Preview -->
<div id="assetImageLightbox" aria-hidden="true">
  <button type="button" class="asset-lightbox-close" title="Close image" aria-label="Close image">
    &times;
  </button>

  <img id="assetLightboxImage" src="" alt="Asset image preview">
</div>

<script>
  (function() {
    'use strict';

    var baseUrl = '<?= BASEURL ?>';
    var canOperateAssets = <?= $assetModalCanOperate ? "true" : "false" ?>;

    function escapeHtml(value) {
      var div = document.createElement('div');

      div.textContent = value === null ||
        value === undefined ||
        value === '' ?
        '-' :
        String(value);

      return div.innerHTML;
    }

    function safeValue(value) {
      return value === null ||
        value === undefined ||
        value === '' ?
        '-' :
        value;
    }

    // Format stored SQL timestamps without applying a browser timezone shift.
    function formatAuditDateTime(value) {
      var match = String(value || '').match(
        /^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2}):(\d{2})/
      );
      if (!match) return safeValue(value);

      var hour = parseInt(match[2], 10),
        meridiem = hour >= 12 ? 'PM' : 'AM',
        displayHour = hour % 12 || 12;

      return match[1] + ' ' + String(displayHour).padStart(2, '0') + ':' +
        match[3] + ':' + match[4] + ' ' + meridiem;
    }

    function createAuditDateHtml(value) {
      var formatted = String(formatAuditDateTime(value)),
        match = formatted.match(/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2} (?:AM|PM))$/);
      if (!match) return escapeHtml(formatted);

      return '<div class="audit-date-stack"><strong>' + escapeHtml(match[1]) +
        '</strong><span><i class="fa fa-clock-o"></i> ' + escapeHtml(match[2]) +
        '</span></div>';
    }

    function createAuditEventBadge(eventType) {
      var key = String(eventType || '').toUpperCase(),
        presentation = {
          RECEIPT: ['Receipt', 'audit-event-receipt', 'fa-truck'],
          ASSET_UPDATE: ['Asset Update', 'audit-event-update', 'fa-pencil'],
          ASSIGN: ['Assignment', 'audit-event-assign', 'fa-share'],
          RETURN: ['Return', 'audit-event-return', 'fa-reply'],
          SCRAP_APPROVED: ['Scrap Approved', 'audit-event-approved', 'fa-recycle'],
          SCRAP_REJECTED: ['Scrap Rejected', 'audit-event-danger', 'fa-ban'],
          DELETE: ['Deletion', 'audit-event-danger', 'fa-trash']
        }[key] || [key.replace(/_/g, ' '), 'audit-event-default', 'fa-circle-o'];

      return '<span class="audit-event-pill ' + presentation[1] + '"><i class="fa ' +
        presentation[2] + '"></i>' + escapeHtml(presentation[0]) + '</span>';
    }

    function getLoadingHtml() {
      return '' +
        '<div class="asset-loading-box">' +
        '<i class="fa fa-spinner fa-spin"></i>' +
        '<div>Loading asset information...</div>' +
        '</div>';
    }

    function getErrorHtml(message) {
      return '' +
        '<div class="alert alert-danger" style="margin-bottom:0;">' +
        '<i class="fa fa-exclamation-triangle"></i> ' +
        escapeHtml(message) +
        '</div>';
    }

    function createImageCard(media, stage) {
      var fileName = media.file_name || '';
      var originalName = media.original_name || fileName || stage + ' image';

      var source = media.direct_url || (baseUrl +
        '/Asset/media/' +
        encodeURIComponent(fileName));

      var mediaId = media.media_id ||
        media.id ||
        '';

      return '' +
        '<div class="col-sm-6 asset-image-column">' +
        '<div class="asset-image-card">' +

        '<div class="asset-image-preview" ' +
        'data-image-src="' + escapeHtml(source) + '">' +

        (canOperateAssets ? '<button ' +
        'type="button" ' +
        'class="asset-image-close" ' +
        'data-media-id="' + escapeHtml(mediaId) + '" ' +
        'title="Remove image from view" ' +
        'aria-label="Remove image">' +
        '&times;' +
        '</button>' : '') +

        '<img ' +
        'src="' + escapeHtml(source) + '" ' +
        'alt="' + escapeHtml(originalName) + '" ' +
        'onerror="this.onerror=null;this.src=\'' +
        baseUrl +
        '/public/img/no-image.png\';">' +

        '<div class="asset-image-overlay">' +
        '<i class="fa fa-search-plus"></i> ' +
        'Click to enlarge' +
        '</div>' +

        '</div>' +

        '<div class="asset-image-info">' +
        '<span class="asset-image-name" ' +
        'title="' + escapeHtml(originalName) + '">' +
        escapeHtml(originalName) +
        '</span>' +

        '<i class="fa fa-clock-o"></i> ' +
        escapeHtml(media.uploaded_at || '-') +
        '</div>' +

        '</div>' +
        '</div>';
    }

    function createGallery(media, legacyImage, archiveImageUrl) {
      var groups = {
        ENTRY: [],
        ASSIGN: [],
        RETURN: []
      };

      var labels = {
        ENTRY: 'Entry Images',
        ASSIGN: 'Assignment Images',
        RETURN: 'Return Images'
      };

      var icons = {
        ENTRY: 'fa-download',
        ASSIGN: 'fa-share-square-o',
        RETURN: 'fa-undo'
      };

      var galleryHtml = '';

      (media || []).forEach(function(item) {
        if (
          item.media_stage &&
          Object.prototype.hasOwnProperty.call(
            groups,
            item.media_stage
          )
        ) {
          groups[item.media_stage].push(item);
        }
      });

      if (!groups.ENTRY.length && archiveImageUrl) {
        groups.ENTRY.push({
          direct_url: archiveImageUrl,
          original_name: 'Archived Asset Image',
          uploaded_at: '-'
        });
      } else if (!groups.ENTRY.length && legacyImage) {
        groups.ENTRY.push({
          file_name: legacyImage,
          original_name: 'Asset Image',
          uploaded_at: '-'
        });
      }

      Object.keys(groups).forEach(function(stage) {
        galleryHtml += '' +
          '<div class="asset-gallery-group">' +
          '<h5 class="asset-gallery-heading">' +
          '<i class="fa ' + icons[stage] + '"></i>' +
          labels[stage] +
          '<span class="asset-gallery-count">' +
          groups[stage].length +
          '</span>' +
          '</h5>' +

          '<div class="row asset-image-group-row">';

        if (groups[stage].length) {
          groups[stage].forEach(function(item) {
            galleryHtml += createImageCard(item, stage);
          });
        } else {
          galleryHtml += '<div class="col-xs-12"><div class="asset-no-image">' +
            '<img src="' + baseUrl + '/public/img/no-image.png" alt="No image available">' +
            '<strong>No ' + labels[stage].toLowerCase() + ' available</strong>' +
            '<div>No image has been uploaded for this stage.</div></div></div>';
        }

        galleryHtml += '' +
          '</div>' +
          '</div>';
      });

      return galleryHtml;
    }

    function getStatusClass(status) {
      var normalizedStatus = String(status || '').toUpperCase();

      if (
        normalizedStatus === 'ISSUED' ||
        normalizedStatus === 'ACTIVE' ||
        normalizedStatus === 'AVAILABLE'
      ) {
        return 'asset-status-badge';
      }

      return 'label label-default';
    }

    function createDetailRow(icon, label, value) {
      return '' +
        '<tr>' +
        '<th><i class="fa ' + icon + '"></i> ' + escapeHtml(label) + '</th>' +
        '<td>' + escapeHtml(safeValue(value)) + '</td>' +
        '</tr>';
    }

    function createDetailSection(title) {
      return '<tr><th colspan="2" class="asset-detail-subheading">' +
        escapeHtml(title) +
        '</th></tr>';
    }

    function getConditionClass(condition) {
      condition = String(condition || '').toUpperCase();
      if (condition === 'NEW') return 'label label-primary asset-current-condition';
      if (condition === 'GOOD') return 'label label-success asset-current-condition';
      if (condition === 'USED' || condition === 'REFURBISHED') {
        return 'label label-info asset-current-condition';
      }
      if (condition === 'DAMAGED') {
        return 'label label-danger asset-current-condition';
      }
      return 'label label-default asset-current-condition';
    }

    function getCurrentCondition(asset, isInStock) {
      if (asset.asset_condition) {
        return {
          value: asset.asset_condition,
          source: 'Current condition stored on this asset'
        };
      }

      // Compatibility fallback for databases awaiting the condition migration.
      if (!isInStock && asset.condition_out) {
        return {
          value: asset.condition_out,
          source: asset.issue_no ? 'Current assignment: ' + asset.issue_no : 'Current assignment'
        };
      }

      var returns = (asset.transactions || []).filter(function(transaction) {
        return transaction.condition_in && (transaction.return_no || transaction.return_date);
      });
      if (returns.length) {
        var latestReturn = returns[returns.length - 1];
        var returnReference = String(latestReturn.return_no || '').replace(
          /^(RET-\d{8}-\d{6}-\d{3})-\d{2}$/,
          '$1'
        );
        return {
          value: latestReturn.condition_in,
          source: 'Latest return: ' + safeValue(returnReference) +
            (latestReturn.return_date ? ' / ' + latestReturn.return_date : '')
        };
      }

      return {
        value: asset.condition_on_receipt || 'NOT RECORDED',
        source: asset.condition_on_receipt ? 'Condition recorded on receipt' : 'No condition recorded'
      };
    }

    function createDetailTable(asset) {
      var isInStock = String(asset.assetStat || '').toUpperCase() === 'IN_STOCK';
      var currentCondition = getCurrentCondition(asset, isInStock);
      var holderLabel = asset.assignmentType === 'DEPARTMENT' ? 'Department' : 'Employee';
      var holderValue = asset.assignmentType === 'DEPARTMENT' ? asset.deptName : asset.empName;
      var quantity = safeValue(asset.quantity) + ' ' + safeValue(asset.unit);
      var html = '<table class="table table-bordered asset-info-table">';

      html += createDetailSection('Identification');
      html += '<tr><th><i class="fa fa-barcode"></i> Asset Number</th><td>' +
        '<span class="asset-number-value">' + escapeHtml(safeValue(asset.assetNum)) +
        '</span></td></tr>';
      html += createDetailRow('fa-qrcode', 'Serial Number', asset.serial_number);
      html += createDetailRow('fa-file-text-o', 'Description', asset.Description);
      html += createDetailRow('fa-tags', 'Category', asset.assetKatName);
      html += createDetailRow('fa-industry', 'Manufacturer', asset.manufacturer);
      html += createDetailRow('fa-desktop', 'Model', asset.model);
      html += createDetailRow('fa-map-marker', 'Location', asset.locationName);
      html += createDetailRow('fa-cubes', 'Quantity / Unit', quantity);
      html += '<tr><th><i class="fa fa-info-circle"></i> Status</th><td>' +
        '<span class="' + getStatusClass(asset.assetStatusName) + '">' +
        escapeHtml(safeValue(asset.assetStatusName)) + '</span></td></tr>';
      html += '<tr class="asset-current-condition-row"><th><i class="fa fa-heartbeat"></i> Current Condition</th><td>' +
        '<span class="' + getConditionClass(currentCondition.value) + '">' +
        escapeHtml(String(currentCondition.value || '').replace(/_/g, ' ')) + '</span>' +
        '<small class="asset-condition-source">' + escapeHtml(currentCondition.source) + '</small></td></tr>';

      html += createDetailSection('Purchase and Receipt');
      html += createDetailRow('fa-truck', 'Supplier', asset.supplier_name);
      html += createDetailRow('fa-user', 'Vendor Contact', asset.vendorContact);
      html += createDetailRow('fa-envelope', 'Vendor Email', asset.vendorEmail);
      html += createDetailRow('fa-phone', 'Vendor Phone', asset.vendorPhone);
      html += createDetailRow('fa-map-marker', 'Vendor Address', asset.vendorAddress);
      html += createDetailRow('fa-id-card-o', 'Vendor Tax Number', asset.vendorTaxNumber);
      html += createDetailRow('fa-file-text', 'Receipt Number', asset.receipt_no);
      html += createDetailRow('fa-calendar-check-o', 'Received Date', asset.received_date);
      html += createDetailRow('fa-check-circle', 'Condition on Receipt', asset.condition_on_receipt);
      html += createDetailRow('fa-shopping-cart', 'Purchase Order', asset.po_number);
      html += createDetailRow('fa-file-o', 'Invoice Number', asset.invoice_number);
      if (asset.invoice_pdf) {
        var invoiceFile = String(asset.invoice_pdf).split(/[\\/]/).pop();
        var invoiceUrl = baseUrl + '/Asset/downloadInvoice/' +
          encodeURIComponent(invoiceFile);
        html += '<tr><th><i class="fa fa-file-pdf-o"></i> Invoice Document</th>' +
          '<td><a class="btn btn-xs btn-danger" target="_blank" rel="noopener" href="' +
          escapeHtml(invoiceUrl) + '"><i class="fa fa-file-pdf-o"></i> View PDF</a></td></tr>';
      } else {
        html += createDetailRow('fa-file-pdf-o', 'Invoice Document', '-');
      }
      html += createDetailRow('fa-calendar', 'Purchase Date', asset.purchase_date);
      html += createDetailRow('fa-shield', 'Warranty Expiry', asset.warranty_expiry);
      html += createDetailRow('fa-user-plus', 'Received By', asset.received_by);
      // Asset Entry has one user-entered notes field. Older records may store the
      // same value in legacy asset/item columns, so use them only as fallbacks
      // instead of displaying three duplicate rows.
      var entryNotes = asset.receiptNotes || asset.receiptItemRemarks || asset.assetNotes;
      html += createDetailRow('fa-comment-o', 'Entry Notes / Remarks', entryNotes);

      if (!isInStock) {
        html += createDetailSection('Current Assignment');
        html += createDetailRow('fa-user', holderLabel, holderValue);
        if (asset.assignmentType === 'USER') {
          html += createDetailRow('fa-id-badge', 'Employee ID', asset.empId);
        }
        html += createDetailRow('fa-random', 'Assignment Type', asset.assignmentType);
        html += createDetailRow('fa-file-text-o', 'Issue Number', asset.issue_no);
        html += createDetailRow('fa-calendar', 'Issue Date', asset.issue_date);
        html += createDetailRow('fa-calendar-o', 'Expected Return', asset.expected_return_date);
        html += createDetailRow('fa-check-square-o', 'Condition Out', asset.condition_out);
        html += createDetailRow('fa-comment', 'Purpose / Remarks', asset.purpose);
        html += createDetailRow('fa-user-circle', 'Issued By', asset.issued_by);
        html += createDetailRow('fa-check', 'Acknowledged By', asset.acknowledged_by);
      }

      html += createDetailSection('Record Information');
      html += createDetailRow('fa-user-plus', 'Created By', asset.createdBy);
      html += createDetailRow('fa-calendar', 'Created Date', asset.createdDate);
      html += createDetailRow('fa-clock-o', 'Last Updated', asset.updated_at);

      return html + '</table>';
    }

    function createReturnHistory(transactions) {
      var returnedTransactions = (transactions || []).filter(function(transaction) {
        return transaction.return_no || transaction.return_date;
      });

      if (!returnedTransactions.length) {
        return '<p class="asset-history-empty"><i class="fa fa-info-circle"></i> ' +
          'No return history is recorded for this asset.</p>';
      }

      var html = '<div class="table-responsive"><table class="table table-bordered ' +
        'table-striped asset-history-table"><thead><tr>' +
        '<th>Issue No.</th><th>Return No.</th><th>Return Date</th><th>Type</th>' +
        '<th>Assigned To</th><th>Condition</th><th>Remarks</th><th>Received By</th>' +
        '</tr></thead><tbody>';

      returnedTransactions.forEach(function(transaction) {
        var holder = transaction.employee_name || transaction.deptName ||
          transaction.acknowledged_by;

        html += '<tr>' +
          '<td>' + escapeHtml(safeValue(transaction.issue_no)) + '</td>' +
          '<td>' + escapeHtml(safeValue(transaction.return_no)) + '</td>' +
          '<td>' + escapeHtml(safeValue(transaction.return_date)) + '</td>' +
          '<td>' + escapeHtml(safeValue(transaction.return_type)) + '</td>' +
          '<td>' + escapeHtml(safeValue(holder)) + '</td>' +
          '<td>' + escapeHtml(safeValue(transaction.condition_in)) + '</td>' +
          '<td>' + escapeHtml(safeValue(transaction.reason)) + '</td>' +
          '<td>' + escapeHtml(safeValue(transaction.received_by)) + '</td>' +
          '</tr>';
      });

      return html + '</tbody></table></div>';
    }

    function createTransactionTimeline(events) {
      events = events || [];
      if (!events.length) {
        return '<p class="asset-history-empty"><i class="fa fa-info-circle"></i> ' +
          'No transactions are recorded for this asset.</p>';
      }

      var html = '<details class="asset-transaction-card" open>' +
        '<summary style="cursor:pointer;font-weight:600;padding:10px 0">' +
        '<i class="fa fa-list-alt"></i> Show / hide ' + events.length +
        ' transaction(s)</summary>' +
        '<div class="table-responsive"><table class="table table-bordered table-striped asset-history-table">' +
        '<thead><tr><th>Date</th><th>Event</th><th>Reference</th><th>Actor</th>' +
        '<th>Assigned / Accepted For</th><th>Condition</th><th>Details</th></tr></thead><tbody>';

      events.forEach(function(event) {
        html += '<tr>' +
          '<td>' + createAuditDateHtml(event.eventDate) + '</td>' +
          '<td>' + createAuditEventBadge(event.eventType) + '</td>' +
          '<td>' + escapeHtml(safeValue(event.referenceNo)) + '</td>' +
          '<td>' + escapeHtml(safeValue(event.actor)) + '</td>' +
          '<td>' + escapeHtml(safeValue(event.assignedTo || event.department)) + '</td>' +
          '<td>' + escapeHtml(safeValue(event.conditionInfo)) + '</td>' +
          '<td>' + escapeHtml(safeValue(event.details)) + '</td>' +
          '</tr>';
      });

      return html + '</tbody></table></div></details>';
    }

    function renderAssetDetails(asset) {
      var gallery = createGallery(
        asset.media || [],
        asset.assetImage,
        asset.archiveImageUrl
      );

      var html = '' +
        '<div class="row">' +

        '<div class="col-md-6">' +
        '<div class="asset-detail-panel">' +
        '<h4 class="asset-section-title">' +
        '<i class="fa fa-picture-o"></i> ' +
        'Asset Images' +
        '</h4>' +
        gallery +
        '</div>' +
        '</div>' +

        '<div class="col-md-6">' +
        '<div class="asset-detail-panel">' +
        '<h4 class="asset-section-title">' +
        '<i class="fa fa-info-circle"></i> ' +
        'Asset Information' +
        '</h4>' +
        createDetailTable(asset) +
        '</div>' +
        '</div>' +

        '<div class="col-xs-12">' +
        '<div class="asset-detail-panel">' +
        '<h4 class="asset-section-title"><i class="fa fa-history"></i> Complete Asset Transaction History</h4>' +
        createTransactionTimeline(asset.timeline || []) +
        '</div>' +
        '</div>' +

        '</div>';

      window.jQuery('#shared-asset-detail').html(html);
    }

    function openImageLightbox(imageSource) {
      var lightbox = document.getElementById(
        'assetImageLightbox'
      );

      var previewImage = document.getElementById(
        'assetLightboxImage'
      );

      previewImage.src = imageSource;
      lightbox.classList.add('active');
      lightbox.setAttribute('aria-hidden', 'false');

      document.body.style.overflow = 'hidden';
    }

    function closeImageLightbox() {
      var lightbox = document.getElementById(
        'assetImageLightbox'
      );

      var previewImage = document.getElementById(
        'assetLightboxImage'
      );

      lightbox.classList.remove('active');
      lightbox.setAttribute('aria-hidden', 'true');

      previewImage.src = '';
      document.body.style.overflow = '';
    }

    document.addEventListener('click', function(event) {
      var assetButton = event.target.closest(
        '.asset-detail-link, [data-asset-details]'
      );

      if (assetButton) {
        event.preventDefault();

        var assetNum = assetButton.getAttribute('data-asset') ||
          assetButton.getAttribute('data-asset-details');

        document.getElementById('assetStickerLink').href = baseUrl + '/Asset/printLabel/' +
          encodeURIComponent(assetNum);

        var $ = window.jQuery;

        $('#sharedAssetDetailModal').modal('show');

        $('#sharedAssetDetailModalLabel').html(
          '<i class="fa fa-desktop"></i> Asset ' +
          escapeHtml(assetNum) +
          ' Details'
        );

        $('#shared-asset-detail').html(
          getLoadingHtml()
        );

        $.get(
            baseUrl +
            '/Asset/details/' +
            encodeURIComponent(assetNum)
          )
          .done(function(response) {
            var asset = response;

            if (
              typeof response === 'string'
            ) {
              try {
                asset = JSON.parse(response);
              } catch (error) {
                $('#shared-asset-detail').html(
                  getErrorHtml(
                    'Invalid response received from the server.'
                  )
                );

                return;
              }
            }

            if (!asset || !asset.assetNum) {
              $('#shared-asset-detail').html(
                getErrorHtml(
                  'Asset information was not found.'
                )
              );

              return;
            }

            renderAssetDetails(asset);
          })
          .fail(function(xhr) {
            var message =
              'Unable to load asset details.';

            if (
              xhr.responseJSON &&
              xhr.responseJSON.message
            ) {
              message = xhr.responseJSON.message;
            }

            $('#shared-asset-detail').html(
              getErrorHtml(message)
            );
          });

        return;
      }

      var removeButton = event.target.closest(
        '.asset-image-close'
      );

      if (removeButton) {
        event.preventDefault();
        event.stopPropagation();

        var $ = window.jQuery;
        var mediaId = removeButton.getAttribute('data-media-id');
        var imageColumn = removeButton.closest('.asset-image-column');

        if (!mediaId) {
          alert('This image cannot be deleted because its media ID is missing.');
          return;
        }

        if (!window.confirm(
            'Are you sure you want to permanently delete this image?'
          )) {
          return;
        }

        removeButton.disabled = true;
        removeButton.innerHTML =
          '<i class="fa fa-spinner fa-spin"></i>';

        $.ajax({
            url: baseUrl + '/Asset/deleteMedia',
            type: 'POST',
            dataType: 'json',
            data: {
              media_id: mediaId,
              csrf_token: '<?= htmlspecialchars(
                $_SESSION["csrf_media_delete"],
                ENT_QUOTES,
                "UTF-8",
            ) ?>'
            }
          })
          .done(function(response) {
            if (!response || response.success !== true) {
              alert(
                response && response.message ?
                response.message :
                'Unable to delete the image.'
              );

              removeButton.disabled = false;
              removeButton.innerHTML = '&times;';
              return;
            }

            if (!imageColumn) {
              return;
            }

            var groupRow = imageColumn.closest(
              '.asset-image-group-row'
            );

            var galleryGroup = imageColumn.closest(
              '.asset-gallery-group'
            );

            imageColumn.parentNode.removeChild(imageColumn);

            /*
             * Update the image counter.
             */
            if (galleryGroup) {
              var remainingImages = galleryGroup.querySelectorAll(
                '.asset-image-column'
              ).length;

              var countElement = galleryGroup.querySelector(
                '.asset-gallery-count'
              );

              if (countElement) {
                countElement.textContent = remainingImages;
              }

              /*
               * Remove the complete stage group when empty.
               */
              if (remainingImages === 0) {
                galleryGroup.parentNode.removeChild(
                  galleryGroup
                );
              }
            }

            /*
             * Show "No images" when every image is deleted.
             */
            if (
              !document.querySelector(
                '#shared-asset-detail .asset-image-column'
              )
            ) {
              var imagePanels = document.querySelectorAll(
                '#shared-asset-detail .asset-detail-panel'
              );

              var imagePanel = imagePanels.length ?
                imagePanels[0] :
                null;

              if (imagePanel) {
                var title = imagePanel.querySelector(
                  '.asset-section-title'
                );

                imagePanel.innerHTML =
                  (title ? title.outerHTML : '') +
                  '<div class="asset-no-image">' +
                  '<img src="' + baseUrl +
                  '/public/img/no-image.png" alt="No image available">' +
                  '<strong>No images available</strong>' +
                  '<div>All images have been deleted.</div>' +
                  '</div>';
              }
            }
          })
          .fail(function(xhr) {
            var message = 'Unable to delete the image.';

            if (
              xhr.responseJSON &&
              xhr.responseJSON.message
            ) {
              message = xhr.responseJSON.message;
            }

            alert(message);

            removeButton.disabled = false;
            removeButton.innerHTML = '&times;';
          });

        return;
      }
      var imagePreview = event.target.closest(
        '.asset-image-preview'
      );

      if (imagePreview) {
        openImageLightbox(
          imagePreview.getAttribute(
            'data-image-src'
          )
        );

        return;
      }

      if (
        event.target.closest(
          '.asset-lightbox-close'
        ) ||
        event.target.id === 'assetImageLightbox'
      ) {
        closeImageLightbox();
      }
    });

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeImageLightbox();
      }
    });

    var sharedModal = window.jQuery('#sharedAssetDetailModal');
    var resetSharedModal = function() {
        window.jQuery(
          '#shared-asset-detail'
        ).html(getLoadingHtml());

        closeImageLightbox();
      };

    if (typeof sharedModal.on === 'function') {
      sharedModal.on('hidden.bs.modal', resetSharedModal);
    } else if (typeof sharedModal.bind === 'function') {
      sharedModal.bind('hidden.bs.modal', resetSharedModal);
    }

  })();
</script>
