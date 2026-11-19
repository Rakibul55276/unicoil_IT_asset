"""QA: backend role/access matrix for every read route.
Expected access derived from the navigation permissions in header_a.php and
docs/WORKFLOWS_AND_ROLES.md. A route counts as ALLOWED when it returns 200 with
content; DENIED when it returns 302 to login, 403, 404 or a short error body."""
import json, sys
from lib import *

ROLES = ["anon", "qa_emp", "qa_hr", "qa_dm", "qa_itm", "qa_iss", "qa_adm"]
ALL = set(ROLES) - {"anon"}
OPS = {"qa_iss", "qa_adm"}                  # Issuer + Admin
REPORT = {"qa_iss", "qa_itm", "qa_adm"}     # canViewAssetReports / audit
ADMIN = {"qa_adm"}

ISSUE, RET, ASSET_ASSIGNED, ASSET_STOCK, EMP, KAT = 47, 19, "AC0001", "AC0002", 1622, "ACCESS"
ROUTES = [
    # path, expected allowed set
    ("/Request", ALL), ("/Request/create", ALL),
    ("/Request/history", OPS), ("/Request/exportHistoryExcel", OPS),
    ("/Asset/myAssets", ALL), ("/Asset/myAssetHistory", ALL),
    ("/Asset/active", REPORT), ("/Asset/inactive", REPORT), ("/Asset/returnHistory", REPORT),
    ("/Asset/auditReport", REPORT), ("/Asset/exportAuditExcel", REPORT), ("/Asset/exportAuditPdf", REPORT),
    ("/Asset/warrantyTimeline", REPORT),
    ("/Asset/archive", REPORT), ("/Asset/exportArchiveExcel", REPORT), ("/Asset/exportArchivePdf", REPORT),
    ("/Asset/disposed", OPS),
    ("/Asset/printHistory/%s" % ASSET_ASSIGNED, REPORT),
    ("/Asset/printLabel/%s" % ASSET_STOCK, REPORT),
    ("/Asset/printAssignment/%d" % ISSUE, REPORT),
    ("/Asset/downloadAssignmentPdf/%d" % ISSUE, REPORT),
    ("/Asset/printReturn/%d" % RET, REPORT),
    ("/Asset/downloadReturnPdf/%d" % RET, REPORT),
    ("/Asset/details/%s" % ASSET_ASSIGNED, REPORT),
    ("/Asset/getAssetById/%s" % ASSET_ASSIGNED, OPS),
    ("/Asset/receive", OPS), ("/Asset/assign", OPS), ("/Asset/returns", OPS),
    ("/Asset/returnRequests", OPS), ("/Asset/searchAvailableAssets?q=A", OPS),
    ("/Asset/searchOpenIssues?q=a", OPS), ("/Asset/assignmentEmployee/%d" % EMP, OPS),
    ("/Asset/departmentHeads/D010", OPS), ("/Asset/edit/%s" % ASSET_STOCK, OPS),
    ("/Asset/exportReportExcel/assigned", REPORT), ("/Asset/exportReportExcel/instock", REPORT),
    ("/Asset/exportReportExcel/my-assets", ALL),
    ("/Asset/getAllDepartments", set()),  # removed: was a broken endpoint (HTTP 500)
    ("/Asset/exportReportExcel/returns", REPORT), ("/Asset/exportReportExcel/expiry", REPORT),
    ("/Asset/exportReportExcel/my-history", ALL),
    ("/AssetDirectory", REPORT), ("/AssetDirectory/employee/%d" % EMP, REPORT),
    ("/AssetDirectory/currentAssigneeItems/USER/%d" % EMP, REPORT),
    ("/AssetDirectory/exportExcel", REPORT),
    ("/Accessory", OPS),
    ("/Scrap", {"qa_iss", "qa_itm", "qa_adm"}), ("/Scrap/create", OPS),
    ("/Employee", {"qa_hr", "qa_iss", "qa_adm"}), ("/Employee/list", {"qa_hr", "qa_iss", "qa_adm"}),
    ("/Employee/getById/%d" % EMP, {"qa_hr", "qa_iss", "qa_adm"}),
    ("/Employee/details/%d" % EMP, {"qa_hr", "qa_iss", "qa_adm"}),
    ("/Department", {"qa_itm", "qa_iss", "qa_adm"}), ("/Assetkat", {"qa_itm", "qa_iss", "qa_adm"}),
    ("/Location", {"qa_itm", "qa_iss", "qa_adm"}), ("/Unit", {"qa_itm", "qa_iss", "qa_adm"}),
    ("/Vendor", {"qa_itm", "qa_iss", "qa_adm"}),
    ("/User", ADMIN), ("/User/create", ADMIN), ("/User/edit/qa_emp", ADMIN),
    ("/User/profile", ALL),
    ("/Platform", set()), ("/Subscription", set()),
    ("/About", set()), ("/Mahasiswa", set()), ("/Member", set()),
    # direct file / source exposure (should never be served)
    ("/config.php", set()), ("/app/config/.private/r8m2q6x4.dat", set()),
    ("/database/backups/central_asset_db-2026-11-09.sql", set()), ("/.git/config", set()),
]

def allowed(r):
    if r.status_code in (301, 302, 303, 401, 403, 404, 405, 419, 422):
        return False
    body = r.text.strip()
    if r.status_code >= 500:
        return "ERROR"
    deny_words = ["Only an", "do not have permission", "Not Found", "permission", "Forbidden", "disabled"]
    if len(body) < 300 and any(w.lower() in body.lower() for w in deny_words):
        return False
    return True

def main():
    sessions = {u: (requests.Session() if u == "anon" else session_for(u)) for u in ROLES}
    leaks, blocked, errors, rows = [], [], [], []
    for path, exp in ROUTES:
        line = {"route": path}
        for u in ROLES:
            r = get(sessions[u], path)
            a = allowed(r)
            line[u] = {True: "ALLOW", False: "deny", "ERROR": "ERR%d" % r.status_code}[a]
            if a == "ERROR":
                errors.append((path, u, r.status_code))
            elif a and u not in exp:
                leaks.append((path, u, r.status_code))
            elif not a and u in exp:
                blocked.append((path, u, r.status_code))
        rows.append(line)
    json.dump({"rows": rows, "leaks": leaks, "blocked": blocked, "errors": errors},
              open("access_matrix_result.json", "w"), indent=1)
    w = 44
    print("route".ljust(w) + "".join(u.replace("qa_", "")[:6].ljust(7) for u in ROLES))
    for l in rows:
        print(l["route"][:w - 1].ljust(w) + "".join(l[u].ljust(7) for u in ROLES))
    print("\nLEAKS (allowed but should be denied): %d" % len(leaks))
    for x in leaks: print("  LEAK", x)
    print("OVER-BLOCKED (denied but expected allowed): %d" % len(blocked))
    for x in blocked: print("  BLOCK", x)
    print("SERVER ERRORS: %d" % len(errors))
    for x in errors: print("  ERR", x)
    return 1 if leaks or errors else 0

if __name__ == "__main__":
    sys.exit(main())
