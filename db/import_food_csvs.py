#!/usr/bin/env python3
"""
FuelWise — Food Nutrition CSV Importer
========================================
Reads the 5 Kaggle Nutrition Dataset CSV files, deduplicates,
classifies each food by condition-safety rules, and generates
a SQL import file for the food_nutrition table.

Classification rules (sourced from):
  - Hypertension (DASH diet):    AHA guidelines — sodium, potassium thresholds
  - Diabetes/Prediabetes:         ADA + Atkinson et al. 2021 (GI inference from names)
  - IBS (FODMAP):                 Bertin et al. 2024, Monash U FODMAP food lists
  - Weight Management:            Calories/protein/fiber ratios (WHO 2020)
  - Dietary tags:                 Name-based keyword matching

Output: db/food_nutrition_import.sql
"""

import pandas as pd
import os
import re
import sys

# ============================================================
# Load all CSVs
# ============================================================
CSV_FILES = [
    'FOOD-DATA-GROUP1.csv',
    'FOOD-DATA-GROUP2.csv',
    'FOOD-DATA-GROUP3.csv',
    'FOOD-DATA-GROUP4.csv',
    'FOOD-DATA-GROUP5.csv',
]

script_dir = os.path.dirname(os.path.abspath(__file__))
csv_dir = os.path.join(script_dir, 'datasets')  # CSVs sit in db/datasets/

dfs = []
for fname in CSV_FILES:
    fpath = os.path.join(csv_dir, fname)
    if not os.path.exists(fpath):
        print(f"⚠ Warning: {fname} not found in {csv_dir} — skipping")
        continue
    df = pd.read_csv(fpath)
    print(f"✓ Loaded {fname}: {len(df)} foods")
    dfs.append(df)

if not dfs:
    print("ERROR: No CSV files found. Place the 5 FOOD-DATA-GROUP*.csv files next to this script.")
    sys.exit(1)

# Combine and deduplicate
combined = pd.concat(dfs, ignore_index=True)
print(f"\nTotal foods before dedup: {len(combined)}")
combined = combined.drop_duplicates(subset=['food'], keep='first').reset_index(drop=True)
print(f"Total foods after dedup: {len(combined)}")

# ============================================================
# Classification rules
# ============================================================

# FODMAP food lists (sourced from Bertin et al. 2024 + Monash FODMAP app lists)
HIGH_FODMAP_KEYWORDS = [
    'onion', 'garlic', 'leek', 'shallot', 'asparagus',
    'apple', 'pear', 'mango', 'cherry', 'fig', 'watermelon', 'peach', 'plum', 'nectarine',
    'mushroom', 'cauliflower', 'artichoke',
    'honey', 'agave',
    'wheat', 'rye', 'barley', 'semolina', 'spelt',
    'chickpea', 'bean', 'lentil', 'soybean', 'kidney bean', 'black bean', 'pinto',
    'milk', 'yogurt', 'cream', 'ice cream', 'condensed milk',
    'cashew', 'pistachio',
]

LOW_FODMAP_KEYWORDS = [
    'rice', 'oat', 'quinoa', 'corn', 'polenta',
    'carrot', 'cucumber', 'tomato', 'spinach', 'lettuce', 'zucchini', 'bell pepper', 'eggplant',
    'banana', 'blueberry', 'strawberry', 'grape', 'orange', 'pineapple', 'kiwi', 'raspberry',
    'chicken', 'beef', 'turkey', 'pork', 'lamb', 'fish', 'salmon', 'tuna', 'shrimp', 'prawn',
    'egg', 'tofu',
    'lactose free', 'lactose-free', 'almond milk', 'rice milk', 'coconut milk',
    'hard cheese', 'cheddar', 'parmesan', 'swiss cheese', 'feta',
    'butter', 'olive oil',
    'walnut', 'peanut', 'macadamia',
]

# High-GI food keywords (Atkinson et al. 2021 classifications)
HIGH_GI_KEYWORDS = [
    'white rice', 'white bread', 'bagel', 'cornflakes', 'rice cereal',
    'potato', 'fried potato', 'mashed potato', 'french fries',
    'sugar', 'candy', 'jelly', 'gummies', 'soda', 'cola', 'syrup',
    'watermelon', 'pineapple',
    'pretzel', 'cracker', 'puffed rice',
    'instant oat',
]

LOW_GI_KEYWORDS = [
    'oat', 'rolled oat', 'barley', 'quinoa', 'brown rice', 'bulgur',
    'lentil', 'chickpea', 'bean',
    'apple', 'pear', 'orange', 'peach', 'plum', 'cherry', 'strawberry',
    'carrot', 'broccoli', 'spinach', 'cauliflower',
    'milk', 'yogurt',
    'peanut', 'almond', 'walnut',
    'whole wheat', 'whole grain', 'multigrain',
]

# Non-vegetarian indicators
MEAT_KEYWORDS = [
    'beef', 'pork', 'chicken', 'turkey', 'lamb', 'mutton', 'duck', 'goose',
    'bacon', 'ham', 'sausage', 'salami', 'pepperoni', 'hotdog', 'hot dog',
    'meat', 'steak', 'veal', 'venison',
    'fish', 'salmon', 'tuna', 'cod', 'tilapia', 'mackerel', 'herring', 'sardine', 'anchovy',
    'shrimp', 'prawn', 'crab', 'lobster', 'oyster', 'mussel', 'clam', 'squid', 'octopus',
    'liver', 'kidney', 'tripe', 'gelatin',
]

# Non-vegan (but vegetarian) indicators
DAIRY_EGG_KEYWORDS = [
    'milk', 'cheese', 'cream', 'butter', 'yogurt', 'yoghurt',
    'egg', 'omelet', 'custard', 'pudding', 'ice cream', 'mayonnaise',
    'whey', 'casein', 'lactose', 'paneer', 'ghee',
]

# Non-halal indicators
NON_HALAL_KEYWORDS = [
    'pork', 'bacon', 'ham', 'lard', 'pepperoni', 'prosciutto',
    'wine', 'beer', 'liquor', 'vodka', 'rum', 'whiskey', 'champagne', 'cognac', 'brandy',
    'alcohol', 'cooking wine', 'sake',
    'gelatin',  # unless halal-certified
]

# Gluten indicators
GLUTEN_KEYWORDS = [
    'wheat', 'barley', 'rye', 'spelt', 'kamut', 'triticale', 'semolina',
    'bread', 'pasta', 'noodle', 'bagel', 'tortilla', 'wrap',
    'flour', 'cookie', 'cake', 'pastry', 'biscuit', 'muffin', 'donut', 'pretzel',
    'beer', 'couscous', 'bulgur', 'farro',
]

def name_matches_any(name, keywords):
    """Check if name contains any of the keywords (case-insensitive)."""
    n = name.lower()
    return any(kw in n for kw in keywords)


def classify_fodmap(name):
    n = name.lower()
    # High takes priority
    if name_matches_any(n, HIGH_FODMAP_KEYWORDS):
        return 'high'
    if name_matches_any(n, LOW_FODMAP_KEYWORDS):
        return 'low'
    return 'unknown'


def classify_gi(name, sugars, fiber):
    n = name.lower()
    if name_matches_any(n, HIGH_GI_KEYWORDS):
        return 'high'
    if name_matches_any(n, LOW_GI_KEYWORDS):
        return 'low'
    # Fallback: infer from nutrients
    # High sugars + low fiber = likely high GI
    if sugars > 15 and fiber < 2:
        return 'high'
    if fiber > 5 and sugars < 5:
        return 'low'
    return 'unknown'


def is_vegetarian(name):
    return 0 if name_matches_any(name, MEAT_KEYWORDS) else 1


def is_vegan(name):
    if not is_vegetarian(name):
        return 0
    return 0 if name_matches_any(name, DAIRY_EGG_KEYWORDS) else 1


def is_halal(name):
    return 0 if name_matches_any(name, NON_HALAL_KEYWORDS) else 1


def is_gluten_free(name):
    return 0 if name_matches_any(name, GLUTEN_KEYWORDS) else 1


def classify_diabetes_safe(name, sugars, fiber, carbs, gi_cat):
    """Safe if: low/no added sugars, decent fiber, not high-GI."""
    if gi_cat == 'high':
        return 0
    if sugars > 15:
        return 0
    if fiber < 1 and carbs > 30:  # high carb, no fiber
        return 0
    # Sweets / desserts
    if name_matches_any(name, ['cake', 'candy', 'cookie', 'ice cream', 'syrup', 'chocolate bar']):
        return 0
    return 1


def classify_prediabetes_safe(name, sugars, fiber, carbs, gi_cat):
    """Same rules as diabetes but slightly lenient on fiber."""
    if gi_cat == 'high':
        return 0
    if sugars > 20:
        return 0
    if name_matches_any(name, ['cake', 'candy', 'soda', 'syrup']):
        return 0
    return 1


def classify_hypertension_safe(sodium, potassium):
    """DASH diet: low sodium, high potassium."""
    # AHA: sodium < 140mg per serving is 'low-sodium'
    if sodium > 400:  # per serving — too high
        return 0
    if sodium > 200 and potassium < 100:
        return 0
    return 1


def classify_ibs_safe(fodmap_level):
    """Low-FODMAP foods only."""
    if fodmap_level == 'high':
        return 0
    if fodmap_level == 'low':
        return 1
    return 1  # Unknown — err on the safe side for inclusion (let recommendation engine filter further)


def classify_weight_loss_safe(calories, protein, fiber):
    """Low-cal, high-protein, high-fiber = weight loss friendly."""
    if calories > 400:
        return 0
    if calories < 100 and protein < 2:  # too low-cal, empty food
        return 1
    if protein > 5 or fiber > 3:
        return 1
    if calories > 300:
        return 0
    return 1


# ============================================================
# Process and generate SQL
# ============================================================
out_sql = os.path.join(script_dir, 'food_nutrition_import.sql')

with open(out_sql, 'w', encoding='utf-8') as f:
    f.write('-- ============================================================\n')
    f.write('-- FuelWise — Food Nutrition Data Import\n')
    f.write(f'-- Total foods: {len(combined)}\n')
    f.write('-- Source: Kaggle Food Nutrition Dataset\n')
    f.write('-- Classifications sourced from:\n')
    f.write('--   - Atkinson et al. (2021) — GI classifications\n')
    f.write('--   - Bertin et al. (2024) — FODMAP classifications\n')
    f.write('--   - AHA Guidelines — sodium/potassium thresholds (hypertension)\n')
    f.write('--   - WHO 2020 — calorie/fiber thresholds (weight management)\n')
    f.write('-- ============================================================\n\n')
    f.write('USE fuelwise;\n\n')
    f.write('-- Clear existing data (safe to re-run)\n')
    f.write('TRUNCATE TABLE food_nutrition;\n\n')

    # Build inserts in batches of 100 for MySQL performance
    BATCH = 100

    def esc(s):
        if s is None:
            return 'NULL'
        s = str(s).replace("\\", "\\\\").replace("'", "''")
        return f"'{s}'"

    def num(v, default=0):
        try:
            x = float(v)
            if pd.isna(x):
                return default
            return x
        except Exception:
            return default

    rows = []
    for _, row in combined.iterrows():
        name = str(row['food']).strip().title()[:199]

        caloric_value = num(row.get('Caloric Value'))
        fat = num(row.get('Fat'))
        sat_fats = num(row.get('Saturated Fats'))
        mono_fats = num(row.get('Monounsaturated Fats'))
        poly_fats = num(row.get('Polyunsaturated Fats'))
        carbs = num(row.get('Carbohydrates'))
        sugars = num(row.get('Sugars'))
        protein = num(row.get('Protein'))
        fiber = num(row.get('Dietary Fiber'))
        cholesterol = num(row.get('Cholesterol'))
        sodium = num(row.get('Sodium'))
        water = num(row.get('Water'))
        vit_a = num(row.get('Vitamin A'))
        vit_b12 = num(row.get('Vitamin B12'))
        vit_c = num(row.get('Vitamin C'))
        vit_d = num(row.get('Vitamin D'))
        vit_e = num(row.get('Vitamin E'))
        calcium = num(row.get('Calcium'))
        iron = num(row.get('Iron'))
        magnesium = num(row.get('Magnesium'))
        phosphorus = num(row.get('Phosphorus'))
        potassium = num(row.get('Potassium'))
        zinc = num(row.get('Zinc'))
        nutrition_density = num(row.get('Nutrition Density'))

        # Compute classification flags
        fodmap = classify_fodmap(name)
        gi = classify_gi(name, sugars, fiber)
        veg = is_vegetarian(name)
        vegan = is_vegan(name) if veg else 0
        halal = is_halal(name)
        gf = is_gluten_free(name)

        diab_safe = classify_diabetes_safe(name, sugars, fiber, carbs, gi) if carbs > 0 or sugars > 0 else 1
        pre_safe = classify_prediabetes_safe(name, sugars, fiber, carbs, gi) if carbs > 0 or sugars > 0 else 1
        hyp_safe = classify_hypertension_safe(sodium, potassium)
        ibs_safe = classify_ibs_safe(fodmap)
        wl_safe = classify_weight_loss_safe(caloric_value, protein, fiber)

        rows.append((
            name, caloric_value, fat, sat_fats, mono_fats, poly_fats,
            carbs, sugars, protein, fiber, cholesterol, sodium, water,
            vit_a, vit_b12, vit_c, vit_d, vit_e,
            calcium, iron, magnesium, phosphorus, potassium, zinc, nutrition_density,
            diab_safe, ibs_safe, hyp_safe, pre_safe, wl_safe,
            veg, vegan, halal, gf, fodmap, gi
        ))

    # Write as batch INSERTs
    header = """INSERT IGNORE INTO food_nutrition
    (name, caloric_value, fat, saturated_fats, monounsaturated_fats, polyunsaturated_fats,
     carbohydrates, sugars, protein, dietary_fiber, cholesterol, sodium, water,
     vitamin_a, vitamin_b12, vitamin_c, vitamin_d, vitamin_e,
     calcium, iron, magnesium, phosphorus, potassium, zinc, nutrition_density,
     is_diabetes_safe, is_ibs_safe, is_hypertension_safe, is_prediabetes_safe, is_weight_loss_safe,
     is_vegetarian, is_vegan, is_halal, is_gluten_free, fodmap_level, gi_category)
VALUES\n"""

    for i in range(0, len(rows), BATCH):
        chunk = rows[i:i+BATCH]
        f.write(header)
        values_lines = []
        for r in chunk:
            vals = (
                f"({esc(r[0])}, {r[1]}, {r[2]}, {r[3]}, {r[4]}, {r[5]}, "
                f"{r[6]}, {r[7]}, {r[8]}, {r[9]}, {r[10]}, {r[11]}, {r[12]}, "
                f"{r[13]}, {r[14]}, {r[15]}, {r[16]}, {r[17]}, "
                f"{r[18]}, {r[19]}, {r[20]}, {r[21]}, {r[22]}, {r[23]}, {r[24]}, "
                f"{r[25]}, {r[26]}, {r[27]}, {r[28]}, {r[29]}, "
                f"{r[30]}, {r[31]}, {r[32]}, {r[33]}, {esc(r[34])}, {esc(r[35])})"
            )
            values_lines.append(vals)
        f.write(',\n'.join(values_lines))
        f.write(';\n\n')

    f.write(f'-- Import complete. {len(rows)} foods inserted.\n')

print(f"\n✅ SQL file generated: {out_sql}")
print(f"📊 Total foods imported: {len(rows)}")

# Print classification stats
stats = {
    'Diabetes-safe':     sum(1 for r in rows if r[25]),
    'IBS-safe':          sum(1 for r in rows if r[26]),
    'Hypertension-safe': sum(1 for r in rows if r[27]),
    'Prediabetes-safe':  sum(1 for r in rows if r[28]),
    'Weight-loss-safe':  sum(1 for r in rows if r[29]),
    'Vegetarian':        sum(1 for r in rows if r[30]),
    'Vegan':             sum(1 for r in rows if r[31]),
    'Halal':             sum(1 for r in rows if r[32]),
    'Gluten-free':       sum(1 for r in rows if r[33]),
    'Low FODMAP':        sum(1 for r in rows if r[34] == 'low'),
    'High FODMAP':       sum(1 for r in rows if r[34] == 'high'),
    'Low GI':            sum(1 for r in rows if r[35] == 'low'),
    'High GI':           sum(1 for r in rows if r[35] == 'high'),
}
print("\n📈 Classification statistics:")
for k, v in stats.items():
    print(f"  {k:20s}: {v:4d} foods ({v/len(rows)*100:.1f}%)")
