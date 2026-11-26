#!/usr/bin/env python3
"""Match exact composer.lock versions against a local GitHub Advisory Database checkout."""

import argparse
import hashlib
import json
import subprocess
from pathlib import Path

from packaging.version import Version

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("lockfile", type=Path)
parser.add_argument("database", type=Path)
args = parser.parse_args()

def git(*arguments):
    return subprocess.check_output(["git", "-C", str(args.database), *arguments], text=True).strip()

if git("status", "--porcelain"):
    raise SystemExit("Use a clean checkout of the advisory database.")
source = git("rev-parse", "HEAD")
source_time = git("log", "-1", "--format=%cI")
lock = json.loads(args.lockfile.read_text())
packages = {}
for scope, key in [("runtime", "packages"), ("development", "packages-dev")]:
    for package in lock.get(key, []):
        packages[package["name"]] = (Version(package["version"]), package["version"], scope)

def in_range(version, events):
    lower = None
    for event in events:
        kind, value = next(iter(event.items()))
        boundary = Version(value)
        if kind == "introduced":
            lower = boundary
            continue
        if lower is None or kind not in {"fixed", "last_affected", "limit"}:
            raise ValueError(f"Unsupported range event: {event}")
        if lower <= version and (version < boundary or (kind == "last_affected" and version == boundary)):
            return True
        lower = None
    return lower is not None and lower <= version

findings = {}
matched_records = 0
reviewed = args.database / "advisories/github-reviewed"
if not reviewed.is_dir() or not packages:
    raise SystemExit("Expected a GitHub Advisory Database checkout and a populated Composer lockfile.")
for path in sorted(reviewed.rglob("*.json")):
    advisory = json.loads(path.read_text())
    if advisory.get("withdrawn"):
        continue
    for affected in advisory.get("affected", []):
        package = affected["package"]
        name = package["name"].lower()
        if package["ecosystem"] != "Packagist" or name not in packages:
            continue
        matched_records += 1
        version, locked, scope = packages[name]
        ranges = affected.get("ranges", [])
        if any(record["type"] != "ECOSYSTEM" for record in ranges):
            raise ValueError(f"Unsupported Packagist range in {advisory['id']}")
        if not ranges and not affected.get("versions"):
            raise ValueError(f"Missing Packagist version data in {advisory['id']}")
        if version not in {Version(value) for value in affected.get("versions", [])} and not any(
            in_range(version, record["events"]) for record in ranges
        ):
            continue
        advisory_id = advisory["id"]
        findings[(advisory_id, name)] = {
            "advisory": advisory_id,
            "package": name,
            "version": locked,
            "scope": scope,
            "severity": advisory.get("database_specific", {}).get("severity", "UNKNOWN"),
            "summary": advisory.get("summary"),
            "matched_entry": affected,
            "url": f"https://github.com/advisories/{advisory_id}",
        }
if not matched_records:
    raise SystemExit("No advisory records matched package names, check the source and lockfile.")
print(json.dumps({
    "source": "GitHub Advisory Database, reviewed Packagist advisories",
    "source_commit": source,
    "source_commit_time": source_time,
    "lock_sha256": hashlib.sha256(args.lockfile.read_bytes()).hexdigest(),
    "packages": len(packages),
    "affected_records_examined": matched_records,
    "findings": list(findings.values()),
}, ensure_ascii=False, indent=2))
raise SystemExit(bool(findings))
