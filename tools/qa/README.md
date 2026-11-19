# QA test suite (IT Asset Management)

Run against a **test copy** of the database only — the scripts create QA users
(`qa_emp`, `qa_dm`, `qa_itm`, `qa_iss`, `qa_hr`, `qa_adm`, `qa_emp2`, `qa_dm2`,
`qa_inactive`, password `Test@12345`) and run real approve/issue/return/scrap
transactions.

| Suite | What it proves |
| --- | --- |
| `unit_tests.php` | Core classes (feature switches, SaaS crypto, router rules) and that every controller action has a server-side guard. `php unit_tests.php <app root>` |
| `test_access_matrix.py` | 70 routes x 7 roles (anonymous + 6 roles): nobody reaches a page or export outside their role; private files are never served. |
| `test_workflows.py` | 71 checks: full request -> dept approval -> IT approval -> issue -> receiver acceptance -> return request -> physical return -> scrap approval, with a bypass attempt at every step (self-approval, skipping steps, wrong approver, double issue, wrong asset, IDOR on PDFs, CSRF, GET on POST actions, deactivated users). |
| `test_csrf_deletes.py` | Master-data deletes cannot be triggered by a link or without the token. |

Setup used: PHP 8.3 built-in server on port 8080 with a router that mirrors
`.htaccess`, MariaDB 10.11, database restored from
`database/backups/central_asset_db-2026-11-09.sql`, then `seed.sql`
(`reset_db.sh` does both). Python 3 + `requests`.
