<?php
$adminTitle = 'Food Database';
require_once '../includes/db.php';
requireAdmin();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $conn->query("DELETE FROM foods WHERE id=".(int)$_GET['delete']);
    setFlash('success','Food deleted.');
    redirect('admin/foods.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name     = sanitize($_POST['name']??'');
        $category = sanitize($_POST['category']??'');
        $calories = (float)($_POST['calories']??0);
        $protein  = (float)($_POST['protein']??0);
        $carbs    = (float)($_POST['carbohydrates']??0);
        $fat      = (float)($_POST['fat']??0);
        $fiber    = (float)($_POST['fiber']??0);
        $gi       = (int)($_POST['glycemic_index']??0);
        $desc     = sanitize($_POST['description']??'');
        $culture  = sanitize($_POST['cultural_origin']??'universal');
        $isDiab   = isset($_POST['is_diabetes_safe'])?1:0;
        $isIbs    = isset($_POST['is_ibs_safe'])?1:0;
        $isVeg    = isset($_POST['is_vegetarian'])?1:0;
        $isVegan  = isset($_POST['is_vegan'])?1:0;
        $isHalal  = isset($_POST['is_halal'])?1:0;
        if (empty($name)||empty($category)) { $error='Name and category are required.'; }
        else {
            $stmt = $conn->prepare("INSERT INTO foods (name,category,calories,protein,carbohydrates,fat,fiber,glycemic_index,description,cultural_origin,is_diabetes_safe,is_ibs_safe,is_vegetarian,is_vegan,is_halal) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssdddddiissiiiii",$name,$category,$calories,$protein,$carbs,$fat,$fiber,$gi,$desc,$culture,$isDiab,$isIbs,$isVeg,$isVegan,$isHalal);
            $stmt->execute(); $stmt->close();
            setFlash('success','Food added to database.');
            redirect('admin/foods.php');
        }
    }
}

$foods = $conn->query("SELECT * FROM foods ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
include 'header.php';
?>
<div class="admin-page-title"><h2>Food Database</h2><span class="badge badge-blue"><?php echo count($foods); ?> Items</span></div>
<?php if ($error): ?><div class="alert alert-error"><span>✗</span> <?php echo $error; ?></div><?php endif; ?>
<div class="admin-two-col">
  <!-- Add Food Form -->
  <div class="admin-form-card">
    <h3>➕ Add New Food</h3>
    <form method="POST"><input type="hidden" name="action" value="add">
      <div class="form-group"><label class="form-label">Food Name <span>*</span></label><input type="text" name="name" class="form-control" placeholder="e.g. Grilled Salmon" required></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Category <span>*</span></label>
          <select name="category" class="form-control" required>
            <option value="">Select</option>
            <option value="breakfast">Breakfast</option><option value="lunch">Lunch</option>
            <option value="dinner">Dinner</option><option value="snack">Snack</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Cultural Origin</label>
          <select name="cultural_origin" class="form-control">
            <option value="universal">Universal</option><option value="western">Western</option>
            <option value="indian">Indian</option><option value="asian">Asian</option><option value="mediterranean">Mediterranean</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Calories</label><input type="number" name="calories" class="form-control" step="0.1" placeholder="kcal"></div>
        <div class="form-group"><label class="form-label">Protein (g)</label><input type="number" name="protein" class="form-control" step="0.1"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Carbs (g)</label><input type="number" name="carbohydrates" class="form-control" step="0.1"></div>
        <div class="form-group"><label class="form-label">Fat (g)</label><input type="number" name="fat" class="form-control" step="0.1"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Fiber (g)</label><input type="number" name="fiber" class="form-control" step="0.1"></div>
        <div class="form-group"><label class="form-label">Glycemic Index</label><input type="number" name="glycemic_index" class="form-control" min="0" max="100"></div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <?php foreach (['is_diabetes_safe'=>'Diabetes Safe','is_ibs_safe'=>'IBS Safe','is_vegetarian'=>'Vegetarian','is_vegan'=>'Vegan','is_halal'=>'Halal'] as $field=>$label): ?>
        <label style="display:flex;align-items:center;gap:6px;font-size:0.88rem;cursor:pointer;"><input type="checkbox" name="<?php echo $field; ?>" style="accent-color:var(--green);"> <?php echo $label; ?></label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn-primary btn-block">Add Food</button>
    </form>
  </div>

  <!-- Food List -->
  <div class="admin-table-wrap" style="max-height:600px;overflow-y:auto;">
    <div class="admin-table-header"><div class="admin-search"><span>🔍</span><input type="text" id="tableSearch" placeholder="Search foods..."></div></div>
    <table>
      <thead><tr><th>Name</th><th>Category</th><th>Calories</th><th>D</th><th>I</th><th></th></tr></thead>
      <tbody id="tableBody">
        <?php foreach ($foods as $f): ?>
        <tr>
          <td style="font-size:0.88rem;"><?php echo htmlspecialchars($f['name']); ?></td>
          <td><span class="badge badge-blue" style="font-size:0.72rem;"><?php echo ucfirst($f['category']); ?></span></td>
          <td style="font-size:0.85rem;"><?php echo $f['calories']; ?></td>
          <td><?php echo $f['is_diabetes_safe']?'✅':'—'; ?></td>
          <td><?php echo $f['is_ibs_safe']?'✅':'—'; ?></td>
          <td><a href="?delete=<?php echo $f['id']; ?>" class="action-btn delete btn-delete-confirm">✕</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'footer.php'; ?>
