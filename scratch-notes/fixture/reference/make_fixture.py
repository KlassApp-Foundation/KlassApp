"""Builds the made-up class used for parity tests. Names and numbers are invented; KLS numbers start with KLS9 so they cannot be real."""
import json, random, csv, os, sys
sys.path.insert(0, os.path.dirname(__file__))
from calc_reference import compute_set, set_is_active, division
random.seed(20261115)
SUBJECTS = ["English", "Mathematics", "Science", "Social Studies"]
SETS = ["Beginning of Term", "Mid Term"]
N = 76
learners = []
for i in range(1, N + 1):
    ability = random.gauss(58, 24)
    row = {"kls": f"KLS9{i:06d}", "name": f"PUPIL {i:03d}", "marks": []}
    for s in range(len(SETS)):
        shift = 5 * s
        marks = []
        for j in range(4):
            m = int(max(0, min(100, round(random.gauss(ability + shift - (8 if j == 2 else 0), 9)))))
            marks.append(m)
        row["marks"].append(marks)
    learners.append(row)
learners[75]["marks"][0][1] = None; learners[75]["marks"][0][2] = None      # two missing marks, first set
learners[9]["marks"][1][2] = "ABS"                                          # absent, second set
learners[32]["marks"][0][0] = "ABS"                                         # absent, first set
learners[40]["marks"][1] = list(learners[41]["marks"][1])                   # two learners with identical marks (a tie)
expected = []
per_set = []
for s in range(len(SETS)):
    cm = [l["marks"][s] for l in learners]
    assert set_is_active(cm)
    per_set.append(compute_set(cm, "aggregate"))
for i, l in enumerate(learners):
    expected.append({"kls": l["kls"], "sets": [per_set[s][i] for s in range(len(SETS))]})
summary = {SETS[s]: {d: sum(1 for o in per_set[s] if o["division"] == d) for d in ["1", "2", "3", "4", "U"]} for s in range(len(SETS))}
fx = {"school": "Sample Junior School", "class": "Primary Five", "term": "Third Term · 2026", "subjects": SUBJECTS, "sets": SETS,
      "rank_by": "aggregate", "rules": "see reference/calc_reference.py",
      "learners": learners, "expected": expected, "division_counts": summary,
      "notes": ["All names, numbers and KLS numbers are invented.", "null = missing mark; 'ABS' = absent. Both count as 0 (F9, 9 points).",
                "PUPIL 041 and PUPIL 042 have identical Mid Term marks, so they share a place."]}
here = os.path.dirname(os.path.dirname(__file__))
json.dump(fx, open(os.path.join(here, "fixtures", "p5-synthetic-class.json"), "w"), indent=1, ensure_ascii=False)
for s, sname in enumerate(SETS):
    for j, subj in enumerate(SUBJECTS):
        with open(os.path.join(here, "fixtures", "importer-csv", f"{sname.replace(' ', '-')}__{subj.replace(' ', '-')}.csv"), "w", newline="") as f:
            w = csv.writer(f); w.writerow(["KLS number", "student_name", "Mark"])
            for l in learners:
                m = l["marks"][s][j]
                w.writerow([l["kls"], l["name"], "" if m is None else m])
print("division counts:", summary)
