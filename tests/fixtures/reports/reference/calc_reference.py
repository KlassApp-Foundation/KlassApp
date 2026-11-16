"""Reference implementation of the Progressive report calculation.
Independent of KlassApp's code on purpose: use it to produce expected values and to test KlassApp against.
Defaults are the values KlassApp uses today for the primary standard; a school must confirm them."""
BANDS = [(95, 100, "D1", 1), (90, 94, "D2", 2), (75, 89, "C3", 3), (65, 74, "C4", 4), (60, 64, "C5", 5),
         (50, 59, "C6", 6), (45, 49, "P7", 7), (40, 44, "P8", 8), (0, 39, "F9", 9)]
DIVISIONS = [(0, 12, "1"), (13, 24, "2"), (25, 28, "3"), (29, 32, "4"), (33, None, "U")]

def band(mark):
    """Grade and points for a mark: the first band whose lower bound the mark reaches."""
    for lo, hi, grade, pts in BANDS:
        if mark >= lo:
            return grade, pts

def division(aggregate):
    for lo, hi, name in DIVISIONS:
        if aggregate >= lo and (hi is None or aggregate <= hi):
            return name

def ordinal(n):
    return f"{n}{'th' if 11 <= n % 100 <= 13 else {1: 'st', 2: 'nd', 3: 'rd'}.get(n % 10, 'th')}"

def score(raw):
    """A mark is a number. None (missing) and 'ABS' (absent) both count as 0."""
    return raw if isinstance(raw, (int, float)) else 0

def compute_set(class_marks, rank_by="aggregate"):
    """class_marks: one list of subject marks per learner, for ONE exam set.
    Returns per learner: grades, points, total, aggregate, division, position.
    Rules: missing and ABS = 0 (F9, 9 points); aggregate = sum of points over the subjects;
    rank_by 'aggregate' = lowest first, 'total' = highest first; equal values share a place and the next place is skipped."""
    out = []
    for marks in class_marks:
        g = [band(score(m)) for m in marks]
        out.append({"grades": [x[0] for x in g], "points": [x[1] for x in g],
                    "total": sum(score(m) for m in marks), "aggregate": sum(x[1] for x in g)})
    for o in out:
        o["division"] = division(o["aggregate"])
    for o in out:
        if rank_by == "aggregate":
            o["position"] = 1 + sum(1 for p in out if p["aggregate"] < o["aggregate"])
        else:
            o["position"] = 1 + sum(1 for p in out if p["total"] > o["total"])
    return out

def set_is_active(class_marks):
    """A set counts as taken only if at least one learner has a numeric mark in it."""
    return any(isinstance(m, (int, float)) for row in class_marks for m in row)
