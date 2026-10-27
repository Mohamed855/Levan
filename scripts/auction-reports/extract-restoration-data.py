"""Read selected auction records from a SQL backup without executing its SQL."""

import gzip
import hashlib
import json
import re
from pathlib import Path

root = Path(__file__).resolve().parents[2]
source = root / "storage/app/private/backups/etmam_levan_test-20261026-125042.sql.gz"
tables = {name: [] for name in ["auctions", "auction_properties", "auction_attachments", "assignments", "properties", "property_boundaries", "property_owners", "property_units", "attachments"]}


def values(sql):
    position = 0
    while position < len(sql):
        if sql[position] != "(":
            position += 1
            continue
        row = []
        position += 1
        while True:
            while sql[position].isspace():
                position += 1
            if sql[position] == "'":
                position += 1
                text = []
                while position < len(sql):
                    character = sql[position]
                    position += 1
                    if character == "\\":
                        character = sql[position]
                        position += 1
                        text.append({"n":"\n","r":"\r","t":"\t","0":"\0","Z":"\x1a"}.get(character,character))
                    elif character == "'":
                        if position < len(sql) and sql[position] == "'":
                            text.append("'")
                            position += 1
                        else:
                            break
                    else:
                        text.append(character)
                row.append("".join(text))
            else:
                end = position
                while sql[end] not in ",)":
                    end += 1
                value = sql[position:end].strip()
                row.append(None if value == "NULL" else value)
                position = end
            while sql[position].isspace():
                position += 1
            separator = sql[position]
            position += 1
            if separator == ")":
                yield row
                break
            if separator != ",":
                raise ValueError("Invalid SQL value separator")


with gzip.open(source,"rt",encoding="utf-8") as stream:
    for line in stream:
        match = re.match(r"INSERT INTO `([^`]+)` \((.*?)\) VALUES (.*);\s*$",line)
        if match and match[1] in tables:
            columns = re.findall(r"`([^`]+)`",match[2])
            for row in values(match[3]):
                if len(columns) != len(row):
                    raise ValueError("SQL column count mismatch")
                tables[match[1]].append(dict(zip(columns,row)))

tables["auctions"] = [r for r in tables["auctions"] if r["id"] in ["5","6"]]
if len(tables["auctions"]) != 2 or {r["name"] for r in tables["auctions"]} != {"مزاد جوار الحرم","واجهة عسير"}:
    raise ValueError("The backup does not contain the approved original auctions")
for table in ["auction_properties","auction_attachments"]:
    tables[table] = [r for r in tables[table] if r["auction_id"] in ["5","6"]]
assignment_ids = {r["assignment_id"] for r in tables["auctions"]}
property_ids = {r["property_id"] for r in tables["auction_properties"]}
tables["assignments"] = [r for r in tables["assignments"] if r["id"] in assignment_ids]
tables["properties"] = [r for r in tables["properties"] if r["id"] in property_ids]
for table in ["property_boundaries","property_owners","property_units"]:
    tables[table] = [r for r in tables[table] if r["property_id"] in property_ids]
tables["attachments"] = [r for r in tables["attachments"] if r.get("attachable_id") in property_ids and r.get("attachable_type","").endswith("Property")]
target = root / "storage/app/private/backups/auction-restoration-source.json"
target.write_text(json.dumps({"source_sha256":hashlib.sha256(source.read_bytes()).hexdigest(),"tables":tables},ensure_ascii=False),encoding="utf-8")
print(json.dumps({table:len(rows) for table,rows in tables.items()}))
