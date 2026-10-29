# 🌾 Feed Formulation & Nutrition Calculator — Feature Specification

## 🎯 Vision
Integrate feed recipe design and nutrition analysis directly into KUKU Farm Management, empowering farmers to:
- Design custom feed recipes meeting specific protein and energy targets
- Track each batch's exact feed formula received
- Correlate feed composition with measured growth (weight gain)
- Save cost per kg and compare alternative ingredient substitutions

### Why This Matters
Farmers who formulate feed themselves control raw ingredient costs but risk suboptimal nutrition. Correctly formulated diets boost growth rates, reduce mortality, and improve egg production — directly increasing profit margin. This feature turns KUKU from a bookkeeper into an active production optimizer.

---

## 📋 Feature Breakdown

### A. Feed Recipe Builder
| Capability | Description |
|------------|-------------|
| **Ingredient Library** | Predefined common ingredients: corn, soybean meal, fishmeal, wheat bran, bone meal, limestone, vitamin premixes, etc. |
| **Recipe Creation** | Add any number of ingredients with weighted percentages |
| **Nutrition Targets** | Set desired Crude Protein, ME (Mcal/kg), Calcium, Phosphorus percentages |
| **Validation** | Check that sum of % = 100; warn if below or above target |
| **Save Recipe** | Store recipes as named profiles (e.g., "Layer Ration 3% protein") |
| **Load Recipe** | Reuse saved recipes across days/months |

### B. Nutrition Calculator
| Capability | Description |
|------------|------------|
| **Protein Analysis** | Sum crude protein content per kg of each ingredient weighted by % |
| **Energy Calc** | Calculate Metabolizable Energy (Mcal/kg) — metAF or DE basis |
| **Minerals** | Track Calcium, Phosphorus, Phytonutrients |
| **Cost per kg** | Weighted average cost based on today's purchase prices |
| **Unit Price Input** | Each ingredient has editable unit price ($/tonne or $/kg) |
| **Diet Label Summary** | Auto-generate readable "Feed + X% protein" summary |

### C. Growth Correlation Tracker
| Capability | Description |
|------------|-------------|
| **Batch Feed Assignment** | Record which feed recipe each chicken batch was fed |
| **Weight Input** | Measure chicken weight daily or weekly |
| **Correlation Charts** | Cross-tabulation: which feed formulas produced most weight gain? |
| **Growth Metrics** | Avg weekly weight gain per feed formula vs control |
| **Profit Signal** | Did cheaper feed produce lower/equal growth? (Avoid paying premium) |

### D. Cost Optimization Helper
| Capability | Description |
|------------|------------|
| **Substitution Suggestion** | "Save $0.50/tonne by replacing fishmeal with cheaper soybean" |
| **Cost Trend** | Plot feed cost over time per ingredient |
| **Margin Alert** | Flag if feed cost exceeds chicken market price for the batch type |

---

## 🧮 Example Calculation

```
RECIPE: "Broiler Starter Ration"
  Corn            40% @    $0.12 / kg  (crude protein: 7.7%, ME: 3.6 Mcal)
  Soybean meal   35%      @   $0.38 / kg  (crude protein: 44.0%, ME: 2.9)
  Wheat bran     15%       @   $0.10 / kg  (crude protein: 11.0%, ME: 3.1)
  Salt premix     10%      @   $0.08 / kg  (crude protein: 0%)

SUMMARY:
  Total protein = 8.08%    ✓ Meets target ≥ 7.5%
  Total cost    = $0.1935 / kg
  For 50 kg ration = $9.68 total
```

---

## 📐 Data Model (New Tables)

### `feed_ingredients`
| Column | Type | Purpose |
|--------|------|---------|
| id | INTEGER PK | |
| name | TEXT | Corn, Soybean meal |
| category | TEXT | Energy, Protein, Mineral, Vitamin |
| crude_protein_pct | REAL | % of dry matter |
| energy_mcal | REAL | Mcal/kg |
| calcium_pct | REAL | % |
| phosphorus_pct | REAL | % |
| unit_price | REAL | $/tonne |

### `feed_recipes`
| Column | Type | Purpose |
|--------|------|---------|
| id | INTEGER PK | |
| name | TEXT | Broiler Starter Ration |
| target_protein | REAL | e.g., 22.0 |
| target_energy | REAL | e.g., 3000 (Mcal/tonne? or kcal?) |
| is_default | BOOLEAN | Used as farm default |
| created_by | INTEGER FK users | |

### `feed_recipe_ingredients`
| Column | Type | Purpose |
|--------|------|---------|
| id | INTEGER PK | |
| recipe_id | INTEGER FK | |
| ingredient_id | INTEGER FK | |
| percentage | REAL | 0-100 (must sum to 100 for a recipe) |
| sort_order | INT | Layout order in UI |

### `feed_batches`
| Column | Type | Purpose |
|--------|------|---------|
| id | INTEGER PK | |
| recipe_id | INTEGER FK | Which recipe fed |
| quantity_kg | REAL | Amount fed this day |
| cost_per_kg | REAL | Assessed |
| created_at | TIMESTAMP | |
| recorded_by | INTEGER FK users | |

---

## 🖥️ UI Wireframe Ideas

### Main Entry: "Feed Recipes" section on dashboard
- Top: "Create New Recipe" button → opens modal with ingredient library sidebar
- Below: Saved recipes as cards (recipe name, protein, cost/kg, save icon)
- Click card → open detail view with the recipe builder

### Recipe Builder Modal
- Left: Ingredient grid (checkboxes + % input, drag-to-reorder)
- Right: Nutrition calculation live (big numbers updating)
- Bottom: "Save" button + preset dropdowns ("Use defaults: Layer, Broiler, Breeder")

### Growth Correlation Page
- Dropdown: "Feed Formula" selector
- Table: BatchID | Recipe | Weight Start | Weight End | Days | Gain | Cost/kg
- Bar chart: Average gain by formula
- Line chart: Cost trend over months

---

## 🔗 Existing System Integration Points

| Existing Feature | How Feed Feature Connects |
|------------------|---------------------------|
| **Chickens** → assign recipe to batch via "Feeding" tab | Batch records link to `feed_batches` |
| **Inventory** → ingredient prices come from stock records | `feed_ingredients.unit_price` pulled at feed time |
| **Birds** → weight tracked here | Growth data feeds correlation charts |
| **Sales** → sell eggs/meat with price | Margin analysis cross-references product price |

---

## 🧪 Acceptance Criteria

1. [ ] Create feed recipe with 3+ ingredients targeting specific % protein
2. [ ] Nutrition values update live as percentages are typed
3. [ ] Recipe sums to exactly 100% (validation error shown otherwise)
4. [ ] Cost per kg calculated correctly (weighted average)
5. [ ] Feed batch record links recipe → inventory stock snapshot
6. [ ] Chicken weight tracked and matched to assigned feed batch
7. [ ] Correlation report shows which recipe produced highest average weight gain
8. [ ] Substitution suggestion works (auto-swap expensive for cheap equivalent)
9. [ ] Both desktop Electron and web PHP versions have this feature
10. [ ] Database tables created automatically on schema migration

---

## 🗺️ Implementation Order

| Step | Task | Dependencies |
|------|------|--------------|
| 1 | Add DB tables (`feed_ingredients`, `feed_recipes`, etc.) to schema | PostgreSQL / SQLite migrations |
| 2 | Build Nutrition Calculator backend (server.js Express endpoint) | server.js modifications |
| 3 | Build Recipe Builder UI (desktop HTML + web PHP) | Frontend files in `desktop/renderer/`, `public/` |
| 4 | Build Growth Tracking UI for batches | Existing chicken management pages |
| 5 | Build Correlation Dashboard with Chart.js | reports / analytics pages |
| 6 | Integrate feed selection into chicken batch creation flow | server.js chicken routes |
| 7 | Add substitution algorithm logic | Step 2 endpoint enhancement |
