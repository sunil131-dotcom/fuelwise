// FuelWise — Main JavaScript

document.addEventListener('DOMContentLoaded', function () {

    // ---- Sticky Navbar ----
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        });
    }

    // ---- Hamburger Menu ----
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            hamburger.classList.toggle('active');
        });
        document.addEventListener('click', (e) => {
            if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('open');
                hamburger.classList.remove('active');
            }
        });
    }

    // ---- Password Toggle ----
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.previousElementSibling;
            if (input && input.type === 'password') {
                input.type = 'text';
                this.textContent = '🙈';
            } else if (input) {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    });

    // ---- Password Strength ----
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function () {
            const val = this.value;
            let strength = 0;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;
            strengthBar.className = 'password-strength-bar';
            if (val.length === 0) { strengthBar.style.width = '0'; return; }
            if (strength <= 1) strengthBar.classList.add('strength-weak');
            else if (strength <= 3) strengthBar.classList.add('strength-medium');
            else strengthBar.classList.add('strength-strong');
        });
    }

    // ---- OTP Input Navigation ----
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function () {
            if (this.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            combineOTP();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const paste = e.clipboardData.getData('text').slice(0, 6);
            paste.split('').forEach((char, i) => {
                if (otpInputs[i]) otpInputs[i].value = char;
            });
            combineOTP();
        });
    });
    function combineOTP() {
        const combined = document.getElementById('otpCombined');
        if (combined) {
            combined.value = Array.from(otpInputs).map(i => i.value).join('');
        }
    }

    // ---- OTP Resend Timer ----
    const timerEl = document.getElementById('resendTimer');
    const resendLink = document.getElementById('resendLink');
    if (timerEl) {
        let seconds = 60;
        const interval = setInterval(() => {
            seconds--;
            timerEl.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                if (resendLink) {
                    resendLink.style.display = 'inline';
                    timerEl.parentElement.style.display = 'none';
                }
            }
        }, 1000);
    }

    // ---- Condition Selector ----
    document.querySelectorAll('.condition-option').forEach(option => {
        option.addEventListener('click', function () {
            document.querySelectorAll('.condition-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // ---- Auto-dismiss Alerts ----
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            alert.style.transition = 'all 0.4s ease';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // ---- BMI Calculator ----
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiDisplay = document.getElementById('bmiDisplay');
    if (weightInput && heightInput && bmiDisplay) {
        function calcBMI() {
            const w = parseFloat(weightInput.value);
            const h = parseFloat(heightInput.value) / 100;
            if (w > 0 && h > 0) {
                const bmi = (w / (h * h)).toFixed(1);
                bmiDisplay.value = bmi;
                const bmiCat = document.getElementById('bmiCategory');
                if (bmiCat) {
                    if (bmi < 18.5) bmiCat.textContent = 'Underweight';
                    else if (bmi < 25) bmiCat.textContent = 'Normal weight';
                    else if (bmi < 30) bmiCat.textContent = 'Overweight';
                    else bmiCat.textContent = 'Obese';
                }
            }
        }
        weightInput.addEventListener('input', calcBMI);
        heightInput.addEventListener('input', calcBMI);
    }

    // ---- Admin Sidebar Toggle (Mobile) ----
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('open');
        });
    }

    // ---- Table Search ----
    const searchInput = document.getElementById('tableSearch');
    const tableBody = document.getElementById('tableBody');
    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            tableBody.querySelectorAll('tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    }

    // ---- Confirm Delete ----
    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ---- Animate stat numbers ----
    document.querySelectorAll('.stat-value, .admin-stat h3').forEach(el => {
        const target = parseInt(el.textContent);
        if (!isNaN(target) && target > 0) {
            let current = 0;
            const step = Math.ceil(target / 40);
            const timer = setInterval(() => {
                current = Math.min(current + step, target);
                el.textContent = current;
                if (current >= target) clearInterval(timer);
            }, 30);
        }
    });

    // ---- Scroll Reveal ----
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.feature-card, .step-card, .condition-card, .stat-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(el);
    });
});
