#!/usr/bin/env python3
"""Validate case coverage and links to authentic browser PNGs."""

import csv
import re
import struct
import sys
import zlib
from pathlib import Path

root = Path(sys.argv[1]).resolve()
cases = {f"TC-{number:02}" for number in range(1, 29)}
statuses = {"PASS", "FAIL", "BLOCKED", "NOT_RUN", "N_A"}

def load(name):
    with (root / name).open(newline="", encoding="utf-8") as handle:
        return list(csv.DictReader(handle))

def reachable(relative):
    path = (root / relative).resolve()
    assert path.is_relative_to(root) and path.is_file(), relative
    return path

def check_png(path):
    payload = path.read_bytes()
    assert payload.startswith(b"\x89PNG\r\n\x1a\n"), path
    position = 8
    kinds = []
    while position < len(payload):
        size = struct.unpack_from(">I", payload, position)[0]
        chunk = payload[position + 4:position + 8 + size]
        expected = struct.unpack_from(">I", payload, position + 8 + size)[0]
        assert zlib.crc32(chunk) == expected, path
        kinds.append(chunk[:4])
        if chunk[:4] == b"IHDR":
            width, height = struct.unpack_from(">II", chunk, 4)
            assert width >= 1024 and height >= 600, path
        position += 12 + size
    assert position == len(payload) and kinds[0] == b"IHDR" and kinds[-1] == b"IEND", path
    assert b"IDAT" in kinds, path

results = load("TEST_CASE_RESULTS.csv")
assert len(results) == 28 and {row["test_case_id"] for row in results} == cases
screenshots = load("SCREENSHOT_EVIDENCE.csv")
by_id = {row["evidence_id"]: row for row in screenshots}
assert len(by_id) == len(screenshots)
files = set()
for row in screenshots:
    case_id = row["test_case_id"]
    assert case_id in cases and row["status"] in {"PASS", "FAIL"}
    assert re.fullmatch(r"[0-9a-f]{40}", row["run_sha"]), row
    image = reachable(row["screenshot_path"])
    assert image.name.startswith(case_id + "_") and image.suffix == ".png", image
    assert image not in files, image
    files.add(image)
    check_png(image)
    reachable(row["assertion_reference"].split("#")[0])

referenced = set()
for row in results:
    assert row["status"] in statuses, row
    for reference in row["evidence_path"].split(";"):
        reachable(reference.strip())
    screen_ids = row["screenshot_evidence_ids"]
    if screen_ids not in {"NOT_APPLICABLE", "NOT_RUN", "BLOCKED"}:
        for evidence_id in screen_ids.split(";"):
            assert by_id[evidence_id]["test_case_id"] == row["test_case_id"], evidence_id
            referenced.add(evidence_id)
assert referenced == set(by_id)
assert files == {path.resolve() for path in (root / "screenshots").glob("*.png")}
counts = {status: sum(row["status"] == status for row in results) for status in sorted(statuses)}
print(f"Validated {len(results)} unique test cases and {len(files)} intact PNGs. {counts}")
