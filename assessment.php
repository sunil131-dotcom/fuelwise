<?php
require_once 'includes/db.php';
requireLogin();

$userId = $_SESSION['user_id'];
$error = '';

// Fetch existing profile (for pre-fill)
$stmt = $conn->prepare("SELECT * FROM health_profiles WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // -- Required physical metrics --
    $age     = (int)   ($_POST['age']    ?? 0);
    $weight  = (float) ($_POST['weight'] ?? 0);
    $height  = (float) ($_POST['height'] ?? 0);

    // -- Multi-select conditions --
    $conditions = $_POST['conditions'] ?? [];
    if (!is_array($conditions)) $conditions = [$conditions];
    $conditions = array_filter(array_unique(array_map('sanitize', $conditions)));

    // -- Lifestyle --
    $activity_level      = sanitize($_POST['activity_level']      ?? 'sedentary');
    $dietary_preference  = sanitize($_POST['dietary_preference']  ?? 'none');
    $cultural_background = sanitize($_POST['cultural_background'] ?? 'western');
    $allergies           = sanitize($_POST['allergies']           ?? '');
    $weight_goal         = sanitize($_POST['weight_goal']         ?? 'maintain');
    $target_weight       = !empty($_POST['target_weight']) ? (float)$_POST['target_weight'] : null;

    // -- Optional clinical values --
    $glucose   = !empty($_POST['glucose_level'])          ? (float)$_POST['glucose_level']           : null;
    $bpSys     = !empty($_POST['blood_pressure_systolic'])  ? (int)$_POST['blood_pressure_systolic']    : null;
    $bpDia     = !empty($_POST['blood_pressure_diastolic']) ? (int)$_POST['blood_pressure_diastolic']   : null;
    $insulin   = !empty($_POST['insulin_level'])          ? (float)$_POST['insulin_level']           : null;
    $cholesterol = !empty($_POST['cholesterol_total'])    ? (float)$_POST['cholesterol_total']       : null;
    $bpString  = ($bpSys && $bpDia) ? "{$bpSys}/{$bpDia}" : null;

    // -- Validation --
    if ($age < 10 || $age > 120)          $error = 'Please enter a valid age (10-120).';
    elseif ($weight < 20 || $weight > 400) $error = 'Please enter a valid weight in kg (20-400).';
    elseif ($height < 80 || $height > 250) $error = 'Please enter a valid height in cm (80-250).';
    elseif (empty($conditions))            $error = 'Please select at least one health concern.';
    elseif ($glucose !== null && ($glucose < 40 || $glucose > 600))           $error = 'Glucose level seems out of range (40-600 mg/dL). Leave blank if unknown.';
    elseif ($bpSys !== null && ($bpSys < 60 || $bpSys > 250))                 $error = 'Systolic BP seems out of range (60-250). Leave blank if unknown.';
    elseif ($bpDia !== null && ($bpDia < 30 || $bpDia > 150))                 $error = 'Diastolic BP seems out of range (30-150). Leave blank if unknown.';
    elseif ($insulin !== null && ($insulin < 1 || $insulin > 300))            $error = 'Insulin value seems out of range (1-300). Leave blank if unknown.';
    elseif ($cholesterol !== null && ($cholesterol < 50 || $cholesterol > 500)) $error = 'Cholesterol value seems out of range (50-500). Leave blank if unknown.';

    if (!$error) {
        // -- Determine primary condition_type for backward compat --
        if (count($conditions) === 1) {
            $condition_type = $conditions[0];
        } elseif (count($conditions) === 2 && in_array('diabetes', $conditions) && in_array('ibs', $conditions)) {
            $condition_type = 'both';
        } else {
            $condition_type = 'multiple';
        }
        $conditions_list = implode(',', $conditions);

        // Snapshot for analytics
        $oldSnapshot = $existing ? [
            'conditions_list'    => $existing['conditions_list'] ?? '',
            'weight'             => $existing['weight'],
            'glucose_level'      => $existing['glucose_level'],
            'blood_pressure'     => $existing['blood_pressure'],
            'dietary_preference' => $existing['dietary_preference'],
            'allergies'          => $existing['allergies'],
        ] : [];

        $newSnapshot = [
            'conditions_list'    => $conditions_list,
            'weight'             => $weight,
            'glucose_level'      => $glucose,
            'blood_pressure'     => $bpString,
            'dietary_preference' => $dietary_preference,
            'allergies'          => $allergies,
        ];

        if ($existing) {
            $stmt = $conn->prepare("UPDATE health_profiles SET
                age=?, weight=?, height=?, condition_type=?, conditions_list=?, activity_level=?,
                glucose_level=?, blood_pressure=?, blood_pressure_systolic=?, blood_pressure_diastolic=?,
                insulin_level=?, cholesterol_total=?, dietary_preference=?, cultural_background=?,
                allergies=?, weight_goal=?, target_weight=?
                WHERE user_id=?");
            $stmt->bind_param("iddsssdsiiddssssdi",
                $age, $weight, $height, $condition_type, $conditions_list, $activity_level,
                $glucose, $bpString, $bpSys, $bpDia,
                $insulin, $cholesterol, $dietary_preference, $cultural_background,
                $allergies, $weight_goal, $target_weight, $userId);
            $stmt->execute();
            $stmt->close();
            $updateType = 'full_assessment';
        } else {
            $stmt = $conn->prepare("INSERT INTO health_profiles
                (user_id, age, weight, height, condition_type, conditions_list, activity_level,
                 glucose_level, blood_pressure, blood_pressure_systolic, blood_pressure_diastolic,
                 insulin_level, cholesterol_total, dietary_preference, cultural_background,
                 allergies, weight_goal, target_weight)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iiddsssdsiiddssssd",
                $userId, $age, $weight, $height, $condition_type, $conditions_list, $activity_level,
                $glucose, $bpString, $bpSys, $bpDia,
                $insulin, $cholesterol, $dietary_preference, $cultural_background,
                $allergies, $weight_goal, $target_weight);
            $stmt->execute();
            $stmt->close();
            $updateType = 'full_assessment';
        }

        // Log the update for analytics
        logProfileUpdate($userId, $updateType, $oldSnapshot, $newSnapshot);

        // Generate recommendation
        require_once __DIR__ . '/api/recommend.php';
        $profile = array_merge($_POST, [
            'condition_type'   => $condition_type,
            'conditions_list'  => $conditions_list,
            'glucose_level'    => $glucose,
            'blood_pressure'   => $bpString,
            'blood_pressure_systolic'  => $bpSys,
            'blood_pressure_diastolic' => $bpDia,
            'insulin_level'    => $insulin,
            'cholesterol_total'=> $cholesterol,
            'target_weight'    => $target_weight,
            'weight_goal'      => $weight_goal,
            'gender'           => $_SESSION['gender'] ?? 'male',
        ]);
        generateRecommendation($userId, $profile);

        setFlash('success', 'Your health profile has been updated! Here is your 7-day personalised plan.');
        redirect('recommendation.php');
    }
}

$profile   = $existing ?? [];
$pageTitle = 'Health Assessment';
$extraCSS  = 'assessment.css';
include 'includes/header.php';

// Parse existing conditions for pre-fill
$existingConditions = [];
if (!empty($profile['conditions_list'])) {
    $existingConditions = explode(',', $profile['conditions_list']);
} elseif (!empty($profile['condition_type'])) {
    if ($profile['condition_type'] === 'both') {
        $existingConditions = ['diabetes', 'ibs'];
    } elseif (!in_array($profile['condition_type'], ['multiple'])) {
        $existingConditions = [$profile['condition_type']];
    }
}
?>

<div class="inner-page">
    <div class="page-header">
        <h1>Health Assessment</h1>
        <p>Help us understand your health so we can personalise your nutrition and exercise plan.</p>
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
            <span>›</span>
            <span class="current">Assessment</span>
        </div>
    </div>

    <div class="inner-content">
        <?php if ($error): ?>
            <div class="alert alert-error"><span>✗</span> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="assessment-form">

            <!-- STEP 1: Physical Metrics -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">📏</div>
                    <div>
                        <h3>Your Physical Information</h3>
                        <p>Required to calculate your calorie and nutrient needs.</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Age <span class="req">*</span></label>
                        <input type="number" name="age" class="form-control" required min="10" max="120"
                               value="<?php echo $profile['age'] ?? ''; ?>" placeholder="e.g. 35">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight (kg) <span class="req">*</span></label>
                        <input type="number" name="weight" class="form-control" required step="0.1" min="20" max="400"
                               value="<?php echo $profile['weight'] ?? ''; ?>" placeholder="e.g. 70.5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Height (cm) <span class="req">*</span></label>
                        <input type="number" name="height" class="form-control" required step="0.1" min="80" max="250"
                               value="<?php echo $profile['height'] ?? ''; ?>" placeholder="e.g. 170">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Activity Level</label>
                        <select name="activity_level" class="form-control">
                            <?php
                            $activityLevels = [
                                'sedentary'         => 'Sedentary (little to no exercise)',
                                'lightly_active'    => 'Lightly Active (light exercise 1-3 days/wk)',
                                'moderately_active' => 'Moderately Active (exercise 3-5 days/wk)',
                                'very_active'       => 'Very Active (hard exercise 6-7 days/wk)',
                                'extra_active'      => 'Extra Active (hard physical job + daily exercise)'
                            ];
                            $current = $profile['activity_level'] ?? 'sedentary';
                            foreach ($activityLevels as $value => $label) {
                                $selected = $current === $value ? 'selected' : '';
                                echo "<option value='{$value}' {$selected}>{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Weight Goal</label>
                        <select name="weight_goal" class="form-control">
                            <?php
                            $goals = ['lose' => 'Lose Weight', 'maintain' => 'Maintain Current Weight', 'gain' => 'Gain Weight'];
                            $currentGoal = $profile['weight_goal'] ?? 'maintain';
                            foreach ($goals as $val => $lbl) {
                                $sel = $currentGoal === $val ? 'selected' : '';
                                echo "<option value='{$val}' {$sel}>{$lbl}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Target Weight (kg) <span class="optional">(optional)</span></label>
                        <input type="number" name="target_weight" class="form-control" step="0.1" min="20" max="400"
                               value="<?php echo $profile['target_weight'] ?? ''; ?>" placeholder="e.g. 65">
                    </div>
                </div>
            </div>

            <!-- STEP 2: Health Conditions -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">🩺</div>
                    <div>
                        <h3>Health Conditions</h3>
                        <p>Select all that apply. FuelWise tailors your plan to each condition.</p>
                    </div>
                </div>
                <div class="conditions-grid">
                    <?php
                    $conditions = [
                        'diabetes' => [
                            'icon' => '🩸', 'title' => 'Type 2 Diabetes',
                            'desc' => 'Low-GI foods, balanced macros, carb-aware planning.'
                        ],
                        'prediabetes' => [
                            'icon' => '🔵', 'title' => 'Pre-Diabetes',
                            'desc' => 'Prevent progression with fiber-rich, low-GI meals.'
                        ],
                        'ibs' => [
                            'icon' => '🌿', 'title' => 'IBS',
                            'desc' => 'Low-FODMAP friendly meals that are gentle on your gut.'
                        ],
                        'hypertension' => [
                            'icon' => '🫀', 'title' => 'High Blood Pressure',
                            'desc' => 'DASH-style eating: low sodium, high potassium.'
                        ],
                        'weight_management' => [
                            'icon' => '⚖️', 'title' => 'Weight Management',
                            'desc' => 'Calorie-controlled meals + exercise recommendations.'
                        ],
                    ];
                    foreach ($conditions as $code => $info):
                        $checked = in_array($code, $existingConditions) ? 'checked' : '';
                    ?>
                    <label class="condition-card">
                        <input type="checkbox" name="conditions[]" value="<?php echo $code; ?>" <?php echo $checked; ?>>
                        <div class="condition-content">
                            <div class="condition-icon"><?php echo $info['icon']; ?></div>
                            <h4><?php echo $info['title']; ?></h4>
                            <p><?php echo $info['desc']; ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="hint">💡 You can select multiple conditions — we'll combine their dietary rules safely.</p>
            </div>

            <!-- STEP 3: Clinical Values (OPTIONAL) -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">🧪</div>
                    <div>
                        <h3>Clinical Values <span class="optional">(all optional)</span></h3>
                        <p>Share only what you know. Leave blank if unsure — your doctor can tell you these.</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Fasting Glucose (mg/dL)</label>
                        <input type="number" name="glucose_level" class="form-control" step="0.1" min="40" max="600"
                               value="<?php echo $profile['glucose_level'] ?? ''; ?>" placeholder="e.g. 95">
                        <small class="hint">Normal: &lt;100 | Prediabetes: 100-125 | Diabetes: ≥126</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Blood Pressure</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="number" name="blood_pressure_systolic" class="form-control" min="60" max="250"
                                   value="<?php echo $profile['blood_pressure_systolic'] ?? ''; ?>" placeholder="120" style="flex:1;">
                            <span style="font-size:1.2rem;color:var(--text-light);">/</span>
                            <input type="number" name="blood_pressure_diastolic" class="form-control" min="30" max="150"
                                   value="<?php echo $profile['blood_pressure_diastolic'] ?? ''; ?>" placeholder="80" style="flex:1;">
                        </div>
                        <small class="hint">Normal: &lt;120/80 | High: ≥140/90</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fasting Insulin (μU/mL)</label>
                        <input type="number" name="insulin_level" class="form-control" step="0.1" min="1" max="300"
                               value="<?php echo $profile['insulin_level'] ?? ''; ?>" placeholder="e.g. 8">
                        <small class="hint">Normal: 2-25 μU/mL</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Total Cholesterol (mg/dL)</label>
                        <input type="number" name="cholesterol_total" class="form-control" step="0.1" min="50" max="500"
                               value="<?php echo $profile['cholesterol_total'] ?? ''; ?>" placeholder="e.g. 180">
                        <small class="hint">Desirable: &lt;200 mg/dL</small>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Preferences -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-icon">🍽️</div>
                    <div>
                        <h3>Dietary Preferences</h3>
                        <p>Help us pick meals you'll actually enjoy.</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Dietary Preference</label>
                        <select name="dietary_preference" class="form-control">
                            <?php
                            $diets = [
                                'none' => 'No Specific Preference', 'vegetarian' => 'Vegetarian',
                                'vegan' => 'Vegan', 'halal' => 'Halal', 'kosher' => 'Kosher',
                                'gluten_free' => 'Gluten-Free', 'pescatarian' => 'Pescatarian',
                                'low_carb' => 'Low Carb'
                            ];
                            $currDiet = $profile['dietary_preference'] ?? 'none';
                            foreach ($diets as $v => $l) {
                                $s = $currDiet === $v ? 'selected' : '';
                                echo "<option value='{$v}' {$s}>{$l}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cultural Cuisine</label>
                        <select name="cultural_background" class="form-control">
                            <?php
                            $cultures = [
                                'western' => 'Western / European', 'indian' => 'Indian / South Asian',
                                'asian' => 'East & Southeast Asian', 'mediterranean' => 'Mediterranean',
                                'african' => 'African', 'latin' => 'Latin American', 'other' => 'Other'
                            ];
                            $currCult = $profile['cultural_background'] ?? 'western';
                            foreach ($cultures as $v => $l) {
                                $s = $currCult === $v ? 'selected' : '';
                                echo "<option value='{$v}' {$s}>{$l}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Allergies / Foods to Avoid <span class="optional">(optional)</span></label>
                    <textarea name="allergies" class="form-control" rows="2"
                              placeholder="e.g. peanuts, shellfish, lactose, gluten"><?php echo htmlspecialchars($profile['allergies'] ?? ''); ?></textarea>
                    <small class="hint">Separate by commas. We'll filter out any meal containing these ingredients.</small>
                </div>
            </div>

            <div style="text-align:center; margin-top:24px;">
                <button type="submit" class="btn-primary btn-lg">
                    🚀 Generate My Personalised Plan
                </button>
                <p style="font-size:0.82rem; color:var(--text-light); margin-top:12px;">
                    ⚕️ FuelWise provides dietary guidance and is not a substitute for medical advice.
                </p>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
