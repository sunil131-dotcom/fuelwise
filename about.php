<?php
require_once 'includes/db.php';
$pageTitle = 'About';
include 'includes/header.php';
?>
<main>
<div class="page-header">
  <h1>About FuelWise</h1>
  <p>AI-powered personalised nutrition for people managing chronic conditions</p>
  <div class="breadcrumb"><a href="<?php echo SITE_URL; ?>/index.php">Home</a><span>›</span><span class="current">About</span></div>
</div>

<section class="section" style="background:var(--white);">
  <div class="section-container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;">
      <div>
        <span class="section-badge">Our Mission</span>
        <h2 style="margin:12px 0 20px;">Nutrition That Works for <em style="color:var(--green);">Your Condition</em></h2>
        <p style="margin-bottom:16px;">FuelWise is an AI-driven dietary recommendation platform built specifically for individuals managing Type 2 Diabetes and Irritable Bowel Syndrome (IBS). We believe that personalised nutrition — tailored to your health condition, body, culture, and preferences — can transform long-term health outcomes.</p>
        <p style="margin-bottom:16px;">Traditional dietary guidelines are often generic and fail to account for individual differences. FuelWise bridges this gap by combining machine learning, evidence-based nutrition science, and culturally inclusive food databases to deliver recommendations that are truly yours.</p>
        <p>We are committed to fairness, privacy, and accessibility — making AI-powered nutrition guidance available to everyone, regardless of cultural background or dietary tradition.</p>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <?php $cards = [['🤖','AI Engine','Machine learning models trained on validated clinical and nutritional datasets'],['🌍','Culturally Inclusive','Supports Western, Indian, Asian, Mediterranean, halal, kosher, and vegetarian diets'],['🔒','Privacy First','GDPR-compliant. Your health data is encrypted and never sold or shared'],['⚕️','Evidence-Based','Built on peer-reviewed research and validated dietary protocols']];
        foreach ($cards as [$icon,$title,$desc]): ?>
        <div class="feature-card" style="padding:20px;">
          <div class="feature-icon" style="width:44px;height:44px;font-size:1.3rem;"><?php echo $icon; ?></div>
          <h3 style="font-size:1rem;margin:10px 0 6px;"><?php echo $title; ?></h3>
          <p style="font-size:0.84rem;"><?php echo $desc; ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:var(--light-grey);">
  <div class="section-container">
    <div class="section-header">
      <span class="section-badge">The Technology</span>
      <h2>How the AI Works</h2>
      <p>FuelWise uses five validated datasets and multiple machine learning models to generate your personalised plan.</p>
    </div>
    <div class="features-grid">
      <?php $tech=[['🩸','Diabetes Risk Assessment','Trained on the Pima Indians Diabetes Database (768 clinical records). Uses XGBoost and Random Forest classifiers to assess diabetes risk from glucose, BMI, blood pressure, and insulin data.'],['📊','Calorie & BMR Calculation','Uses the Mifflin-St Jeor equation with activity multipliers to calculate your Basal Metabolic Rate and Total Daily Energy Expenditure — personalised to your age, weight, height, and gender.'],['🥗','Recipe Recommendation','Filters thousands of recipes from the Food.com dataset by your daily nutritional targets, health condition, and dietary preferences using content-based filtering.'],['🌿','IBS-Safe Meal Filtering','Applies Low-FODMAP dietary principles to identify gut-friendly meals, reducing IBS triggers while maintaining nutritional balance.'],['🍛','Cultural Food Database','Incorporates the Indian RDA diet dataset to recommend culturally appropriate foods for South Asian users, with nutritional deficiency analysis.'],['💡','Explainable Recommendations','Generates condition-specific dietary notes and guidance based on your health metrics, aligned with Diabetes UK, ADA, and Monash University Low-FODMAP protocols.']];
      foreach ($tech as [$icon,$title,$desc]): ?>
      <div class="feature-card">
        <div class="feature-icon"><?php echo $icon; ?></div>
        <h3><?php echo $title; ?></h3>
        <p><?php echo $desc; ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="background:var(--white);">
  <div class="section-container">
    <div class="section-header">
      <span class="section-badge">Research Foundation</span>
      <h2>Built on Peer-Reviewed Science</h2>
      <p>FuelWise draws on five peer-reviewed academic sources to ensure evidence-based recommendations.</p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <?php $refs=[['Metachew & Nemeon (2024)','Demonstrated 91.4% accuracy in AI-based metabolic prediction using CNN-LSTM hybrid deep learning with genomic and lifestyle data.','National Journal of Food Security'],['Tsolakidis et al. (2024)','Systematic review of 67 AI nutrition studies identifying key recommendation system architectures and data requirements.','Informatics (MDPI)'],['Verma et al. (2018)','Established systems biology framework for precision nutrition and identified infrastructure challenges in personalised dietary systems.','Frontiers in Nutrition'],['Coughlin et al. (2015)','Demonstrated smartphone dietary apps significantly improve dietary compliance, physical activity, and weight outcomes.','Literature Review'],['Brankovic & Hendrie (2025)','Comprehensive taxonomy of AI techniques for personalised nutrition including XAI, SHAP, and fairness considerations.','Proceedings of the Nutrition Society']];
      foreach ($refs as [$author,$summary,$journal]): ?>
      <div style="border:1px solid var(--border);border-radius:var(--radius);padding:20px;background:var(--off-white);">
        <div style="font-weight:700;color:var(--navy-dark);margin-bottom:6px;"><?php echo $author; ?></div>
        <p style="font-size:0.88rem;margin-bottom:8px;"><?php echo $summary; ?></p>
        <span class="badge badge-blue" style="font-size:0.74rem;"><?php echo $journal; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="section-container">
    <h2>Ready to Start Your Journey?</h2>
    <p>Create your free account and get your AI-powered personalised diet plan today.</p>
    <div class="cta-buttons">
      <?php if (isLoggedIn()): ?>
        <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-primary btn-lg">Go to Assessment</a>
      <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/register.php" class="btn-primary btn-lg">Create Free Account</a>
        <a href="<?php echo SITE_URL; ?>/login.php" class="btn-outline btn-lg">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</section>
</main>
<?php include 'includes/footer.php'; ?>
