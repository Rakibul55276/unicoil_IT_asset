"""QA: end-to-end workflow tests with negative (bypass) attempts at every step."""
import sys, re
from lib import *
from db import q, one

results = []
def check(name, cond, detail=""):
    results.append((name, bool(cond), str(detail)))
    print(("PASS " if cond else "FAIL ") + name + (("  -- " + str(detail)) if (detail and not cond) else ""))

S = {u: session_for(u) for u in ["qa_emp","qa_emp2","qa_hr","qa_dm","qa_dm2","qa_itm","qa_iss","qa_adm"]}
def tok(u, page="/Request/create"):
    return csrf_from(get(S[u], page).text)
def status(rid): return one("SELECT status FROM asset_request WHERE request_id=%d" % rid)
def latest_req(user): return int(one("SELECT MAX(request_id) FROM asset_request WHERE requested_by='%s'" % user) or 0)
def act(u, action, rid, extra=None, token=True):
    d = {"csrf_token": tok(u)} if token else {}
    d.update(extra or {})
    return post(S[u], "/Request/%s/%d" % (action, rid), d)

# pick a category with >=2 in-stock assets
cat = one("SELECT assetKat FROM assetdata WHERE assetStat='IN_STOCK' GROUP BY assetKat HAVING COUNT(*)>=3 ORDER BY assetKat LIMIT 1")
print("Using category", cat)

def create(u, extra):
    d = {"csrf_token": tok(u, "/Request/create"), "business_reason": "QA test", "category[]": [cat], "quantity[]": ["1"]}
    d.update(extra)
    r = post(S[u], "/Request/store", d)
    return latest_req(u), r

# ---------- 1. Employee self request: full happy path + bypass attempts ----------
before = latest_req("qa_emp")
rid, r = create("qa_emp", {"request_target_type": "SELF"})
check("WF1 employee creates self request", rid > before and status(rid) == "PENDING_DEPARTMENT_APPROVAL", status(rid))
check("WF1 route: dept step waits on own dept head (qa_dm)", one("SELECT approver_username FROM asset_request_approval_step WHERE request_id=%d AND status='WAITING'" % rid) == "qa_dm")
check("WF1 IT step is QUEUED (not visible early)", one("SELECT status FROM asset_request_approval_step WHERE request_id=%d AND step_type='IT_MANAGER'" % rid) == "QUEUED")
r = act("qa_emp", "approveDepartment", rid); check("WF1 requester cannot self-approve (403)", r.status_code == 403 and status(rid) == "PENDING_DEPARTMENT_APPROVAL", r.status_code)
r = act("qa_dm2", "approveDepartment", rid); check("WF1 other dept manager cannot approve", r.status_code == 403, r.status_code)
r = act("qa_itm", "approveIt", rid, {"approved_quantity[1]": "1"}); check("WF1 IT Manager cannot act before dept step", r.status_code == 403 and status(rid) == "PENDING_DEPARTMENT_APPROVAL", r.status_code)
r = act("qa_itm", "approveDepartment", rid); check("WF1 IT Manager cannot take dept step", r.status_code == 403, r.status_code)
r = act("qa_adm", "approveDepartment", rid); check("WF1 Administrator cannot silently approve as dept manager", r.status_code == 403, r.status_code)
r = act("qa_iss", "issue", rid); check("WF1 issuer cannot issue before approval", status(rid) == "PENDING_DEPARTMENT_APPROVAL")
r = act("qa_emp", "accept", rid); check("WF1 receiver cannot accept before issue", status(rid) == "PENDING_DEPARTMENT_APPROVAL")
r = act("qa_dm", "approveDepartment", rid, token=False); check("WF1 approval without CSRF token rejected (419)", r.status_code == 419, r.status_code)
r = get(S["qa_dm"], "/Request/approveDepartment/%d" % rid); check("WF1 approval via GET rejected (405)", r.status_code == 405, r.status_code)
r = get(S["qa_emp2"], "/Request/details/%d" % rid); check("WF1 unrelated employee cannot view request", r.status_code == 403, r.status_code)
r = get(S["qa_itm"], "/Request/details/%d" % rid); check("WF1 queued IT approver cannot view early", r.status_code == 403, r.status_code)
r = act("qa_dm", "approveDepartment", rid); check("WF1 dept manager approves -> PENDING_IT_APPROVAL", status(rid) == "PENDING_IT_APPROVAL", status(rid))
r = act("qa_dm", "approveDepartment", rid); check("WF1 dept manager cannot approve twice", r.status_code == 403 and status(rid) == "PENDING_IT_APPROVAL", r.status_code)
r = act("qa_dm", "reject", rid, {"reason": "x"}); check("WF1 dept manager cannot reject after own step", r.status_code == 403, r.status_code)
item = int(one("SELECT request_item_id FROM asset_request_item WHERE request_id=%d" % rid))
r = act("qa_emp", "approveIt", rid, {"approved_quantity[%d]" % item: "1"}); check("WF1 employee cannot IT-approve", r.status_code == 403, r.status_code)
r = act("qa_itm", "approveIt", rid, {"approved_quantity[%d]" % item: "5"}); check("WF1 IT cannot approve more than requested", status(rid) == "PENDING_IT_APPROVAL")
r = act("qa_itm", "approveIt", rid, {"approved_quantity[%d]" % item: "1"}); check("WF1 IT approves -> APPROVED", status(rid) == "APPROVED", status(rid))
asset = one("SELECT assetNum FROM assetdata WHERE assetStat='IN_STOCK' AND assetKat='%s' ORDER BY assetNum LIMIT 1" % cat)
wrong_cat = one("SELECT assetNum FROM assetdata WHERE assetStat='IN_STOCK' AND assetKat<>'%s' LIMIT 1" % cat)
assigned = one("SELECT assetNum FROM assetdata WHERE assetStat='ASSIGNED' AND assetKat='%s' LIMIT 1" % cat)
r = act("qa_itm", "issue", rid, {"item_assets[%d][]" % item: asset}); check("WF1 IT Manager (non-issuer) cannot issue", r.status_code == 403 and status(rid) == "APPROVED", r.status_code)
r = act("qa_adm", "issue", rid, {"item_assets[%d][]" % item: asset}); check("WF1 Administrator without ISSUER role cannot issue", r.status_code == 403, r.status_code)
r = act("qa_iss", "issue", rid, {"item_assets[%d][]" % item: wrong_cat}); check("WF1 issuer cannot issue wrong-category asset", status(rid) == "APPROVED")
if assigned:
    r = act("qa_iss", "issue", rid, {"item_assets[%d][]" % item: assigned}); check("WF1 issuer cannot issue an already-assigned asset", status(rid) == "APPROVED")
r = act("qa_iss", "issue", rid, {"item_assets[999999][]": asset}); check("WF1 issuer cannot inject foreign request item", status(rid) == "APPROVED")
r = act("qa_iss", "issue", rid, {"item_assets[%d][]" % item: asset, "condition_out[%s]" % asset: "GOOD"})
check("WF1 issuer issues -> PENDING_RECEIVER_ACCEPTANCE", status(rid) == "PENDING_RECEIVER_ACCEPTANCE", status(rid))
check("WF1 asset now ASSIGNED to qa_emp", q("SELECT assetStat FROM assetdata WHERE assetNum='%s'" % asset)[0][0] == "ASSIGNED")
asset2 = one("SELECT assetNum FROM assetdata WHERE assetStat='IN_STOCK' AND assetKat='%s' ORDER BY assetNum LIMIT 1" % cat)
r = act("qa_iss", "issue", rid, {"item_assets[%d][]" % item: asset2}); check("WF1 request cannot be issued twice", one("SELECT COUNT(*) FROM asset_request_allocation a JOIN asset_request_item i ON i.request_item_id=a.request_item_id WHERE i.request_id=%d" % rid) == "1")
r = act("qa_iss", "accept", rid); check("WF1 issuer cannot accept on receiver's behalf", r.status_code == 403, r.status_code)
r = act("qa_emp2", "accept", rid); check("WF1 other employee cannot accept", r.status_code == 403, r.status_code)
r = act("qa_emp", "accept", rid); check("WF1 receiver accepts -> COMPLETED", status(rid) == "COMPLETED", status(rid))
r = act("qa_adm", "cancel", rid, {"reason": "x"}); check("WF1 completed request cannot be cancelled", status(rid) == "COMPLETED")
r = act("qa_adm", "adminChangeApprovalStatus", rid, {"target_status": "PENDING_IT_APPROVAL", "reason": "x"}); check("WF1 admin override blocked after issue", status(rid) == "COMPLETED")
events = [e[0] for e in q("SELECT event_type FROM asset_request_event WHERE request_id=%d ORDER BY request_event_id" % rid)] if one("SHOW COLUMNS FROM asset_request_event LIKE 'request_event_id'") else [e[0] for e in q("SELECT event_type FROM asset_request_event WHERE request_id=%d" % rid)]
check("WF1 audit trail has every step", all(x in events for x in ["CREATED","MANAGER_APPROVED","IT_APPROVED","RECEIVER_ACCEPTED"]), str(events))
issue_id = int(one("SELECT issue_id FROM asset_issue WHERE assetNum='%s' AND status='ISSUED'" % asset))

# ---------- 2. Document ownership ----------
r = get(S["qa_emp"], "/Asset/downloadAssignmentPdf/%d" % issue_id); check("DOC owner can download own assignment PDF", r.status_code == 200 and r.content[:4] == b"%PDF", r.status_code)
r = get(S["qa_dm"], "/Asset/downloadAssignmentPdf/%d" % issue_id); check("DOC approving dept manager can download it", r.status_code == 200, r.status_code)
r = get(S["qa_emp2"], "/Asset/downloadAssignmentPdf/%d" % issue_id); check("DOC unrelated employee blocked", r.status_code == 403, r.status_code)
r = get(S["qa_dm2"], "/Asset/printAssignment/%d" % issue_id); check("DOC other dept manager blocked", r.status_code == 403, r.status_code)
r = get(S["qa_emp"], "/Asset/downloadAssignmentPdf/47"); check("DOC employee blocked from someone else's PDF (IDOR)", r.status_code == 403, r.status_code)
r = get(S["qa_emp"], "/Asset/downloadAssignmentPdf/%d,47" % issue_id); check("DOC mixing own+foreign id blocked", r.status_code == 403, r.status_code)

# ---------- 3. Return request + physical return ----------
c = tok("qa_emp", "/Asset/myAssets")
r = post(S["qa_emp2"], "/Asset/requestReturn", {"csrf_token": tok("qa_emp2", "/Asset/myAssets"), "issue_ids[]": [str(issue_id)], "reason": "x"})
check("RET other employee cannot request return of my asset", one("SELECT COUNT(*) FROM asset_return_request WHERE issue_id=%d" % issue_id) == "0")
r = post(S["qa_emp"], "/Asset/requestReturn", {"csrf_token": c, "issue_ids[]": [str(issue_id)], "reason": "QA return"})
check("RET owner creates return request (PENDING)", one("SELECT status FROM asset_return_request WHERE issue_id=%d ORDER BY return_request_id DESC LIMIT 1" % issue_id) == "PENDING")
check("RET stock unchanged until physical return", one("SELECT assetStat FROM assetdata WHERE assetNum='%s'" % asset) == "ASSIGNED")
rrq = one("SELECT return_request_no FROM asset_return_request WHERE issue_id=%d ORDER BY return_request_id DESC LIMIT 1" % issue_id) or ""
rrq = re.sub(r"-\d{2}$", "", rrq) if re.match(r"^RRQ-\d{8}-\d{6}-\d{3}-\d{2}$", rrq) else rrq
ret_data = {"returnRequestBatch": rrq, "issueIds[]": [str(issue_id)], "returnDate": "2026-11-19T10:00", "returnType": "REISSUE", "conditionIn[%d]" % issue_id: "GOOD", "remarks": "QA", "return_source": "requests"}
d = dict(ret_data, csrf_token="x"); r = post(S["qa_emp"], "/Asset/storeReturn", d)
check("RET employee cannot process physical return", r.status_code == 403, r.status_code)
d = dict(ret_data, csrf_token=csrf_from(get(S["qa_iss"], "/Asset/returnRequests").text), returnType="SCRAP")
r = post(S["qa_iss"], "/Asset/storeReturn", d)
check("RET issuer cannot scrap via return form (bypass scrap approval)", one("SELECT assetStat FROM assetdata WHERE assetNum='%s'" % asset) == "ASSIGNED")
d = dict(ret_data, csrf_token=csrf_from(get(S["qa_iss"], "/Asset/returnRequests").text))
r = post(S["qa_iss"], "/Asset/storeReturn", d)
check("RET issuer completes return -> IN_STOCK", one("SELECT assetStat FROM assetdata WHERE assetNum='%s'" % asset) == "IN_STOCK")
check("RET return request closed as COMPLETED", one("SELECT status FROM asset_return_request WHERE issue_id=%d ORDER BY return_request_id DESC LIMIT 1" % issue_id) == "COMPLETED")
ret_id = int(one("SELECT MAX(return_id) FROM asset_return WHERE issue_id=%d" % issue_id) or 0)
r = get(S["qa_emp"], "/Asset/downloadReturnPdf/%d" % ret_id); check("DOC owner can download own return PDF", r.status_code == 200, r.status_code)
r = get(S["qa_emp2"], "/Asset/downloadReturnPdf/%d" % ret_id); check("DOC unrelated employee blocked from return PDF", r.status_code == 403, r.status_code)

# ---------- 4. Scrap approval ----------
sc = csrf_from(get(S["qa_iss"], "/Scrap/create").text)
r = post(S["qa_emp"], "/Scrap/store", {"csrf_token": sc, "assetNum": asset, "reason": "x"}); check("SCRAP employee cannot create scrap request", r.status_code in (302, 403) and one("SELECT COUNT(*) FROM asset_scrap_request WHERE assetNum='%s'" % asset) == "0", r.status_code)
r = post(S["qa_iss"], "/Scrap/store", {"csrf_token": sc, "assetNum": asset, "reason": "QA scrap"})
sid = int(one("SELECT MAX(scrap_request_id) FROM asset_scrap_request WHERE assetNum='%s'" % asset) or 0)
check("SCRAP issuer creates scrap request (asset not yet scrapped)", sid > 0 and one("SELECT assetStat FROM assetdata WHERE assetNum='%s'" % asset) == "IN_STOCK")
assigned_any = one("SELECT assetNum FROM assetdata WHERE assetStat='ASSIGNED' LIMIT 1")
post(S["qa_iss"], "/Scrap/store", {"csrf_token": sc, "assetNum": assigned_any, "reason": "x"})
check("SCRAP cannot request scrap of an assigned asset", one("SELECT COUNT(*) FROM asset_scrap_request WHERE assetNum='%s'" % assigned_any) == "0")
r = post(S["qa_iss"], "/Scrap/approve/%d" % sid, {"csrf_token": csrf_from(get(S["qa_iss"], "/Scrap/create").text)}); check("SCRAP issuer cannot approve own scrap", r.status_code == 403, r.status_code)
r = post(S["qa_emp"], "/Scrap/approve/%d" % sid, {"csrf_token": "x"}); check("SCRAP employee cannot approve", r.status_code in (403, 419) and one("SELECT assetStat FROM assetdata WHERE assetNum='%s'" % asset) == "IN_STOCK", r.status_code)
r = post(S["qa_itm"], "/Scrap/approve/%d" % sid, {"csrf_token": csrf_from(get(S["qa_itm"], "/Scrap").text)})
check("SCRAP IT Manager approves -> SCRAPPED", one("SELECT assetStat FROM assetdata WHERE assetNum='%s'" % asset) == "SCRAPPED")
r = post(S["qa_itm"], "/Scrap/approve/%d" % sid, {"csrf_token": csrf_from(get(S["qa_itm"], "/Scrap").text)})
check("SCRAP cannot be approved twice", one("SELECT COUNT(*) FROM asset_scrap_event WHERE scrap_request_id=%d AND event_type LIKE '%%APPROV%%'" % sid) in ("1", "0"))

# ---------- 5. Routing variants ----------
rid, r = create("qa_dm", {"request_target_type": "SELF"})
check("ROUTE dept manager own request skips dept step -> IT", status(rid) == "PENDING_IT_APPROVAL", status(rid))
r = act("qa_dm", "approveIt", rid, {"approved_quantity[1]": "1"}); check("ROUTE dept manager cannot IT-approve own", r.status_code == 403)
rid, r = create("qa_itm", {"request_target_type": "SELF"})
check("ROUTE IT manager own request (documented: straight to Issuer)", status(rid) == "APPROVED", status(rid))
before = latest_req("qa_emp")
rid, r = create("qa_emp", {"request_target_type": "USER", "requested_for": "9007"})
check("ROUTE employee cannot request for another employee", latest_req("qa_emp") == before)
rid, r = create("qa_emp", {"request_target_type": "DEPARTMENT", "requested_department": "D010"})
check("ROUTE employee cannot request for a department", latest_req("qa_emp") == before)
before = latest_req("qa_dm")
rid, r = create("qa_dm", {"request_target_type": "DEPARTMENT", "requested_department": "D001"})
check("ROUTE dept manager cannot request for another department", latest_req("qa_dm") == before)
before = latest_req("qa_emp")
r = post(S["qa_emp"], "/Request/store", {"csrf_token": tok("qa_emp", "/Request/create"), "business_reason": "x", "request_target_type": "SELF", "category[]": [cat], "quantity[]": ["9"]})
check("ROUTE max 8 assets per request enforced", latest_req("qa_emp") == before)
rid, r = create("qa_iss", {"request_target_type": "SELF"})
act("qa_dm", "approveDepartment", rid)
it = int(one("SELECT request_item_id FROM asset_request_item WHERE request_id=%d" % rid))
act("qa_itm", "approveIt", rid, {"approved_quantity[%d]" % it: "1"})
a = one("SELECT assetNum FROM assetdata WHERE assetStat='IN_STOCK' AND assetKat='%s' ORDER BY assetNum LIMIT 1" % cat)
act("qa_iss", "issue", rid, {"item_assets[%d][]" % it: a})
check("ROUTE issuer cannot issue to their own request", status(rid) == "APPROVED", status(rid))

# ---------- 6. Reject / edit rules ----------
rid, r = create("qa_emp", {"request_target_type": "SELF"})
r = act("qa_dm", "reject", rid, {"reason": ""}); check("REJ rejection requires a reason", status(rid) == "PENDING_DEPARTMENT_APPROVAL")
r = act("qa_dm", "reject", rid, {"reason": "QA reject"}); check("REJ dept manager rejects -> REJECTED", status(rid) == "REJECTED")
r = act("qa_dm", "approveDepartment", rid); check("REJ rejected request cannot be approved", status(rid) == "REJECTED")
r = get(S["qa_emp"], "/Request/edit/%d" % rid); check("REJ rejected request cannot be edited", r.status_code == 403, r.status_code)

# ---------- 7. Session / auth hardening ----------
s = requests.Session(); r = post(s, "/Request/store", {"business_reason": "x"})
check("AUTH anonymous POST redirected to login", r.status_code == 302)
check("AUTH inactive account cannot log in", get(session_for("qa_inactive"), "/Request").status_code == 302)
q("UPDATE users SET active=0 WHERE username='qa_iss'")
r = get(S["qa_iss"], "/Asset/assign"); check("AUTH deactivated user loses access mid-session", r.status_code in (302, 403), r.status_code)
q("UPDATE users SET active=1 WHERE username='qa_iss'")

fails = [r for r in results if not r[1]]
print("\n%d checks, %d passed, %d failed" % (len(results), len(results) - len(fails), len(fails)))
for f in fails: print("  FAIL:", f[0], f[2])
sys.exit(1 if fails else 0)
