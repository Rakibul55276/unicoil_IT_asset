import re, requests
BASE = "http://127.0.0.1:8080"
PW = "Test@12345"

def session_for(user, pw=PW):
    s = requests.Session()
    r = s.get(BASE + "/")
    tok = re.search(r'name="csrf_token" value="([^"]+)"', r.text).group(1)
    r = s.post(BASE + "/Home/members", data={"csrf_token": tok, "username": user, "password": pw, "company_code": ""}, allow_redirects=True)
    s.logged_in = "it_asset_management" in s.cookies and "Sign out" in r.text or "logout" in r.text.lower()
    return s

def csrf_from(html, name="csrf_token"):
    m = re.search(r'name="%s"\s+value="([^"]+)"' % name, html) or re.search(r'value="([^"]+)"\s+name="%s"' % name, html)
    return m.group(1) if m else None

def get(s, path, **kw):
    return s.get(BASE + path, allow_redirects=False, **kw)

def post(s, path, data=None, **kw):
    return s.post(BASE + path, data=data or {}, allow_redirects=False, **kw)
