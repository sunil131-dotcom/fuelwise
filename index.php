<?php
require_once 'includes/db.php';
$pageTitle = 'Home';
include 'includes/header.php';
?>

<main>

<!-- Hero -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-text">
            <span class="hero-badge">AI-Powered Nutrition Science</span>
            <h1>Eat Smart.<br><em>Live Better.</em></h1>
            <p>FuelWise uses artificial intelligence to deliver personalized diet and exercise recommendations for people managing <strong>diabetes, pre-diabetes, hypertension, IBS, and weight</strong>. Evidence-based. Tailored to you.</p>
            <div class="hero-cta">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-primary">Go to Assessment</a>
                    <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn-outline">My Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/register.php" class="btn-primary">Get Started Free</a>
                    <a href="<?php echo SITE_URL; ?>/about.php" class="btn-outline">Learn How It Works</a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="stat"><span>2</span><p>Conditions Supported</p></div>
                <div class="stat"><span>AI</span><p>Recommendation Engine</p></div>
                <div class="stat"><span>100%</span><p>Personalized Plans</p></div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="visual-card card1">
                <div class="card-icon">🥗</div>
                <div><p>Personalized Meal Plan</p><span>Generated just for you</span></div>
            </div>
            <div class="visual-card card2">
                <div class="card-icon">🩺</div>
                <div><p>Diabetes-Safe Choices</p><span>Low glycemic index focus</span></div>
            </div>
            <div class="visual-card card3">
                <div class="card-icon">🧬</div>
                <div><p>IBS-Friendly Recipes</p><span>Gut-health optimized</span></div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section" style="background: var(--white);">
    <div class="section-container">
        <div class="section-header">
            <span class="section-badge">Why FuelWise</span>
            <h2>Nutrition That Understands <em style="color:var(--green);">You</em></h2>
            <p>Our AI analyses your health profile, medical condition, and food preferences to build a plan that's truly yours.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>AI-Powered Recommendations</h3>
                <p>Our machine learning engine analyses your BMI, BMR, glucose levels, and health condition to generate scientifically backed meal plans.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🩸</div>
                <h3>Diabetes Management</h3>
                <p>Specifically designed for Type 2 Diabetes — focusing on low glycemic index foods, portion control, and blood sugar stability.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🫁</div>
                <h3>IBS-Friendly Diets</h3>
                <p>Low-FODMAP meal recommendations tailored to reduce IBS symptoms and promote long-term gut health and digestive comfort.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>Culturally Inclusive</h3>
                <p>Supports Western, Indian, Asian, and Mediterranean food preferences. Accommodates vegetarian, vegan, halal, and kosher diets.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Nutritional Tracking</h3>
                <p>Monitor your daily calorie, protein, carbohydrate, and fat targets. Track your recommendation history over time.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure & Private</h3>
                <p>Your health data is encrypted and stored securely. FuelWise is built with GDPR principles — your data is yours.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section how-it-works">
    <div class="section-container">
        <div class="section-header">
            <span class="section-badge">Simple Process</span>
            <h2>How FuelWise Works</h2>
            <p>Four simple steps to your personalized nutrition plan.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Create Account</h3>
                <p>Register with your basic details and create your secure FuelWise profile.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Health Assessment</h3>
                <p>Enter your health data — age, weight, height, condition, glucose levels, and food preferences.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>AI Analysis</h3>
                <p>Our AI calculates your BMI, BMR, and daily nutritional needs, then filters foods for your condition.</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3>Get Your Plan</h3>
                <p>Receive a complete personalised meal plan with breakfast, lunch, dinner, and snacks — saved to your dashboard.</p>
            </div>
        </div>
    </div>
</section>

<!-- Conditions -->
<section class="section" style="background: var(--white);">
    <div class="section-container">
        <div class="section-header">
            <span class="section-badge">Conditions We Support</span>
            <h2>Built for Your Health Needs</h2>
            <p>FuelWise is specifically designed for chronic conditions where diet has the greatest impact on your health outcomes.</p>
        </div>
        <div class="home-conditions-grid">
            <div class="home-condition-card diabetes">
                <span class="condition-badge">🩺</span>
                <h3>Type 2 Diabetes</h3>
                <p>Personalized meal plans focused on low glycemic index foods, balanced macronutrients, and blood sugar management. Reduce insulin resistance through evidence-based nutrition.</p>
                <div>
                    <span class="condition-tag">Low GI Foods</span>
                    <span class="condition-tag">Blood Sugar Control</span>
                    <span class="condition-tag">High Fiber</span>
                    <span class="condition-tag">Lean Proteins</span>
                    <span class="condition-tag">Portion Control</span>
                </div>
            </div>
            <div class="home-condition-card ibs">
                <span class="condition-badge">🌿</span>
                <h3>Irritable Bowel Syndrome</h3>
                <p>Low-FODMAP meal plans to reduce digestive discomfort, bloating, and IBS symptoms. Gut-friendly foods selected to support long-term digestive health and wellbeing.</p>
                <div>
                    <span class="condition-tag">Low-FODMAP</span>
                    <span class="condition-tag">Gut-Friendly</span>
                    <span class="condition-tag">Anti-Inflammatory</span>
                    <span class="condition-tag">Easy Digestion</span>
                    <span class="condition-tag">Probiotic Rich</span>
                </div>
            </div>
            <div class="home-condition-card prediabetes">
                <span class="condition-badge">🔵</span>
                <h3>Pre-Diabetes</h3>
                <p>Early intervention meal plans designed to reverse pre-diabetic markers through balanced blood sugar regulation, reduced refined carbohydrates, and sustainable dietary habits.</p>
                <div>
                    <span class="condition-tag">Blood Sugar Balance</span>
                    <span class="condition-tag">Low Refined Carbs</span>
                    <span class="condition-tag">High Fiber</span>
                    <span class="condition-tag">Whole Grains</span>
                    <span class="condition-tag">Preventive Nutrition</span>
                </div>
            </div>
            <div class="home-condition-card hypertension">
                <span class="condition-badge">🫀</span>
                <h3>Hypertension</h3>
                <p>DASH-inspired meal plans to help lower blood pressure naturally. Focus on reducing sodium, increasing potassium-rich foods, and heart-healthy nutrients to support cardiovascular health.</p>
                <div>
                    <span class="condition-tag">Low Sodium</span>
                    <span class="condition-tag">DASH Diet</span>
                    <span class="condition-tag">Heart-Healthy</span>
                    <span class="condition-tag">Potassium-Rich</span>
                    <span class="condition-tag">Magnesium Boost</span>
                </div>
            </div>
            <div class="home-condition-card weight_management">
                <span class="condition-badge">⚖️</span>
                <h3>Weight Management</h3>
                <p>Calorie-balanced, nutrient-dense meal plans tailored to your goals — whether losing, gaining, or maintaining weight. Sustainable plans that keep you full and energised.</p>
                <div>
                    <span class="condition-tag">Calorie Balanced</span>
                    <span class="condition-tag">Nutrient-Dense</span>
                    <span class="condition-tag">Satiety Focused</span>
                    <span class="condition-tag">Metabolism Support</span>
                    <span class="condition-tag">Sustainable Eating</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials / Stats -->
<section class="section" style="background: var(--light-grey);">
    <div class="section-container">
        <div class="section-header">
            <span class="section-badge">Backed by Research</span>
            <h2>Evidence-Based Nutrition</h2>
            <p>FuelWise is built on peer-reviewed research and validated datasets.</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(4,1fr); gap: 24px;">
            <div class="feature-card" style="text-align: center;">
                <div style="font-size:2.2rem; font-weight:900; color:var(--navy); font-family:'Playfair Display',serif;">422M</div>
                <p style="font-size:0.88rem; margin-top:8px;">People worldwide living with diabetes (WHO)</p>
            </div>
            <div class="feature-card" style="text-align: center;">
                <div style="font-size:2.2rem; font-weight:900; color:var(--green); font-family:'Playfair Display',serif;">91.4%</div>
                <p style="font-size:0.88rem; margin-top:8px;">Accuracy in AI-based dietary prediction models</p>
            </div>
            <div class="feature-card" style="text-align: center;">
                <div style="font-size:2.2rem; font-weight:900; color:var(--navy); font-family:'Playfair Display',serif;">1 in 7</div>
                <p style="font-size:0.88rem; margin-top:8px;">People globally affected by IBS at some point</p>
            </div>
            <div class="feature-card" style="text-align: center;">
                <div style="font-size:2.2rem; font-weight:900; color:var(--green); font-family:'Playfair Display',serif;">24%</div>
                <p style="font-size:0.88rem; margin-top:8px;">Improvement in glucose stability with AI dietary guidance</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="section-container">
        <h2>Ready to Take Control of Your Nutrition?</h2>
        <p>Join FuelWise and get your AI-powered personalized diet plan today. Free to use. No hidden costs.</p>
        <div class="cta-buttons">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/assessment.php" class="btn-primary btn-lg">Start My Assessment</a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/register.php" class="btn-primary btn-lg">Create Free Account</a>
                <a href="<?php echo SITE_URL; ?>/login.php" class="btn-outline btn-lg">Sign In</a>
            <?php endif; ?>
        </div>
        <p style="margin-top:24px; font-size:0.82rem; color:rgba(255,255,255,0.5);">⚕️ FuelWise is a dietary guidance tool. Always consult your healthcare provider before making changes to your diet.</p>
    </div>
</section>

</main>

<?php include 'includes/footer.php'; ?>
