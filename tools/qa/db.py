import subprocess, json
def q(sql):
    out = subprocess.run(["sudo", "mariadb", "-N", "-B", "central_asset_db", "-e", sql], capture_output=True, text=True)
    if out.returncode: raise RuntimeError(out.stderr)
    return [l.split("\t") for l in out.stdout.strip().splitlines() if l]
def one(sql):
    r = q(sql); v = r[0][0] if r else None; return None if v == "NULL" else v
