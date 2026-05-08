<?php
$adminTitle = 'Exercise Database';
require_once '../includes/db.php';
requireAdmin();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $conn->query("DELETE FROM exercises WHERE id=".(int)$_GET['delete']);
    setFlash('success','Exercise deleted.');
    redirect('admin/exercises.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name       = sanitize($_POST['name'] ?? '');
    $type       = sanitize($_POST['type'] ?? 'cardio');
    $intensity  = sanitize($_POST['intensity'] ?? 'light');
    $difficulty = sanitize($_POST['difficulty'] ?? 'beginner');
    $duration   = (int)($_POST['duration_minutes'] ?? 30);
    $calories   = (int)($_POST['calories_per_30min'] ?? 100);
    $met        = (float)($_POST['met_value'] ?? 3.0);
    $bodyPart   = sanitize($_POST['body_part'] ?? '');
    $muscle     = sanitize($_POST['muscle_group'] ?? '');
    $sets       = sanitize($_POST['sets'] ?? '');
    $reps       = sanitize($_POST['reps'] ?? '');
    $diab = isset($_POST['is_diabetes_safe'])     ? 1 : 0;
    $ibs  = isset($_POST['is_ibs_safe'])          ? 1 : 0;
    $hyp  = isset($_POST['is_hypertension_safe']) ? 1 : 0;
    $pre  = isset($_POST['is_prediabetes_safe'])  ? 1 : 0;
    $wl   = isset($_POST['is_weight_loss'])       ? 1 : 0;
    $minActivity = sanitize($_POST['min_activity_level'] ?? 'sedentary');
    $equip       = sanitize($_POST['equipment_needed'] ?? 'None');
    $instr       = sanitize($_POST['instructions'] ?? '');
    $contra      = sanitize($_POST['contraindications'] ?? '');
    $source      = sanitize($_POST['source_citation'] ?? '');

    if (empty($name)) {
        $error = 'Exercise name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO exercises
            (name, type, intensity, difficulty, duration_minutes, calories_per_30min, met_value,
             body_part, muscle_group, sets, reps,
             is_diabetes_safe, is_ibs_safe, is_hypertension_safe, is_prediabetes_safe, is_weight_loss,
             min_activity_level, equipment_needed, instructions, contraindications, source_citation)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssiidssssiiiiisssss",
            $name, $type, $intensity, $difficulty, $duration, $calories, $met,
            $bodyPart, $muscle, $sets, $reps,
            $diab, $ibs, $hyp, $pre, $wl,
            $minActivity, $equip, $instr, $contra, $source);
        if ($stmt->execute()) {
            setFlash('success', 'Exercise added to database.');
            $stmt->close();
            redirect('admin/exercises.php');
        } else {
            $error = 'Failed to save: ' . $stmt->error;
            $stmt->close();
        }
    }
}

$exercises = $conn->query("SELECT * FROM exercises ORDER BY type, intensity, name")->fetch_all(MYSQLI_ASSOC);
include 'header.php';
?>
<div class="admin-page-title">
    <h2>Exercise Database</h2>
    <span class="badge badge-blue"><?php echo count($exercises); ?> Exercises</span>
</div>
<?php if ($error): ?><div class="alert alert-error"><span>✗</span> <?php echo $error; ?></div><?php endif; ?>

<div class="admin-two-col">
    <!-- Add Exercise Form -->
    <div class="admin-form-card">
        <h3>➕ Add New Exercise</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label class="form-label">Exercise Name <span>*</span></label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Brisk Walking" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        <option value="cardio">Cardio</option>
                        <option value="strength">Strength</option>
                        <option value="flexibility">Flexibility</option>
                        <option value="balance">Balance</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Intensity</label>
                    <select name="intensity" class="form-control">
                        <option value="light">Light</option>
                        <option value="moderate">Moderate</option>
                        <option value="vigorous">Vigorous</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-control">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Duration (min)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="30" min="5" max="240">
                </div>
                <div class="form-group">
                    <label class="form-label">Calories/30min</label>
                    <input type="number" name="calories_per_30min" class="form-control" value="100">
                </div>
                <div class="form-group">
                    <label class="form-label">MET</label>
                    <input type="number" name="met_value" class="form-control" value="3.0" step="0.1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Body Part</label>
                    <input type="text" name="body_part" class="form-control" placeholder="e.g. Legs">
                </div>
                <div class="form-group">
                    <label class="form-label">Muscle Group</label>
                    <input type="text" name="muscle_group" class="form-control" placeholder="e.g. Quadriceps">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Sets</label>
                    <input type="text" name="sets" class="form-control" placeholder="e.g. 3-4">
                </div>
                <div class="form-group">
                    <label class="form-label">Reps</label>
                    <input type="text" name="reps" class="form-control" placeholder="e.g. 8-12">
                </div>
                <div class="form-group">
                    <label class="form-label">Min Activity Level</label>
                    <select name="min_activity_level" class="form-control">
                        <option value="sedentary">Sedentary</option>
                        <option value="lightly_active">Lightly Active</option>
                        <option value="moderately_active">Moderately Active</option>
                        <option value="very_active">Very Active</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Equipment Needed</label>
                <input type="text" name="equipment_needed" class="form-control" placeholder="None, Dumbbells, Yoga mat...">
            </div>
            <div class="form-group">
                <label class="form-label">Instructions</label>
                <textarea name="instructions" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Contraindications</label>
                <textarea name="contraindications" class="form-control" rows="2"
                          placeholder="When NOT to do this exercise"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Source / Citation</label>
                <input type="text" name="source_citation" class="form-control" placeholder="e.g. WHO Guidelines 2020">
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                <?php foreach ([
                    'is_diabetes_safe'     => 'Diabetes Safe',
                    'is_ibs_safe'          => 'IBS Safe',
                    'is_hypertension_safe' => 'Hypertension Safe',
                    'is_prediabetes_safe'  => 'Pre-Diabetes Safe',
                    'is_weight_loss'       => 'Weight Loss'
                ] as $field => $label): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                    <input type="checkbox" name="<?php echo $field; ?>" style="accent-color:var(--green);" checked> <?php echo $label; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn-primary btn-block">Add Exercise</button>
        </form>
    </div>

    <!-- Exercise List -->
    <div class="admin-table-wrap" style="max-height:700px;overflow-y:auto;">
        <div class="admin-table-header">
            <h3 style="font-size:1rem;font-weight:700;color:var(--navy-dark);margin:0;">All Exercises</h3>
        </div>
        <table>
            <thead>
                <tr><th>Name</th><th>Type</th><th>Int.</th><th>Safe for</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($exercises as $ex): ?>
                <tr>
                    <td style="font-size:0.85rem;">
                        <strong><?php echo htmlspecialchars($ex['name']); ?></strong>
                        <?php if ($ex['body_part']): ?>
                            <br><small style="color:var(--text-light);"><?php echo htmlspecialchars($ex['body_part']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-blue" style="font-size:0.72rem;"><?php echo ucfirst($ex['type']); ?></span></td>
                    <td><span class="badge badge-<?php echo $ex['intensity']==='vigorous'?'red':($ex['intensity']==='moderate'?'orange':'green'); ?>" style="font-size:0.72rem;"><?php echo ucfirst($ex['intensity']); ?></span></td>
                    <td style="font-size:0.82rem;">
                        <?php
                        $flags = [];
                        if ($ex['is_diabetes_safe']) $flags[] = '🩸';
                        if ($ex['is_ibs_safe']) $flags[] = '🌿';
                        if ($ex['is_hypertension_safe']) $flags[] = '🫀';
                        if ($ex['is_weight_loss']) $flags[] = '⚖️';
                        echo implode(' ', $flags);
                        ?>
                    </td>
                    <td><a href="?delete=<?php echo $ex['id']; ?>" class="action-btn delete btn-delete-confirm">✕</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>
