<?php
$d = $data['roleDashboard'] ?? [];
$context = $d['context'] ?? [];
$roles = $context['roles'] ?? [];
$personal = $d['personal'] ?? ['summary' => [], 'recent' => [], 'assets' => []];
$ps = $personal['summary'] ?? [];
$workflowUiEnabled = $data['workflowUiEnabled'] ??
    (defined('WORKFLOW_UI_ENABLED') ? WORKFLOW_UI_ENABLED : true);
$issuerUiEnabled = $data['issuerUiEnabled'] ??
    (defined('ISSUER_UI_ENABLED') ? ISSUER_UI_ENABLED : false);
$esc = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusLabel = static fn($status) => ucwords(strtolower(str_replace('_', ' ', (string) $status)));
$summaryCards = [
    ['My Requests', $ps['total'] ?? 0, BASEURL . '/Request?view=mine'],
    [
        'Pending Approval',
        $ps['pending'] ?? 0,
        BASEURL . '/Request?view=mine&pending=1',
    ],
    [
        'Awaiting My Acceptance',
        $ps['awaiting_acceptance'] ?? 0,
        BASEURL . '/Request?view=mine',
    ],
    [
        'My Assigned Assets',
        $personal['assetCount'] ?? 0,
        BASEURL . '/Asset/myAssets',
    ],
];
if (in_array('DEPARTMENT_MANAGER', $roles, true)) {
    $summaryCards[] = [
        'Department Requests',
        $d['department']['total'] ?? 0,
        BASEURL . '/Request?view=department',
    ];
}
?>
<style>
.role-dashboard .summary-card{display:block;background:#fff;border:1px solid #e4e9f0;border-left:4px solid #087f5b;border-radius:10px;padding:17px;margin-bottom:16px;text-decoration:none;color:#172033;min-height:104px}.role-dashboard .summary-card h2{margin:0;font-size:28px}.role-dashboard .summary-card p{margin:7px 0 0;color:#687386}.role-dashboard .panel-box{background:#fff;border:1px solid #e4e9f0;border-radius:10px;margin-bottom:20px;overflow:hidden}.role-dashboard .panel-box h4{margin:0;padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e4e9f0}.role-dashboard .panel-body{padding:16px}.role-dashboard .quick-action{margin:0 8px 8px 0}.role-dashboard .status-pill{display:inline-block;padding:3px 8px;border-radius:12px;background:#eef3f7;font-size:12px}.role-dashboard .welcome{margin-bottom:18px}.role-dashboard .welcome h3{margin-bottom:4px}
</style>
<div class="page-content role-dashboard"><div class="container-fluid">
  <div class="welcome"><h3>Welcome, <?= $esc(($context['fullname'] ?? '') ?: ($context['nama'] ?? $context['username'] ?? 'User')) ?></h3><p class="text-muted"><?= $workflowUiEnabled
      ? 'Your dashboard shows only the requests, assets, and actions relevant to your account.'
      : ($issuerUiEnabled && in_array('ISSUER', $roles, true)
          ? 'Issuer asset operations and reporting are available. The general request workflow remains paused.'
          : 'The request and approval interface is temporarily paused. Administrative asset operations remain available.') ?></p></div>

  <?php if ($workflowUiEnabled): ?>
  <!-- Preserved request, approval and self-service dashboard. -->
  <div class="row">
    <?php foreach ($summaryCards as $card): ?>
      <div class="col-lg-3 col-sm-6"><a class="summary-card" href="<?= $esc($card[2]) ?>"><h2><?= (int)$card[1] ?></h2><p><?= $esc($card[0]) ?></p></a></div>
    <?php endforeach; ?>
  </div>

  <div class="panel-box"><h4><i class="fa fa-bolt"></i> Quick Actions</h4><div class="panel-body">
    <a class="btn btn-primary quick-action" href="<?= BASEURL ?>/Request/create"><i class="fa fa-plus"></i> New Equipment Request</a>
    <a class="btn btn-default quick-action" href="<?= BASEURL ?>/Request?view=mine"><i class="fa fa-list"></i> View My Requests</a>
    <?php if (in_array('HR_REQUESTER',$roles,true)): ?><a class="btn btn-default quick-action" href="<?= BASEURL ?>/Request/create"><i class="fa fa-user-plus"></i> Request for Employee</a><?php endif; ?>
    <?php if (in_array('DEPARTMENT_MANAGER',$roles,true)): ?><a class="btn btn-warning quick-action" href="<?= BASEURL ?>/Request?view=all&pending=1"><i class="fa fa-check-square-o"></i> Department Approvals (<?= (int)($d['department']['pending'] ?? 0) ?>)</a><?php endif; ?>
    <?php if (in_array('IT_MANAGER',$roles,true)): ?><a class="btn btn-warning quick-action" href="<?= BASEURL ?>/Request?view=all&pending=1"><i class="fa fa-check"></i> IT Approvals (<?= (int)($d['it']['pending'] ?? 0) ?>)</a><?php endif; ?>
    <?php if (in_array('ISSUER',$roles,true)): ?><a class="btn btn-success quick-action" href="<?= BASEURL ?>/Request?view=all&pending=1"><i class="fa fa-share-square-o"></i> Issue Queue (<?= (int)($d['issuer']['waiting'] ?? 0) ?>)</a><?php endif; ?>
  </div></div>

  <?php if (in_array('IT_MANAGER',$roles,true) || in_array('ADMINISTRATOR',$roles,true)): ?>
  <div class="row">
    <?php if (in_array('IT_MANAGER',$roles,true)): ?>
      <div class="col-md-6"><div class="panel-box"><h4>IT Operations</h4><div class="panel-body">
        <div class="row text-center"><div class="col-xs-4"><h3><?= (int)($d['it']['pending'] ?? 0) ?></h3><small>Pending IT approval</small></div><div class="col-xs-4"><h3><?= (int)($d['it']['waitingIssue'] ?? 0) ?></h3><small>Waiting issuance</small></div><div class="col-xs-4"><h3><?= (int)($d['it']['asset']['in_stock'] ?? 0) ?></h3><small>Assets in stock</small></div></div>
      </div></div></div>
    <?php endif; ?>
    <?php if (in_array('ADMINISTRATOR',$roles,true)): $as=$d['administrator']['statuses'] ?? []; ?>
      <div class="col-md-6"><div class="panel-box"><h4>Administrator Overview</h4><div class="panel-body">
        <div class="row text-center"><div class="col-xs-4"><h3><?= (int)($d['administrator']['activeUsers'] ?? 0) ?></h3><small>Active users</small></div><div class="col-xs-4"><h3><?= (int)(($as['PENDING_DEPARTMENT_APPROVAL'] ?? 0)+($as['PENDING_IT_APPROVAL'] ?? 0)) ?></h3><small>Pending approvals</small></div><div class="col-xs-4"><h3><?= (int)($as['APPROVED'] ?? 0) ?></h3><small>Waiting for issuer</small></div></div>
      </div></div></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-7"><div class="panel-box"><h4><i class="fa fa-file-text-o"></i> Recent Requests</h4><div class="panel-body table-responsive"><table class="table table-striped"><thead><tr><th>Request</th><th>Status</th><th>Required Date</th><th>Created</th></tr></thead><tbody>
      <?php foreach ($personal['recent'] ?? [] as $r): ?><tr><td><a href="<?= BASEURL ?>/Request/details/<?= (int)$r['request_id'] ?>"><?= $esc($r['request_no']) ?></a></td><td><span class="status-pill"><?= $esc($statusLabel($r['status'])) ?></span></td><td><?= $esc($r['required_date'] ?: '-') ?></td><td><?= $esc($r['created_at']) ?></td></tr><?php endforeach; ?>
      <?php if (empty($personal['recent'])): ?><tr><td colspan="4" class="text-center text-muted">No requests yet.</td></tr><?php endif; ?>
    </tbody></table></div></div></div>
    <div class="col-md-5"><div class="panel-box" id="assigned-assets"><h4><i class="fa fa-laptop"></i> My Assigned Assets</h4><div class="panel-body table-responsive"><table class="table table-striped"><thead><tr><th>Asset</th><th>Category</th><th>Issue No.</th></tr></thead><tbody>
      <?php foreach ($personal['assets'] ?? [] as $a): ?><tr><td><?= $esc($a['assetNum']) ?><br><small><?= $esc($a['Description']) ?></small></td><td><?= $esc($a['category'] ?: '-') ?></td><td><?= $esc($a['issue_no']) ?></td></tr><?php endforeach; ?>
      <?php if (empty($personal['assets'])): ?><tr><td colspan="3" class="text-center text-muted">No assets currently assigned.</td></tr><?php endif; ?>
    </tbody></table></div></div></div>
  </div>
  <?php else: ?>
  <div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    Equipment requests, user self-service, and return-request approval are temporarily hidden.
    <?= $issuerUiEnabled && in_array('ISSUER', $roles, true)
        ? 'Issuer asset operations remain available from the navigation.'
        : 'No workflow records or configuration have been removed.' ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($data['isFullAnalytics'])): ?><div class="panel-box"><h4><i class="fa fa-line-chart"></i> Operational Analytics</h4><div class="panel-body"><p class="text-muted">Asset inventory and operational status overview.</p></div></div><?php include __DIR__.'/analytics.php'; endif; ?>
</div></div>
