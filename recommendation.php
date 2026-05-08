<?php
require_once 'includes/db.php';
require_once __DIR__ . '/api/recommend.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Handle AJAX actions (regenerate, quick update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'regenerate_simple') {
        // Use existing profile, just regenerate meals
        $stmt = $conn->prepare("SELECT hp.*, u.gender FROM health_profiles hp JOIN users u ON hp.user_id = u.id WHERE hp.user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($profile) {
            logProfileUpdate($userId, 'regenerate', [], []);
            generateRecommendation($userId, $profile);
            setFlash('success', '✨ Your meal plan has been regenerated with fresh ideas!');
        }
        redirect('recommendation.php');
    }

    if ($action === 'quick_update') {
        // Quick update form from modal
        $stmt = $conn->prepare("SELECT * FROM health_profiles WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            setFlash('error', 'Please complete the full assessment first.');
            redirect('assessment.php');
        }

        $oldSnapshot = [
            'weight'             => $existing['weight'],
            'glucose_level'      => $existing['glucose_level'],
            'blood_pressure'     => $existing['blood_pressure'],
            'dietary_preference' => $existing['dietary_preference'],
            'allergies'          => $existing['allergies'],
            'activity_level'     => $existing['activity_level'],
        ];

        // Merge updates into existing (only non-empty)
        $fields = [
            'weight'             => !empty($_POST['weight'])             ? (float)$_POST['weight']             : $existing['weight'],
            'activity_level'     => !empty($_POST['activity_level'])     ? sanitize($_POST['activity_level'])  : $existing['activity_level'],
            'glucose_level'      => !empty($_POST['glucose_level'])      ? (float)$_POST['glucose_level']      : $existing['glucose_level'],
            'blood_pressure_systolic'  => !empty($_POST['blood_pressure_systolic'])  ? (int)$_POST['blood_pressure_systolic']  : $existing['blood_pressure_systolic'],
            'blood_pressure_diastolic' => !empty($_POST['blood_pressure_diastolic']) ? (int)$_POST['blood_pressure_diastolic'] : $existing['blood_pressure_diastolic'],
            'dietary_preference' => !empty($_POST['dietary_preference']) ? sanitize($_POST['dietary_preference']) : $existing['dietary_preference'],
            'cultural_background'=> !empty($_POST['cultural_background'])? sanitize($_POST['cultural_background']) : $existing['cultural_background'],
            'allergies'          => isset($_POST['allergies'])            ? sanitize($_POST['allergies'])       : $existing['allergies'],
        ];
        $bpString = ($fields['blood_pressure_systolic'] && $fields['blood_pressure_diastolic'])
            ? $fields['blood_pressure_systolic'] . '/' . $fields['blood_pressure_diastolic']
            : null;

        // Update the profile
        $stmt = $conn->prepare("UPDATE health_profiles SET weight=?, activity_level=?,
            glucose_level=?, blood_pressure_systolic=?, blood_pressure_diastolic=?, blood_pressure=?,
            dietary_preference=?, cultural_background=?, allergies=? WHERE user_id=?");
        $stmt->bind_param("dsdiisssssi",
            $fields['weight'], $fields['activity_level'],
            $fields['glucose_level'],
            $fields['blood_pressure_systolic'], $fields['blood_pressure_diastolic'], $bpString,
            $fields['dietary_preference'], $fields['cultural_background'], $fields['allergies'],
            $userId);
        $stmt->execute();
        $stmt->close();

        $newSnapshot = [
            'weight'             => $fields['weight'],
            'glucose_level'      => $fields['glucose_level'],
            'blood_pressure'     => $bpString,
            'dietary_preference' => $fields['dietary_preference'],
            'allergies'          => $fields['allergies'],
            'activity_level'     => $fields['activity_level'],
        ];
        logProfileUpdate($userId, 'quick_update', $oldSnapshot, $newSnapshot);

        // Fetch updated profile + regenerate
        $stmt = $conn->prepare("SELECT hp.*, u.gender FROM health_profiles hp JOIN users u ON hp.user_id = u.id WHERE hp.user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $updated = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        generateRecommendation($userId, $updated);

        setFlash('success', '✅ Your profile has been updated and your plan regenerated!');
        redirect('recommendation.php');
    }
}

// -- Get latest recommendation + weekly meals --
$rec = getLatestPlan($userId);

// -- Get health profile --
$stmt = $conn->prepare("SELECT * FROM health_profiles WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// -- Get exercises --
$exerciseData = ['regular' => [], 'gym' => [], 'include_gym' => false];
if ($profile) {
    $conditions = parseConditions($profile['condition_type'], $profile['conditions_list']);
    $exerciseData = getRecommendedExercises($userId, $conditions, $profile['activity_level'], $profile['weight_goal'] ?? 'maintain');
}

// Parse conditions for display
$conditionsArray = [];
if ($profile) {
    $conditionsArray = parseConditions($profile['condition_type'], $profile['conditions_list']);
}

$pageTitle = 'My 7-Day Diet Plan';
$extraCSS  = 'dashboard.css';
include 'includes/header.php';
?>

<div class="inner-page">
    <div class="page-header">
        <h1>Your 7-Day Personalised Plan</h1>
        <p>AI-generated nutrition and exercise recommendations based on your health profile</p>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
            <span>›</span>
            <a href="<?php echo SITE_URL; ?>/dashboard.php">Dashboard</a>
            <span>›</span>
            <span class="current">Diet Plan</span>
        </div>
    </div>

    <div class="inner-content">
        <?php showFlash(); ?>

        <?php if (!$rec): ?>
            <div style="text-align:center; padding: 60px 24px;">
                <div style="font-size:4rem; margin-bottom:20px;">🥗</div>
                <h2 style="color:var(--navy-dark); margin-bottom:12px;">No Diet Plan Yet</h2>
                <p style="color:var(--text-mid); margin-bottom:28px;">Complete your health assessment to receive your personalised 7-day plan.</p>
                <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-primary btn-lg">Start Assessment →</a>
            </div>
        <?php else: ?>

        <!-- Summary Cards -->
        <div class="stats-grid" style="margin-bottom:28px;">
            <div class="stat-card">
                <div class="stat-icon orange">🔥</div>
                <div>
                    <div class="stat-value"><?php echo number_format($rec['daily_calories']); ?></div>
                    <div class="stat-label">Daily Calories (kcal)</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">💪</div>
                <div>
                    <div class="stat-value"><?php echo $rec['daily_protein']; ?>g</div>
                    <div class="stat-label">Daily Protein</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">🌾</div>
                <div>
                    <div class="stat-value"><?php echo $rec['daily_carbs']; ?>g</div>
                    <div class="stat-label">Daily Carbohydrates</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">🥑</div>
                <div>
                    <div class="stat-value"><?php echo $rec['daily_fat']; ?>g</div>
                    <div class="stat-label">Daily Fat</div>
                </div>
            </div>
        </div>

        <!-- Condition badges + Actions -->
        <div style="display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <?php
                $labels = getConditionLabels();
                foreach ($conditionsArray as $cond):
                    $info = $labels[$cond] ?? null;
                    if (!$info) continue;
                ?>
                <span class="condition-badge"><?php echo $info[0]; ?> <?php echo $info[2]; ?></span>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; gap:10px;">
                <form method="POST" style="display:inline;" onsubmit="return confirm('Generate a fresh 7-day plan?');">
                    <input type="hidden" name="action" value="regenerate_simple">
                    <button type="submit" class="btn-outline-green">🔄 Shuffle Plan</button>
                </form>
                <button type="button" class="btn-primary" onclick="document.getElementById('quickUpdateModal').style.display='flex';">
                    ✏️ Update Profile & Regenerate
                </button>
            </div>
        </div>

        <!-- 7-Day Meal Plan with Day Tabs -->
        <div class="panel">
            <div class="panel-header">
                <h3>🍽️ Your 7-Day Meal Plan</h3>
                <?php
                // Highlight today
                $todayDay = date('N'); // 1=Mon, 7=Sun
                ?>
                <span class="badge badge-green">Today: <?php echo date('l'); ?></span>
            </div>
            <div class="panel-body">

                <!-- Day tabs -->
                <div class="day-tabs">
                    <?php
                    $weeklyMeals = $rec['weekly_meals'] ?? [];
                    foreach ($weeklyMeals as $idx => $dayPlan):
                        $isToday = $dayPlan['day_number'] == $todayDay ? 'today' : '';
                        $isActive = $idx === 0 ? 'active' : '';
                    ?>
                    <button type="button" class="day-tab <?php echo $isActive; ?> <?php echo $isToday; ?>"
                            data-day="<?php echo $dayPlan['day_number']; ?>">
                        <strong><?php echo substr($dayPlan['day_name'], 0, 3); ?></strong>
                        <small>Day <?php echo $dayPlan['day_number']; ?></small>
                        <?php if ($isToday): ?><span class="today-dot">●</span><?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Day content -->
                <?php foreach ($weeklyMeals as $idx => $dayPlan): ?>
                <div class="day-content <?php echo $idx === 0 ? 'active' : ''; ?>" data-day-content="<?php echo $dayPlan['day_number']; ?>">
                    <div class="day-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h4 style="margin:0;color:var(--navy-dark);"><?php echo $dayPlan['day_name']; ?></h4>
                        <div style="display:flex;gap:12px;font-size:0.82rem;color:var(--text-mid);">
                            <span>🔥 <?php echo number_format($dayPlan['day_calories']); ?> kcal</span>
                            <span>💪 <?php echo $dayPlan['day_protein']; ?>g</span>
                            <span>🌾 <?php echo $dayPlan['day_carbs']; ?>g</span>
                            <span>🥑 <?php echo $dayPlan['day_fat']; ?>g</span>
                        </div>
                    </div>

                    <?php
                    $mealTypes = [
                        'breakfast' => ['🌅', 'Breakfast', $dayPlan['breakfast']],
                        'lunch'     => ['☀️', 'Lunch',     $dayPlan['lunch']],
                        'dinner'    => ['🌙', 'Dinner',    $dayPlan['dinner']],
                        'snacks'    => ['🍎', 'Snacks',    $dayPlan['snacks']],
                    ];
                    foreach ($mealTypes as $key => [$icon, $label, $items]):
                        $itemList = explode(' | ', $items);
                    ?>
                    <div class="meal-card">
                        <div class="meal-header <?php echo $key === 'snacks' ? 'snack' : $key; ?>">
                            <?php echo $icon; ?> <?php echo $label; ?>
                        </div>
                        <div class="meal-body">
                            <?php foreach ($itemList as $i => $item): ?>
                                <div class="meal-item">
                                    <span class="meal-option-num">Option <?php echo $i + 1; ?></span>
                                    <p><?php echo htmlspecialchars(trim($item)); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px; margin-top:24px;">
            <div>
                <!-- Exercises -->
                <?php if (!empty($exerciseData['regular']) || !empty($exerciseData['gym'])): ?>
                <div class="panel">
                    <div class="panel-header">
                        <h3>💪 Recommended Exercises</h3>
                        <span class="badge badge-blue">Personalized</span>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($exerciseData['regular'])): ?>
                        <h4 style="color:var(--navy-dark);margin:4px 0 16px;font-size:0.95rem;">Recommended for your conditions</h4>
                        <div class="exercise-grid">
                            <?php foreach (array_slice($exerciseData['regular'], 0, 6) as $ex): ?>
                            <div class="exercise-card">
                                <div class="exercise-header">
                                    <h5><?php echo htmlspecialchars($ex['name']); ?></h5>
                                    <span class="exercise-intensity intensity-<?php echo $ex['intensity']; ?>">
                                        <?php echo ucfirst($ex['intensity']); ?>
                                    </span>
                                </div>
                                <div class="exercise-meta">
                                    <span>⏱ <?php echo $ex['duration_minutes']; ?> min</span>
                                    <span>🔥 <?php echo $ex['calories_per_30min']; ?> kcal/30min</span>
                                    <span>🏃 MET <?php echo $ex['met_value']; ?></span>
                                </div>
                                <p class="exercise-instructions"><?php echo htmlspecialchars($ex['instructions']); ?></p>
                                <?php if (!empty($ex['contraindications']) && $ex['contraindications'] !== 'None'): ?>
                                <p class="exercise-warning">⚠️ <?php echo htmlspecialchars($ex['contraindications']); ?></p>
                                <?php endif; ?>
                                <small class="exercise-citation"><?php echo htmlspecialchars($ex['source_citation']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($exerciseData['gym']) && $exerciseData['include_gym']): ?>
                        <h4 style="color:var(--navy-dark);margin:24px 0 16px;font-size:0.95rem;">🏋️ Gym/Strength (for Weight Management)</h4>
                        <div class="exercise-grid">
                            <?php foreach ($exerciseData['gym'] as $ex): ?>
                            <div class="exercise-card gym">
                                <div class="exercise-header">
                                    <h5><?php echo htmlspecialchars($ex['name']); ?></h5>
                                    <span class="exercise-intensity intensity-<?php echo $ex['intensity']; ?>">
                                        <?php echo ucfirst($ex['intensity']); ?>
                                    </span>
                                </div>
                                <div class="exercise-meta">
                                    <?php if ($ex['sets']): ?><span>📊 <?php echo $ex['sets']; ?> × <?php echo $ex['reps']; ?></span><?php endif; ?>
                                    <span>💪 <?php echo htmlspecialchars($ex['body_part']); ?></span>
                                </div>
                                <p class="exercise-instructions"><?php echo htmlspecialchars($ex['instructions']); ?></p>
                                <?php if (!empty($ex['contraindications'])): ?>
                                <p class="exercise-warning">⚠️ <?php echo htmlspecialchars($ex['contraindications']); ?></p>
                                <?php endif; ?>
                                <small class="exercise-citation"><?php echo htmlspecialchars($ex['source_citation']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Macros Breakdown -->
                <div class="panel" style="margin-top:20px;">
                    <div class="panel-header"><h3>📊 Macronutrient Breakdown</h3></div>
                    <div class="panel-body">
                        <?php
                        $totalCals  = $rec['daily_calories'];
                        $proteinPct = $totalCals > 0 ? round(($rec['daily_protein'] * 4 / $totalCals) * 100) : 0;
                        $carbsPct   = $totalCals > 0 ? round(($rec['daily_carbs']   * 4 / $totalCals) * 100) : 0;
                        $fatPct     = $totalCals > 0 ? round(($rec['daily_fat']     * 9 / $totalCals) * 100) : 0;
                        ?>
                        <div class="nutrition-bar">
                            <span class="nutrition-label">🔥 Calories</span>
                            <div class="nutrition-track"><div class="nutrition-fill calories" style="width:100%"></div></div>
                            <span class="nutrition-value"><?php echo number_format($rec['daily_calories']); ?> kcal</span>
                        </div>
                        <div class="nutrition-bar">
                            <span class="nutrition-label">💪 Protein</span>
                            <div class="nutrition-track"><div class="nutrition-fill protein" style="width:<?php echo $proteinPct; ?>%"></div></div>
                            <span class="nutrition-value"><?php echo $rec['daily_protein']; ?>g (<?php echo $proteinPct; ?>%)</span>
                        </div>
                        <div class="nutrition-bar">
                            <span class="nutrition-label">🌾 Carbs</span>
                            <div class="nutrition-track"><div class="nutrition-fill carbs" style="width:<?php echo $carbsPct; ?>%"></div></div>
                            <span class="nutrition-value"><?php echo $rec['daily_carbs']; ?>g (<?php echo $carbsPct; ?>%)</span>
                        </div>
                        <div class="nutrition-bar">
                            <span class="nutrition-label">🥑 Fat</span>
                            <div class="nutrition-track"><div class="nutrition-fill fat" style="width:<?php echo $fatPct; ?>%"></div></div>
                            <span class="nutrition-value"><?php echo $rec['daily_fat']; ?>g (<?php echo $fatPct; ?>%)</span>
                        </div>
                        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);display:flex;gap:24px;font-size:0.85rem;color:var(--text-mid);">
                            <span>🌾 Daily Fiber Target: <strong><?php echo $rec['daily_fiber']; ?>g</strong></span>
                            <span>🧂 Daily Sodium Limit: <strong><?php echo number_format($rec['daily_sodium']); ?>mg</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <?php if ($profile): ?>
                <div class="panel" style="margin-bottom:20px;">
                    <div class="panel-header"><h3>👤 Your Profile</h3></div>
                    <div class="panel-body">
                        <div class="profile-info">
                            <div class="info-row"><span class="label">BMI</span><span class="value"><?php echo $profile['bmi']; ?>
                                <?php
                                $b = $profile['bmi'];
                                if ($b < 18.5) echo '<span class="badge badge-blue">Underweight</span>';
                                elseif ($b < 25) echo '<span class="badge badge-green">Normal</span>';
                                elseif ($b < 30) echo '<span class="badge badge-orange">Overweight</span>';
                                else echo '<span class="badge badge-red">Obese</span>';
                                ?>
                            </span></div>
                            <div class="info-row"><span class="label">BMR</span><span class="value"><?php echo number_format($profile['bmr']); ?> kcal</span></div>
                            <div class="info-row"><span class="label">Conditions</span><span class="value"><?php echo getConditionDisplay($profile['condition_type'], $profile['conditions_list']); ?></span></div>
                            <div class="info-row"><span class="label">Diet Pref.</span><span class="value"><?php echo ucfirst(str_replace('_',' ',$profile['dietary_preference'])); ?></span></div>
                            <div class="info-row"><span class="label">Culture</span><span class="value"><?php echo ucfirst($profile['cultural_background']); ?></span></div>
                            <div class="info-row"><span class="label">Activity</span><span class="value"><?php echo ucfirst(str_replace('_',' ',$profile['activity_level'])); ?></span></div>
                            <?php if ($profile['glucose_level']): ?>
                            <div class="info-row"><span class="label">Glucose</span><span class="value"><?php echo $profile['glucose_level']; ?> mg/dL</span></div>
                            <?php endif; ?>
                            <?php if ($profile['blood_pressure']): ?>
                            <div class="info-row"><span class="label">BP</span><span class="value"><?php echo $profile['blood_pressure']; ?> mmHg</span></div>
                            <?php endif; ?>
                            <?php if (!empty($profile['allergies'])): ?>
                            <div class="info-row"><span class="label">Avoiding</span><span class="value" style="color:var(--orange);">⚠ <?php echo htmlspecialchars($profile['allergies']); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notes & Tips -->
                <div class="panel">
                    <div class="panel-header"><h3>💡 Dietary Notes & Tips</h3></div>
                    <div class="panel-body">
                        <?php
                        $notes = explode("\n", $rec['notes']);
                        foreach ($notes as $note):
                            if (trim($note) === '') continue;
                        ?>
                        <div class="health-tip" style="margin-bottom:10px;">
                            <p><?php echo htmlspecialchars($note); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                    <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-outline" style="text-align:center; display:block; padding:12px; border:2px solid var(--navy); color:var(--navy);">
                        📝 Full Assessment
                    </a>
                    <a href="<?php echo SITE_URL; ?>/history.php" class="btn-outline" style="text-align:center; display:block; padding:12px; border:2px solid var(--navy); color:var(--navy);">
                        📋 View History
                    </a>
                </div>
            </div>
        </div>

        <div style="margin-top:20px; padding:16px; background:var(--light-grey); border-radius:var(--radius); text-align:center;">
            <p style="font-size:0.82rem; color:var(--text-light); margin:0;">
                ⚕️ <strong>Medical Disclaimer:</strong> FuelWise provides dietary guidance only and is not a substitute for professional medical advice. Always consult your doctor or registered dietitian before making changes to your diet or exercise.
            </p>
        </div>

        <!-- =============================================== -->
        <!-- QUICK UPDATE MODAL                               -->
        <!-- =============================================== -->
        <div id="quickUpdateModal" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>✏️ Quick Update & Regenerate</h3>
                    <button type="button" class="modal-close" onclick="document.getElementById('quickUpdateModal').style.display='none';">×</button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.88rem; color:var(--text-mid); margin-bottom:16px;">
                        All fields are optional. Leave a field empty to keep your saved value. We'll regenerate your 7-day plan based on any changes.
                    </p>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="quick_update">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Current Weight (kg)</label>
                                <input type="number" name="weight" class="form-control" step="0.1" min="20" max="400"
                                       placeholder="<?php echo $profile['weight'] ?? '—'; ?>">
                                <small class="hint">Saved: <?php echo $profile['weight'] ?? 'not set'; ?> kg</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Activity Level</label>
                                <select name="activity_level" class="form-control">
                                    <option value="">Keep saved (<?php echo str_replace('_',' ', $profile['activity_level'] ?? 'sedentary'); ?>)</option>
                                    <option value="sedentary">Sedentary</option>
                                    <option value="lightly_active">Lightly Active</option>
                                    <option value="moderately_active">Moderately Active</option>
                                    <option value="very_active">Very Active</option>
                                    <option value="extra_active">Extra Active</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Glucose (mg/dL)</label>
                                <input type="number" name="glucose_level" class="form-control" step="0.1" min="40" max="600"
                                       placeholder="<?php echo $profile['glucose_level'] ?? '—'; ?>">
                                <small class="hint">Saved: <?php echo $profile['glucose_level'] ?? 'not set'; ?></small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Blood Pressure</label>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="number" name="blood_pressure_systolic" class="form-control" min="60" max="250"
                                           placeholder="<?php echo $profile['blood_pressure_systolic'] ?? '120'; ?>" style="flex:1;">
                                    <span>/</span>
                                    <input type="number" name="blood_pressure_diastolic" class="form-control" min="30" max="150"
                                           placeholder="<?php echo $profile['blood_pressure_diastolic'] ?? '80'; ?>" style="flex:1;">
                                </div>
                                <small class="hint">Saved: <?php echo $profile['blood_pressure'] ?? 'not set'; ?></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Dietary Preference</label>
                                <select name="dietary_preference" class="form-control">
                                    <option value="">Keep saved</option>
                                    <option value="none">No Specific Preference</option>
                                    <option value="vegetarian">Vegetarian</option>
                                    <option value="vegan">Vegan</option>
                                    <option value="halal">Halal</option>
                                    <option value="gluten_free">Gluten-Free</option>
                                    <option value="pescatarian">Pescatarian</option>
                                    <option value="low_carb">Low Carb</option>
                                </select>
                                <small class="hint">Saved: <?php echo str_replace('_',' ', $profile['dietary_preference'] ?? 'none'); ?></small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cultural Cuisine</label>
                                <select name="cultural_background" class="form-control">
                                    <option value="">Keep saved</option>
                                    <option value="western">Western</option>
                                    <option value="indian">Indian</option>
                                    <option value="asian">Asian</option>
                                    <option value="mediterranean">Mediterranean</option>
                                    <option value="african">African</option>
                                    <option value="latin">Latin</option>
                                </select>
                                <small class="hint">Saved: <?php echo ucfirst($profile['cultural_background'] ?? 'western'); ?></small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Allergies / Foods to Avoid</label>
                            <textarea name="allergies" class="form-control" rows="2"
                                      placeholder="e.g. peanuts, shellfish"><?php echo htmlspecialchars($profile['allergies'] ?? ''); ?></textarea>
                            <small class="hint">Leave as-is to keep current allergies. Clear to remove all.</small>
                        </div>

                        <div class="modal-actions">
                            <button type="button" class="btn-outline" onclick="
                                document.getElementById('quickUpdateModal').style.display='none';
                                document.querySelector('form[data-action=\'regenerate_simple\']').submit();
                            ">
                                ⏭️ Skip — Use Saved Profile
                            </button>
                            <button type="submit" class="btn-primary">💾 Update & Regenerate</button>
                        </div>
                    </form>

                    <form method="POST" data-action="regenerate_simple" style="display:none;">
                        <input type="hidden" name="action" value="regenerate_simple">
                    </form>
                </div>
            </div>
        </div>

        <script>
        // Day tab switching
        document.querySelectorAll('.day-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.day-content').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                const day = this.dataset.day;
                document.querySelector(`.day-content[data-day-content="${day}"]`).classList.add('active');
            });
        });

        // Jump to today on load
        (function () {
            const today = <?php echo (int)date('N'); ?>;
            const todayTab = document.querySelector(`.day-tab[data-day="${today}"]`);
            if (todayTab) todayTab.click();
        })();

        // Close modal on background click
        document.getElementById('quickUpdateModal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
        </script>

        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
