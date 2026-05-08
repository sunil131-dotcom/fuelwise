<?php
require_once __DIR__ . '/../includes/db.php';

/**
 * FuelWise Recommendation Engine v2.0
 * --------------------------------------
 * - Supports 5 conditions (diabetes, prediabetes, ibs, hypertension, weight_management)
 * - Multi-condition combinations (intersection of safety filters)
 * - 7-day rotating meal plans (weekly_meals table)
 * - Ingredient-level allergy filtering
 * - Exercise recommendations (light for all, gym for weight_management + active)
 * - Graceful handling of missing clinical values
 */

function getLatestPlan($userId) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM recommendations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $rec = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rec) {
        $stmt = $conn->prepare("SELECT * FROM weekly_meals WHERE recommendation_id = ? ORDER BY day_number");
        $stmt->bind_param("i", $rec['id']);
        $stmt->execute();
        $rec['weekly_meals'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    $conn->close();
    return $rec;
}

/**
 * Main entrypoint — generates a fresh 7-day plan + saves to DB.
 */
function generateRecommendation($userId, $profile) {
    $conn = getDB();

    // -- Extract profile values --
    $age       = (int)   $profile['age'];
    $weight    = (float) $profile['weight'];
    $height    = (float) $profile['height'];
    $gender    = $profile['gender'] ?? 'male';
    $activity  = $profile['activity_level']     ?? 'sedentary';
    $dietary   = $profile['dietary_preference'] ?? 'none';
    $cultural  = $profile['cultural_background']?? 'western';
    $allergies = $profile['allergies']          ?? '';
    $weightGoal = $profile['weight_goal']       ?? 'maintain';

    // -- Conditions (array) --
    $condition_type = $profile['condition_type'] ?? 'diabetes';
    $conditions_list = $profile['conditions_list'] ?? '';
    $conditions = parseConditions($condition_type, $conditions_list);

    // -- Clinical values (may be null) --
    $glucose = $profile['glucose_level'] ?? null;
    $bpSys   = $profile['blood_pressure_systolic']  ?? null;
    $bpDia   = $profile['blood_pressure_diastolic'] ?? null;
    $insulin = $profile['insulin_level'] ?? null;
    $cholesterol = $profile['cholesterol_total'] ?? null;

    // -- BMI & BMR (Mifflin-St Jeor) --
    $heightM = $height / 100;
    $bmi     = round($weight / ($heightM * $heightM), 1);
    $bmr     = $gender === 'female'
        ? (10 * $weight) + (6.25 * $height) - (5 * $age) - 161
        : (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;

    // -- TDEE --
    $multipliers = [
        'sedentary'         => 1.2,  'lightly_active' => 1.375,
        'moderately_active' => 1.55, 'very_active'    => 1.725, 'extra_active' => 1.9
    ];
    $tdee = round($bmr * ($multipliers[$activity] ?? 1.2));

    // -- Adjust calories based on condition + goal --
    $dailyCalories = $tdee;

    if (in_array('weight_management', $conditions) || $weightGoal === 'lose') {
        $dailyCalories = round($tdee * 0.80);  // 20% deficit
    } elseif ($weightGoal === 'gain') {
        $dailyCalories = round($tdee * 1.15);  // 15% surplus
    } elseif (in_array('diabetes', $conditions) || in_array('prediabetes', $conditions)) {
        $dailyCalories = round($tdee * 0.92);  // slight deficit
    }
    $dailyCalories = max(1200, $dailyCalories); // floor for safety

    // -- Macros --
    $proteinMultiplier = 1.1;
    if (in_array('diabetes', $conditions))          $proteinMultiplier = 1.2;
    if (in_array('weight_management', $conditions)) $proteinMultiplier = 1.4;
    if (in_array('ibs', $conditions))               $proteinMultiplier = 1.0;

    $protein = round($weight * $proteinMultiplier);
    $fat     = round($dailyCalories * 0.28 / 9);
    $carbs   = round(($dailyCalories - ($protein * 4) - ($fat * 9)) / 4);

    // -- Fiber & Sodium targets --
    $fiberTarget  = in_array('ibs', $conditions) ? 20 : 28;  // IBS tends lower
    $sodiumTarget = in_array('hypertension', $conditions) ? 1500 : 2300;

    // ========================================================
    // Build SQL filter conditions
    // ========================================================
    $condFilters = [];
    if (in_array('diabetes', $conditions))          $condFilters[] = 'f.is_diabetes_safe=1';
    if (in_array('prediabetes', $conditions))       $condFilters[] = 'f.is_prediabetes_safe=1';
    if (in_array('ibs', $conditions))               $condFilters[] = 'f.is_ibs_safe=1';
    if (in_array('hypertension', $conditions))      $condFilters[] = 'f.is_hypertension_safe=1';
    if (in_array('weight_management', $conditions)) $condFilters[] = 'f.is_weight_loss_safe=1';
    $condWhere = empty($condFilters) ? '' : 'AND ' . implode(' AND ', $condFilters);

    $dietFilter = match($dietary) {
        'vegetarian'  => 'AND f.is_vegetarian=1',
        'vegan'       => 'AND f.is_vegan=1',
        'halal'       => 'AND f.is_halal=1',
        'kosher'      => 'AND f.is_kosher=1',
        'gluten_free' => 'AND f.is_gluten_free=1',
        default       => ''
    };

    $cultFilter = in_array($cultural, ['indian','asian','mediterranean','western','african','latin'])
        ? "AND (f.cultural_origin='$cultural' OR f.cultural_origin='universal')"
        : '';

    // ========================================================
    // Save recommendation record FIRST (we need the id)
    // ========================================================
    $notes = generateNotes($conditions, $bmi, $glucose, $bpSys, $bpDia, $insulin, $cholesterol, $dietary);

    $stmt = $conn->prepare("INSERT INTO recommendations
        (user_id, condition_type, conditions_list, daily_calories, daily_protein, daily_carbs, daily_fat,
         daily_fiber, daily_sodium, breakfast, lunch, dinner, snacks, notes, plan_type)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'weekly')");

    $emptyText = '';
    $stmt->bind_param("isssddddssssss",
        $userId, $condition_type, $conditions_list,
        $dailyCalories, $protein, $carbs, $fat, $fiberTarget, $sodiumTarget,
        $emptyText, $emptyText, $emptyText, $emptyText, $notes);
    $stmt->execute();
    $recId = $conn->insert_id;
    $stmt->close();

    // ========================================================
    // Generate 7 different days of meals
    // ========================================================
    $dayNames = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $baseSeed = ($userId * 1000) + (int)date('z') + (int)(microtime(true) * 100) % 1000;

    for ($day = 1; $day <= 7; $day++) {
        $daySeed = $baseSeed + ($day * 37);
        $dayMeals = [];

        foreach (['breakfast','lunch','dinner','snack'] as $cat) {
            $dayMeals[$cat] = fetchMealsWithFallback(
                $conn, $cat, $condWhere, $dietFilter, $cultFilter, $allergies, $daySeed, 2);
        }

        $breakfastText = formatMealList($dayMeals['breakfast']);
        $lunchText     = formatMealList($dayMeals['lunch']);
        $dinnerText    = formatMealList($dayMeals['dinner']);
        $snackText     = formatMealList($dayMeals['snack']);

        // Rough day totals (sum of first item of each meal, since the UI shows those as primary)
        $dayCals = $dayProt = $dayCarbs = $dayFat = 0;
        foreach ($dayMeals as $cat => $items) {
            if (!empty($items[0])) {
                $dayCals  += $items[0]['calories'] ?? 0;
                $dayProt  += $items[0]['protein']  ?? 0;
                $dayCarbs += $items[0]['carbohydrates'] ?? 0;
                $dayFat   += $items[0]['fat'] ?? 0;
            }
        }

        $dayName = $dayNames[$day - 1];
        $stmt = $conn->prepare("INSERT INTO weekly_meals
            (recommendation_id, user_id, day_number, day_name, breakfast, lunch, dinner, snacks,
             day_calories, day_protein, day_carbs, day_fat)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iiisssssdddd",
            $recId, $userId, $day, $dayName,
            $breakfastText, $lunchText, $dinnerText, $snackText,
            $dayCals, $dayProt, $dayCarbs, $dayFat);
        $stmt->execute();
        $stmt->close();

        // Day 1 meals get copied to the recommendations row (legacy UI)
        if ($day === 1) {
            $stmt = $conn->prepare("UPDATE recommendations SET breakfast=?, lunch=?, dinner=?, snacks=? WHERE id=?");
            $stmt->bind_param("ssssi", $breakfastText, $lunchText, $dinnerText, $snackText, $recId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // ========================================================
    // Update BMI / BMR
    // ========================================================
    $stmt = $conn->prepare("UPDATE health_profiles SET bmi=?, bmr=? WHERE user_id=?");
    $stmt->bind_param("ddi", $bmi, $bmr, $userId);
    $stmt->execute();
    $stmt->close();

    // Track stat
    $today = date('Y-m-d');
    $conn->query("INSERT INTO user_streaks (user_id, total_plans_generated, last_plan_date)
                  VALUES ({$userId}, 1, '{$today}')
                  ON DUPLICATE KEY UPDATE
                  total_plans_generated = total_plans_generated + 1,
                  last_plan_date = '{$today}'");
    $conn->close();

    return ['id' => $recId, 'daily_calories' => $dailyCalories];
}

/**
 * Fetch meals with 4-tier fallback if first filters return nothing.
 * Also applies allergy filtering at the PHP level (using ingredient column).
 */
function fetchMealsWithFallback($conn, $category, $condWhere, $dietFilter, $cultFilter, $allergies, $seed, $limit = 2) {
    $attempts = [
        // Full filters
        "SELECT f.* FROM foods f WHERE f.category='$category' $condWhere $dietFilter $cultFilter ORDER BY RAND($seed) LIMIT 10",
        // Drop cultural filter
        "SELECT f.* FROM foods f WHERE f.category='$category' $condWhere $dietFilter ORDER BY RAND($seed) LIMIT 10",
        // Drop dietary filter
        "SELECT f.* FROM foods f WHERE f.category='$category' $condWhere ORDER BY RAND($seed) LIMIT 10",
        // Final fallback: any food in this category
        "SELECT f.* FROM foods f WHERE f.category='$category' ORDER BY RAND($seed) LIMIT 10",
    ];

    foreach ($attempts as $sql) {
        $r = $conn->query($sql);
        if (!$r) continue;
        $rows = $r->fetch_all(MYSQLI_ASSOC);
        // Apply allergy filtering (ingredient-level)
        $safe = array_filter($rows, function($food) use ($allergies) {
            if (empty($allergies)) return true;
            $haystack = strtolower(($food['name'] ?? '') . ' ' . ($food['ingredients'] ?? ''));
            return !foodMatchesAllergies($haystack, $allergies);
        });
        $safe = array_values($safe); // reindex
        if (count($safe) >= $limit) return array_slice($safe, 0, $limit);
        if (count($safe) > 0 && $sql === end($attempts)) return $safe; // accept whatever we got
    }
    return [];
}

function formatMealList($items) {
    if (empty($items)) return 'No suitable meals found — please adjust your preferences.';
    return implode(' | ', array_map(fn($m) => $m['name'], $items));
}

/**
 * Generate personalized dietary notes based on conditions and clinical values.
 */
function generateNotes($conditions, $bmi, $glucose, $bpSys, $bpDia, $insulin, $cholesterol, $dietary) {
    $notes = [];

    // Condition-specific notes
    if (in_array('diabetes', $conditions) || in_array('prediabetes', $conditions)) {
        $notes[] = "🩸 Focus on low glycemic index foods (GI < 55) to manage blood sugar.";
        $notes[] = "🍽️ Eat smaller, more frequent meals to maintain stable glucose.";
        $notes[] = "🚫 Limit refined carbohydrates, white rice, sugary drinks, and processed foods.";
        if ($glucose !== null && $glucose > GLUCOSE_HIGH_THRESHOLD) {
            $notes[] = "⚠️ Your glucose reading (" . $glucose . " mg/dL) appears elevated. Please consult your healthcare provider.";
        }
        if ($insulin !== null && $insulin > INSULIN_HIGH_THRESHOLD) {
            $notes[] = "⚠️ Your fasting insulin (" . $insulin . " μU/mL) appears elevated — this may indicate insulin resistance. Please discuss with your doctor.";
        }
    }

    if (in_array('ibs', $conditions)) {
        $notes[] = "🌿 Follow Low-FODMAP principles — avoid onions, garlic, wheat, and legumes initially.";
        $notes[] = "🐢 Eat slowly and chew thoroughly. Avoid eating when stressed.";
        $notes[] = "💧 Stay hydrated — drink 6–8 glasses of water daily.";
    }

    if (in_array('hypertension', $conditions)) {
        $notes[] = "🫀 Follow the DASH diet: low sodium (<1500mg/day), rich in potassium, magnesium, and calcium.";
        $notes[] = "🧂 Avoid processed foods, canned soups, and salty snacks. Cook with herbs instead of salt.";
        $notes[] = "🍌 Potassium-rich foods (bananas, sweet potatoes, leafy greens) help lower blood pressure.";
        if ($bpSys !== null && $bpSys >= BP_SYSTOLIC_HIGH_THRESHOLD) {
            $notes[] = "⚠️ Your systolic BP (" . $bpSys . ") is in the high range. Please consult your healthcare provider.";
        } elseif ($bpSys !== null && $bpSys >= BP_SYSTOLIC_ELEVATED_THRESHOLD) {
            $notes[] = "ℹ️ Your systolic BP (" . $bpSys . ") is slightly elevated — diet and exercise can help.";
        }
    }

    if (in_array('weight_management', $conditions)) {
        $notes[] = "⚖️ Modest calorie deficit, high protein intake to preserve muscle, and regular activity are key.";
        $notes[] = "💪 Aim for 150 min/week moderate exercise + 2 strength sessions (see exercise recommendations).";
        $notes[] = "🥗 Fill half your plate with vegetables at every meal.";
    }

    // BMI-based notes
    if ($bmi >= 25 && $bmi < 30) {
        $notes[] = "📊 Your BMI ({$bmi}) indicates overweight. Sustainable small changes yield best results.";
    } elseif ($bmi >= 30) {
        $notes[] = "📊 Your BMI ({$bmi}) indicates obesity. Please work with a healthcare professional for a comprehensive plan.";
    } elseif ($bmi < 18.5) {
        $notes[] = "📊 Your BMI ({$bmi}) indicates underweight. Focus on nutrient-dense, calorie-sufficient foods.";
    }

    // Cholesterol
    if ($cholesterol !== null && $cholesterol > 240) {
        $notes[] = "⚠️ Your cholesterol (" . $cholesterol . " mg/dL) is high. Limit saturated fats; increase fiber and omega-3s.";
    }

    // Dietary-specific
    if (in_array($dietary, ['vegetarian','vegan'])) {
        $notes[] = "🌱 Ensure adequate protein from plant sources: lentils, chickpeas, tofu, and quinoa.";
        $notes[] = "💊 Consider B12 supplementation (especially for vegans) and monitor iron intake.";
    }

    $notes[] = "⚕️ FuelWise is a dietary guidance tool. Always consult your doctor or dietitian before making major changes.";
    return implode("\n", $notes);
}

// ============================================================
// EXERCISES
// ============================================================
function getRecommendedExercises($userId, $conditions, $activity, $weightGoal = 'maintain') {
    $conn = getDB();

    // Include gym only for weight_management + moderately+ active
    $includeGym = (in_array('weight_management', $conditions) || $weightGoal === 'lose') &&
                  in_array($activity, ['moderately_active','very_active','extra_active']);

    $filters = [];
    if (in_array('diabetes', $conditions))     $filters[] = 'is_diabetes_safe=1';
    if (in_array('ibs', $conditions))          $filters[] = 'is_ibs_safe=1';
    if (in_array('hypertension', $conditions)) $filters[] = 'is_hypertension_safe=1';
    if (in_array('prediabetes', $conditions))  $filters[] = 'is_prediabetes_safe=1';

    // Activity level gating
    $activityOrder = ['sedentary'=>0,'lightly_active'=>1,'moderately_active'=>2,'very_active'=>3,'extra_active'=>4];
    $userLevel = $activityOrder[$activity] ?? 0;
    $levelFilter = [];
    foreach ($activityOrder as $lvl => $rank) {
        if ($rank <= $userLevel) $levelFilter[] = "'$lvl'";
    }
    $levelClause = 'min_activity_level IN (' . implode(',', $levelFilter) . ')';

    $where = [$levelClause];
    if (!empty($filters)) $where[] = implode(' AND ', $filters);
    if (!$includeGym) $where[] = "type != 'strength' OR intensity = 'light' OR intensity = 'moderate' AND difficulty = 'beginner'";

    $sql = "SELECT * FROM exercises WHERE " . implode(' AND ', $where) . " ORDER BY RAND() LIMIT 8";
    $result = $conn->query($sql);
    $exercises = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // If user needs gym exercises and qualifies, also fetch 3 gym-specific
    $gymExercises = [];
    if ($includeGym) {
        $gymSql = "SELECT * FROM exercises WHERE is_weight_loss=1 AND type='strength'
                   AND min_activity_level IN ('moderately_active','very_active','extra_active')
                   ORDER BY RAND() LIMIT 4";
        $r = $conn->query($gymSql);
        $gymExercises = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    $conn->close();
    return ['regular' => $exercises, 'gym' => $gymExercises, 'include_gym' => $includeGym];
}

// ============================================================
// Direct API handler (for AJAX calls like regenerate)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    requireLogin();
    $userId = $_SESSION['user_id'];
    $conn   = getDB();

    // Get current profile
    $stmt = $conn->prepare("SELECT * FROM health_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Also get gender from users table
    $stmt = $conn->prepare("SELECT gender FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$profile) {
        echo json_encode(['error' => 'No health profile found.']);
        exit;
    }
    $profile['gender'] = $user['gender'] ?? 'male';
    $rec = generateRecommendation($userId, $profile);
    echo json_encode(['success' => true, 'data' => $rec]);
    exit;
}
