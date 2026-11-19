"""QA: master-data deletes cannot be triggered by a link/GET or without the token."""
import sys
from lib import *
from db import q, one
res = []
def check(n, c, d=""):
    res.append((n, bool(c))); print(("PASS " if c else "FAIL ") + n + ("" if c else "  -- " + str(d)))

adm, iss, emp = session_for("qa_adm"), session_for("qa_iss"), session_for("qa_emp")
q("INSERT INTO location(location,description) VALUES ('QALOC','QA location') ON DUPLICATE KEY UPDATE description='QA location'")
q("INSERT INTO unit(unit,description) VALUES ('QAU','QA unit') ON DUPLICATE KEY UPDATE description='QA unit'") if one("SHOW COLUMNS FROM unit LIKE 'description'") else q("INSERT IGNORE INTO unit(unit) VALUES ('QAU')")
exists = lambda sql: one(sql) is not None

page = get(adm, "/Location").text
token = re.search(r'name="csrf-master-delete" content="([^"]+)"', page)
check("token meta rendered on admin pages", token)
token = token.group(1) if token else ""

for path, sql in [("/Location/delete/QALOC", "SELECT 1 FROM location WHERE location='QALOC'"),
                  ("/Unit/hapusData/QAU", "SELECT 1 FROM unit WHERE unit='QAU'"),
                  ("/Employee/hapusData/9007", "SELECT 1 FROM employee WHERE empId=9007"),
                  ("/Department/hapusData/D001", "SELECT 1 FROM department WHERE deptId='D001'")]:
    r = get(adm, path); check("GET %s blocked (405), record kept" % path, r.status_code == 405 and exists(sql), r.status_code)
    r = post(adm, path, {}); check("POST %s without token blocked (419)" % path, r.status_code == 419 and exists(sql), r.status_code)
    r = post(adm, path, {"csrf_token": "forged"}); check("POST %s with forged token blocked" % path, r.status_code == 419 and exists(sql), r.status_code)
    r = post(emp, path, {"csrf_token": token}); check("POST %s by employee blocked" % path, r.status_code in (302, 403) and exists(sql), r.status_code)

r = post(adm, "/Location/delete/QALOC", {"csrf_token": token})
check("admin POST with valid token deletes (Location)", not exists("SELECT 1 FROM location WHERE location='QALOC'"), r.status_code)
r = post(adm, "/Unit/hapusData/QAU", {"csrf_token": token})
check("admin POST with valid token deletes (Unit)", not exists("SELECT 1 FROM unit WHERE unit='QAU'"), r.status_code)
f = [x for x in res if not x[1]]
print("\n%d checks, %d passed, %d failed" % (len(res), len(res) - len(f), len(f)))
sys.exit(1 if f else 0)
