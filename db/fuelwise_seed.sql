-- ============================================================
-- FuelWise — Curated Meals & Exercises Seed Data
-- Import this AFTER fuelwise.sql
-- ============================================================
USE fuelwise;

-- ============================================================
-- CURATED MEALS
-- Hand-curated complete meals for all 5 conditions
-- ============================================================
INSERT IGNORE INTO foods (name, category, calories, protein, carbohydrates, fat, fiber, sugar, sodium, potassium, glycemic_index,
    is_diabetes_safe, is_ibs_safe, is_hypertension_safe, is_prediabetes_safe, is_weight_loss_safe,
    is_vegetarian, is_vegan, is_halal, is_kosher, is_gluten_free,
    ingredients, cultural_origin, description) VALUES

-- ============ BREAKFAST ============
('Oatmeal with Berries', 'breakfast', 250, 8, 45, 4, 6, 8, 120, 250, 55, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0,
    'oats,blueberries,strawberries,milk', 'western', 'Whole oats topped with fresh blueberries — high fiber, low GI'),
('Scrambled Eggs with Spinach', 'breakfast', 220, 16, 6, 14, 2, 2, 280, 400, 0, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1,
    'eggs,spinach,olive oil', 'western', 'Two scrambled eggs with sautéed spinach — protein-rich, low carb'),
('Greek Yogurt Parfait', 'breakfast', 230, 18, 28, 5, 3, 18, 85, 300, 35, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1,
    'greek yogurt,berries,honey,almonds', 'western', 'Greek yogurt layered with berries and almonds'),
('Idli with Sambar', 'breakfast', 200, 8, 38, 2, 4, 2, 280, 320, 40, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'rice,lentils,turmeric,mustard seeds', 'indian', 'Steamed rice cakes with lentil soup — gentle and nutritious'),
('Vegetable Poha', 'breakfast', 210, 5, 38, 5, 3, 2, 200, 280, 45, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'poha,peas,carrots,mustard seeds', 'indian', 'Flattened rice with vegetables — light and filling'),
('Banana Oat Smoothie', 'breakfast', 220, 6, 42, 4, 4, 18, 60, 450, 52, 0, 1, 1, 0, 1, 1, 1, 1, 1, 0,
    'banana,oats,almond milk', 'universal', 'Blended banana with oats and almond milk'),
('Boiled Egg with Toast', 'breakfast', 260, 14, 28, 10, 3, 3, 340, 200, 55, 1, 1, 0, 1, 1, 1, 0, 1, 1, 0,
    'eggs,whole grain bread', 'western', 'Two boiled eggs with whole grain toast'),
('Congee with Ginger', 'breakfast', 180, 5, 36, 2, 1, 1, 200, 180, 70, 0, 1, 1, 0, 1, 1, 1, 1, 1, 1,
    'rice,ginger,green onions', 'asian', 'Plain rice porridge — very gentle on the gut'),
('Avocado Toast', 'breakfast', 280, 8, 30, 15, 8, 3, 320, 500, 40, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0,
    'bread,avocado,lemon,olive oil', 'mediterranean', 'Whole grain toast with smashed avocado'),
('Chia Pudding', 'breakfast', 220, 8, 24, 12, 10, 10, 50, 220, 30, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'chia seeds,almond milk,berries,vanilla', 'western', 'Chia seeds with almond milk — very high fiber'),
('Steel-Cut Oats with Walnuts', 'breakfast', 270, 9, 40, 8, 7, 4, 100, 280, 42, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0,
    'steel cut oats,walnuts,cinnamon', 'western', 'Steel-cut oats with walnuts and cinnamon'),
('Tofu Scramble', 'breakfast', 200, 18, 10, 10, 3, 2, 300, 350, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'tofu,turmeric,bell peppers,onion', 'asian', 'Vegan alternative to scrambled eggs'),
('Smoked Salmon on Rye', 'breakfast', 280, 20, 30, 8, 4, 3, 640, 300, 50, 1, 1, 0, 1, 1, 0, 0, 1, 1, 0,
    'salmon,rye bread,cream cheese,dill', 'western', 'Smoked salmon on rye bread'),
('Vegetable Upma', 'breakfast', 230, 6, 38, 6, 4, 3, 220, 320, 48, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0,
    'semolina,vegetables,mustard seeds', 'indian', 'South Indian semolina breakfast with vegetables'),

-- ============ LUNCH ============
('Grilled Chicken Salad', 'lunch', 320, 35, 12, 10, 4, 3, 450, 550, 20, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'chicken,lettuce,tomato,cucumber,olive oil', 'western', 'Grilled chicken with mixed greens'),
('Quinoa Buddha Bowl', 'lunch', 340, 14, 42, 12, 7, 5, 290, 520, 35, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'quinoa,chickpeas,vegetables,tahini', 'mediterranean', 'Quinoa with roasted vegetables and tahini'),
('Dal Khichdi', 'lunch', 290, 12, 48, 5, 6, 2, 320, 400, 45, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'rice,lentils,ghee,turmeric', 'indian', 'Lentil and rice porridge — gentle, wholesome'),
('Mediterranean Bowl', 'lunch', 380, 14, 38, 18, 8, 6, 390, 600, 35, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0,
    'chickpeas,tomato,cucumber,feta,olive oil', 'mediterranean', 'Hummus, falafel, tabbouleh bowl'),
('Lentil Soup', 'lunch', 280, 16, 40, 4, 10, 4, 380, 680, 30, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1,
    'lentils,carrots,celery,cumin,lemon', 'mediterranean', 'Red lentil soup with cumin and lemon'),
('Vegetable Stir-Fry with Brown Rice', 'lunch', 360, 10, 58, 8, 6, 6, 460, 420, 50, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'brown rice,broccoli,bell peppers,carrots,ginger', 'asian', 'Mixed vegetables stir-fried with ginger'),
('Turkey Wrap', 'lunch', 330, 28, 35, 10, 5, 4, 620, 450, 45, 1, 1, 0, 1, 1, 0, 0, 1, 1, 0,
    'turkey,whole wheat wrap,lettuce,tomato', 'western', 'Lean turkey with whole wheat wrap'),
('Baked Salmon Salad', 'lunch', 380, 32, 16, 20, 5, 4, 290, 620, 0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'salmon,spinach,quinoa,lemon,olive oil', 'mediterranean', 'Omega-3 rich salmon with greens'),
('Vegetable Biryani', 'lunch', 380, 9, 60, 10, 6, 5, 440, 400, 50, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1,
    'basmati rice,mixed vegetables,spices,saffron', 'indian', 'Fragrant basmati rice with vegetables'),
('Chickpea Curry with Rice', 'lunch', 350, 14, 55, 8, 10, 6, 380, 500, 42, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1,
    'chickpeas,tomato,coconut milk,rice', 'indian', 'Protein-rich chickpea curry over rice'),
('Grilled Fish with Rice', 'lunch', 340, 30, 40, 8, 3, 2, 320, 500, 50, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'white fish,rice,lemon,herbs', 'asian', 'Grilled white fish with steamed rice'),
('Hummus Bowl', 'lunch', 350, 12, 42, 15, 9, 4, 520, 420, 30, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1,
    'chickpeas,tahini,olive oil,pita', 'mediterranean', 'Creamy hummus with fresh vegetables'),
('Tuna Salad', 'lunch', 280, 28, 10, 14, 3, 4, 480, 350, 15, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'tuna,lettuce,cucumber,olive oil', 'western', 'Fresh tuna salad with crisp vegetables'),
('Quinoa Salad', 'lunch', 310, 10, 44, 10, 6, 4, 260, 450, 35, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'quinoa,tomato,cucumber,parsley,lemon', 'mediterranean', 'Light quinoa tabbouleh-style salad'),

-- ============ DINNER ============
('Baked Salmon with Vegetables', 'dinner', 380, 40, 18, 15, 5, 4, 380, 680, 25, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'salmon,broccoli,carrots,olive oil', 'western', 'Baked salmon with steamed broccoli and carrots'),
('Grilled Fish Tacos', 'dinner', 350, 28, 32, 10, 4, 3, 420, 400, 45, 1, 1, 0, 1, 1, 0, 0, 1, 1, 0,
    'white fish,corn tortilla,cabbage,lime', 'western', 'Grilled white fish in corn tortillas'),
('Chicken and Vegetable Stir-Fry', 'dinner', 360, 32, 28, 12, 5, 6, 510, 500, 40, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'chicken,bell peppers,broccoli,ginger', 'asian', 'Lean chicken with vegetables and ginger'),
('Grilled Tofu with Quinoa', 'dinner', 340, 22, 40, 10, 6, 5, 350, 520, 30, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'tofu,quinoa,vegetables,tamari', 'asian', 'Grilled tofu with quinoa and greens'),
('Turkey Meatballs with Zoodles', 'dinner', 310, 32, 14, 14, 4, 6, 440, 700, 20, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'turkey,zucchini,tomato,basil', 'mediterranean', 'Lean turkey meatballs with zucchini noodles'),
('Paneer Bhurji with Roti', 'dinner', 360, 18, 40, 14, 5, 4, 400, 350, 50, 1, 0, 1, 1, 1, 1, 0, 1, 1, 0,
    'paneer,onion,tomato,whole wheat roti', 'indian', 'Scrambled cottage cheese with roti'),
('Vegetable Curry with Rice', 'dinner', 350, 10, 55, 10, 7, 6, 420, 480, 55, 0, 0, 1, 0, 1, 1, 1, 1, 1, 1,
    'mixed vegetables,coconut milk,rice', 'indian', 'Mixed vegetable curry with basmati rice'),
('Shrimp and Vegetable Skewers', 'dinner', 280, 28, 18, 10, 4, 6, 480, 540, 25, 1, 1, 1, 1, 1, 0, 0, 1, 0, 1,
    'shrimp,bell peppers,zucchini,onion', 'mediterranean', 'Grilled shrimp with vegetable skewers'),
('Baked Chicken with Sweet Potato', 'dinner', 380, 34, 38, 10, 6, 8, 380, 800, 50, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'chicken,sweet potato,green beans', 'western', 'Baked chicken with sweet potato'),
('Steamed Fish with Rice Congee', 'dinner', 300, 26, 35, 6, 2, 2, 320, 380, 65, 0, 1, 1, 0, 1, 0, 0, 1, 1, 1,
    'white fish,rice,ginger,scallions', 'asian', 'Steamed fish with rice congee'),
('Vegetable Lasagna', 'dinner', 360, 16, 42, 14, 7, 8, 560, 500, 45, 1, 0, 1, 1, 1, 1, 0, 1, 1, 0,
    'pasta,tomato,ricotta,spinach', 'mediterranean', 'Hearty vegetable lasagna'),
('Grilled Chicken with Quinoa', 'dinner', 380, 38, 38, 8, 5, 3, 360, 580, 35, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1,
    'chicken,quinoa,vegetables,herbs', 'mediterranean', 'Lean grilled chicken over quinoa'),
('Baked Cod with Asparagus', 'dinner', 300, 32, 12, 12, 4, 3, 380, 600, 15, 1, 0, 1, 1, 1, 0, 0, 1, 1, 1,
    'cod,asparagus,lemon,olive oil', 'mediterranean', 'Baked cod with fresh asparagus'),
('Palak Tofu with Roti', 'dinner', 320, 20, 36, 10, 6, 4, 380, 500, 40, 1, 0, 1, 1, 1, 1, 1, 1, 1, 0,
    'tofu,spinach,whole wheat roti', 'indian', 'Spinach tofu curry with roti'),

-- ============ SNACKS ============
('Mixed Nuts (small handful)', 'snack', 180, 5, 8, 15, 2, 2, 5, 200, 15, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'almonds,walnuts,cashews', 'universal', 'Small handful (28g) of unsalted mixed nuts'),
('Greek Yogurt', 'snack', 120, 10, 8, 4, 0, 6, 80, 240, 35, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1,
    'greek yogurt', 'western', 'Plain low-fat Greek yogurt'),
('Apple with Almond Butter', 'snack', 200, 4, 26, 10, 4, 18, 40, 220, 40, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'apple,almond butter', 'universal', 'Apple with 1 tbsp almond butter'),
('Carrot Sticks with Hummus', 'snack', 150, 5, 18, 7, 5, 4, 240, 280, 15, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'carrots,cucumber,hummus', 'mediterranean', 'Fresh veggie sticks with chickpea hummus'),
('Hard-Boiled Egg', 'snack', 80, 7, 1, 6, 0, 1, 70, 65, 0, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1,
    'eggs', 'universal', 'One hard-boiled egg — portable, high-protein'),
('Cottage Cheese with Pineapple', 'snack', 160, 14, 16, 3, 1, 14, 380, 160, 45, 1, 1, 0, 1, 1, 1, 0, 1, 1, 1,
    'cottage cheese,pineapple', 'western', 'Low-fat cottage cheese with fresh pineapple'),
('Roasted Chickpeas', 'snack', 140, 6, 22, 4, 5, 4, 260, 220, 30, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1,
    'chickpeas,olive oil,spices', 'mediterranean', 'Crispy roasted chickpeas'),
('Berry Smoothie', 'snack', 140, 3, 28, 2, 5, 20, 40, 280, 40, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'blueberries,strawberries,almond milk', 'universal', 'Mixed berry smoothie with almond milk'),
('Rice Cakes with Peanut Butter', 'snack', 170, 5, 22, 8, 2, 3, 120, 150, 70, 0, 1, 1, 0, 1, 1, 1, 1, 1, 1,
    'rice cakes,peanut butter', 'universal', 'Two plain rice cakes with peanut butter'),
('Edamame', 'snack', 120, 11, 10, 5, 5, 2, 30, 440, 18, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1,
    'soybeans,salt', 'asian', 'Steamed young soybeans — high protein'),
('Trail Mix', 'snack', 160, 5, 16, 10, 3, 10, 15, 220, 30, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'nuts,seeds,dried fruit', 'universal', 'Mixed nuts, seeds, and dried fruits'),
('Protein Smoothie', 'snack', 180, 20, 18, 4, 3, 12, 90, 380, 30, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1,
    'protein powder,banana,almond milk', 'universal', 'Post-workout protein shake'),
('Veggie Chips (baked)', 'snack', 130, 3, 20, 5, 3, 2, 200, 200, 45, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
    'beets,zucchini,olive oil', 'western', 'Baked vegetable chips');


-- ============================================================
-- EXERCISES
-- ============================================================
-- Sources:
--   - WHO Physical Activity Guidelines (2020)
--   - CDC Physical Activity Basics
--   - ADA Exercise Position Statement (2023)
--   - AHA Exercise Guidelines for Hypertension (2021)
--   - Ainsworth et al. (2011) Compendium of Physical Activities
--   - Kaggle Workout Dataset (gym exercises — for Weight Management only)
-- ============================================================

INSERT IGNORE INTO exercises (name, type, intensity, difficulty, duration_minutes, calories_per_30min, met_value,
    body_part, muscle_group, sets, reps,
    is_diabetes_safe, is_ibs_safe, is_hypertension_safe, is_prediabetes_safe, is_weight_loss,
    min_activity_level, equipment_needed, instructions, contraindications, source_citation) VALUES

-- ===== LIGHT CARDIO (safe for ALL conditions) =====
('Brisk Walking', 'cardio', 'moderate', 'beginner', 30, 120, 3.8, 'Full body', 'Legs, core', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Comfortable shoes',
    'Walk at a pace where you can talk but not sing. Maintain upright posture, swing arms naturally. Start with 10-15 min and gradually increase to 30 min.',
    'Stop immediately if you feel chest pain, dizziness, or severe shortness of breath.',
    'WHO Physical Activity Guidelines 2020; Ainsworth et al. 2011 MET=3.8'),

('Slow Walking', 'cardio', 'light', 'beginner', 30, 90, 2.8, 'Full body', 'Legs', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'None',
    'Leisurely walking pace, suitable for beginners or post-meal activity. A 10-15 min walk after meals helps lower blood glucose spikes.',
    'None for most people. Use a cane if needed for balance.',
    'ADA Exercise Position Statement 2023; Compendium MET=2.8'),

('Stationary Cycling (Light)', 'cardio', 'light', 'beginner', 30, 140, 4.0, 'Legs', 'Quadriceps, hamstrings', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Stationary bike',
    'Maintain a comfortable pedaling pace with low resistance. Keep back straight and shoulders relaxed.',
    'Not recommended during acute IBS flare-ups. Check with doctor if you have knee or hip issues.',
    'CDC Physical Activity Basics; AHA Exercise Guidelines 2021'),

('Swimming (Easy Pace)', 'cardio', 'moderate', 'beginner', 30, 180, 5.8, 'Full body', 'All major muscle groups', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Swimming pool',
    'Easy freestyle or breaststroke. Low-impact, full-body workout. Excellent for joint-friendly cardio.',
    'Avoid if you have open wounds or skin infections. Check with doctor if you have cardiovascular issues.',
    'AHA 2021 — recommended for hypertension and weight management'),

('Water Aerobics', 'cardio', 'moderate', 'beginner', 30, 170, 5.3, 'Full body', 'All major muscle groups', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Pool access',
    'Low-impact exercise in shallow water. Joint-friendly, ideal for beginners or those with joint pain.',
    'None typical. Pool should be warm enough to avoid cramps.',
    'AHA Exercise Guidelines 2021'),

('Leisurely Cycling Outdoors', 'cardio', 'moderate', 'beginner', 30, 210, 6.8, 'Legs', 'Quadriceps, glutes', '-', '-',
    1, 1, 1, 1, 1, 'lightly_active', 'Bicycle, helmet',
    'Easy outdoor cycling on flat terrain. Maintain steady breathing. Wear helmet and visible clothing.',
    'Avoid busy traffic. Start on flat paths. Stop if dizzy.',
    'CDC Physical Activity Basics; Compendium MET=6.8'),

('Elliptical Trainer (Light)', 'cardio', 'moderate', 'beginner', 30, 160, 5.0, 'Full body', 'Legs, core, arms', '-', '-',
    1, 1, 1, 1, 1, 'lightly_active', 'Elliptical machine',
    'Low-impact cardio that works both upper and lower body. Keep posture upright and stride smooth.',
    'Adjust resistance to keep effort moderate.',
    'AHA Exercise Guidelines 2021'),

('Rowing Machine (Easy)', 'cardio', 'moderate', 'intermediate', 30, 200, 6.0, 'Full body', 'Legs, back, arms', '-', '-',
    1, 1, 0, 1, 1, 'lightly_active', 'Rowing machine',
    'Full-body low-impact cardio. Drive with legs, pull with arms, maintain straight back.',
    'NOT recommended for hypertension (isometric effort). Avoid with back issues.',
    'Compendium 2011 MET=6.0'),

('Jogging (Slow)', 'cardio', 'moderate', 'intermediate', 30, 240, 7.0, 'Full body', 'Legs', '-', '-',
    1, 1, 0, 1, 1, 'moderately_active', 'Running shoes',
    'Slow steady jog on flat surface. Breathe rhythmically. Cool down with walking.',
    'NOT for uncontrolled hypertension. Hard on joints — avoid if knee issues.',
    'Compendium 2011 MET=7.0; WHO 2020'),

-- ===== YOGA & FLEXIBILITY (safe for ALL) =====
('Gentle Yoga', 'flexibility', 'light', 'beginner', 30, 90, 2.5, 'Full body', 'All', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Yoga mat',
    'Slow, mindful poses focusing on flexibility and breathing. Try Child Pose, Cat-Cow, gentle twists, and Corpse Pose.',
    'Avoid inverted poses if you have high blood pressure or glaucoma.',
    'Compendium 2011 MET=2.5; Recommended for IBS by British Dietetic Association'),

('Tai Chi', 'flexibility', 'light', 'beginner', 30, 120, 3.0, 'Full body', 'All', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'None',
    'Slow, flowing movements that improve balance, flexibility, and mental calm. Excellent for stress reduction.',
    'None for most people.',
    'Compendium 2011 MET=3.0; Recommended by CDC for older adults'),

('Stretching Routine', 'flexibility', 'light', 'beginner', 15, 45, 2.3, 'Full body', 'All', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'None',
    'Full-body stretching: hamstrings, calves, shoulders, neck. Hold each stretch for 20-30 seconds without bouncing.',
    'Never force a stretch. Stop if sharp pain.',
    'CDC Physical Activity Basics'),

('Pilates (Beginner)', 'strength', 'light', 'beginner', 30, 120, 3.0, 'Core', 'Core, back', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Yoga mat',
    'Low-impact exercises focusing on core strength, posture, and flexibility. Great for back pain prevention.',
    'Avoid forward folds if you have disc issues. Consult doctor.',
    'AHA 2021'),

('Chair Yoga', 'flexibility', 'light', 'beginner', 20, 60, 2.0, 'Full body', 'All', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Sturdy chair',
    'Gentle yoga poses performed while seated. Perfect for beginners, older adults, or those with mobility issues.',
    'None.',
    'CDC recommendations for older adults'),

-- ===== RESISTANCE BANDS / BODYWEIGHT (safe for ALL) =====
('Resistance Band Workout', 'strength', 'moderate', 'beginner', 30, 150, 3.5, 'Full body', 'All major groups', '2-3', '10-12',
    1, 1, 1, 1, 1, 'lightly_active', 'Resistance bands',
    'Full-body exercises with resistance bands: rows, squats, chest presses, shoulder raises. 10-12 reps per exercise.',
    'Check bands for wear before each use to avoid snapping.',
    'WHO 2020 — resistance training guidelines'),

('Wall Push-Ups', 'strength', 'light', 'beginner', 10, 40, 2.5, 'Upper body', 'Chest, shoulders', '2-3', '8-12',
    1, 1, 1, 1, 1, 'sedentary', 'Wall',
    'Stand arm length from a wall, place hands on wall, perform push-up motion. Beginner-friendly version.',
    'Stop if any shoulder or wrist pain.',
    'CDC muscle-strengthening recommendations'),

('Bodyweight Squats', 'strength', 'moderate', 'beginner', 15, 80, 3.5, 'Legs', 'Quadriceps, glutes', '3', '8-12',
    1, 1, 1, 1, 1, 'lightly_active', 'None',
    'Stand with feet shoulder-width apart, lower as if sitting in a chair, keep knees behind toes. Start with half-squats.',
    'Avoid if knee pain. Use chair for support if needed.',
    'CDC muscle-strengthening; WHO 2020'),

('Seated Leg Extensions', 'strength', 'light', 'beginner', 10, 45, 2.5, 'Legs', 'Quadriceps', '2-3', '10-12',
    1, 1, 1, 1, 1, 'sedentary', 'Chair',
    'Sit tall in a chair, extend one leg out straight, hold 2 seconds, lower. Alternate legs.',
    'None.',
    'CDC recommendations for older adults'),

('Glute Bridges', 'strength', 'light', 'beginner', 10, 60, 3.0, 'Core', 'Glutes, lower back', '3', '10-15',
    1, 1, 1, 1, 1, 'sedentary', 'Mat',
    'Lie on back, knees bent, lift hips off floor squeezing glutes. Hold 2 seconds, lower.',
    'None.',
    'AHA Exercise Guidelines 2021'),

-- ===== DANCE & RECREATION =====
('Dancing (Moderate)', 'cardio', 'moderate', 'beginner', 30, 180, 5.5, 'Full body', 'All', '-', '-',
    1, 1, 1, 1, 1, 'lightly_active', 'None',
    'Ballroom, salsa, or simple dance routines. Fun way to get cardio. Move to music for 20-30 min.',
    'Choose dances with controlled movements if you have balance issues.',
    'Compendium 2011 MET=5.5'),

('Gardening (Light)', 'cardio', 'light', 'beginner', 45, 150, 3.5, 'Full body', 'Core, arms, legs', '-', '-',
    1, 1, 1, 1, 1, 'sedentary', 'Garden tools',
    'Weeding, planting, light digging. Combines movement, fresh air, and stress relief.',
    'Use knee pads. Avoid heavy lifting if you have back issues.',
    'Compendium 2011 MET=3.5; WHO 2020'),

('Hiking (Easy Trail)', 'cardio', 'moderate', 'beginner', 45, 270, 6.0, 'Full body', 'Legs, core', '-', '-',
    1, 1, 1, 1, 1, 'lightly_active', 'Comfortable shoes, water',
    'Gentle trail walking in nature. Carry water. Start with short, flat trails.',
    'Avoid steep trails if you have knee or balance issues.',
    'Compendium 2011 MET=6.0'),

-- ===== GYM/STRENGTH (WEIGHT MANAGEMENT ONLY — moderately_active+) =====
-- Source: Kaggle Workout Dataset
('Incline Dumbbell Press', 'strength', 'vigorous', 'intermediate', 20, 160, 5.0, 'Chest', 'Upper Chest', '3-4', '8-12',
    0, 0, 0, 0, 1, 'moderately_active', 'Dumbbells, incline bench',
    'On incline bench, press dumbbells up from chest level. Control the descent.',
    'NOT recommended for hypertension — heavy lifting raises BP. Avoid if shoulder injury.',
    'Kaggle Workout Dataset (gym training)'),

('Dumbbell Rows', 'strength', 'vigorous', 'intermediate', 20, 160, 5.0, 'Back', 'Upper Back', '3-4', '8-12',
    0, 0, 0, 0, 1, 'moderately_active', 'Dumbbells, bench',
    'Support one knee on bench, row dumbbell to hip, squeeze shoulder blade.',
    'NOT recommended for hypertension. Avoid with lower back issues.',
    'Kaggle Workout Dataset'),

('Barbell Squats', 'strength', 'vigorous', 'advanced', 20, 200, 5.0, 'Legs', 'Quadriceps, glutes', '3-4', '8-12',
    0, 0, 0, 0, 1, 'very_active', 'Barbell, squat rack',
    'Bar across upper back, descend until thighs parallel to floor, drive up through heels.',
    'NOT recommended for hypertension or back issues. Have a spotter.',
    'Kaggle Workout Dataset'),

('Dumbbell Curls', 'strength', 'moderate', 'intermediate', 15, 120, 4.0, 'Arms', 'Biceps', '3-4', '8-12',
    0, 1, 0, 0, 1, 'moderately_active', 'Dumbbells',
    'Stand tall, curl dumbbells up with controlled motion, lower slowly.',
    'NOT recommended for hypertension. Avoid if elbow tendonitis.',
    'Kaggle Workout Dataset'),

('Triceps Pushdowns', 'strength', 'moderate', 'intermediate', 15, 120, 4.0, 'Arms', 'Triceps', '3-4', '8-12',
    0, 1, 0, 0, 1, 'moderately_active', 'Cable machine',
    'Face cable station, push bar down extending arms, control return.',
    'NOT recommended for hypertension.',
    'Kaggle Workout Dataset'),

('Lat Pulldown', 'strength', 'moderate', 'intermediate', 15, 130, 4.5, 'Back', 'Lats', '3-4', '10-12',
    0, 1, 0, 0, 1, 'moderately_active', 'Cable machine',
    'Pull bar down to upper chest, keep back slightly arched.',
    'NOT recommended for hypertension.',
    'Kaggle Workout Dataset'),

('Leg Press', 'strength', 'vigorous', 'intermediate', 15, 170, 5.0, 'Legs', 'Quadriceps, glutes', '3-4', '8-12',
    0, 1, 0, 0, 1, 'moderately_active', 'Leg press machine',
    'Push platform with feet shoulder-width apart, do not lock knees.',
    'NOT recommended for hypertension.',
    'Kaggle Workout Dataset'),

('Plank', 'strength', 'moderate', 'beginner', 5, 30, 3.0, 'Abs', 'Core', '3', '30-60 sec',
    1, 1, 0, 1, 1, 'lightly_active', 'Mat',
    'Hold a straight-line position on forearms and toes. Maintain tight core. Start 20 sec, build to 60 sec.',
    'Avoid if you have high blood pressure (isometric holds can spike BP).',
    'CDC core-strengthening; Compendium 2011'),

('Crunches', 'strength', 'moderate', 'intermediate', 10, 80, 3.8, 'Abs', 'Upper Core', '3-4', '10-15',
    1, 0, 0, 1, 1, 'lightly_active', 'Mat',
    'Lie on back, hands behind head, lift shoulders off floor by contracting abs.',
    'NOT recommended for IBS (abdominal pressure). Avoid with neck issues.',
    'Kaggle Workout Dataset');

-- ============================================================
-- Seed complete. You should now have:
--   ~45 curated meals
--   ~28 exercises (20 light/cardio + 8 gym for weight management)
-- ============================================================
