<?php $pageTitle = APP_NAME . ' — Healthcare for Zambia'; ?>

<section class="hero animate-fade">
    <div class="container" style="display:grid; grid-template-columns: 1fr 1fr; gap:var(--space-12); align-items:center; height:100%;">
        <div class="hero-content">
            <div class="hero-tagline" style="text-transform:uppercase; font-size:12px; font-weight:700; color:var(--primary-500); letter-spacing:0.1em; margin-bottom:var(--space-4);">Telemedicine for Zambia</div>
            <h1 style="margin-bottom:var(--space-4);">Healthcare made <span style="color:var(--primary-500);">simple.</span></h1>
            <p style="font-size:1.125rem; max-width:480px; margin-bottom:var(--space-8);">Connect with licensed doctors from anywhere in Zambia. Consult securely via chat, voice, or video.</p>
            <div class="hero-actions" style="display:flex; gap:var(--space-4);">
                <a href="/register" class="btn btn-primary btn-lg">Get started</a>
                <a href="/patient/find-doctor" class="btn btn-secondary btn-lg">Find a doctor</a>
            </div>
        </div>
        <div class="hero-visual" style="display:flex; justify-content:center;">
            <div class="card" style="width: 320px; box-shadow: var(--shadow-lg); border: none;">
                <div class="card-header" style="background: var(--primary-500); color: white; border: none;">
                    <div style="display:flex; align-items:center; gap:var(--space-3);">
                        <div style="width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center;">
                            <?= icon('video') ?>
                        </div>
                        <div style="font-size: var(--text-sm); font-weight: 500;">Live Consultation</div>
                    </div>
                </div>
                <div class="card-body" style="padding: var(--space-8); text-align: center;">
                    <div style="width:80px; height:80px; border-radius:50%; background:var(--primary-100); margin: 0 auto var(--space-4); display:flex; align-items:center; justify-content:center; font-size: 2rem; color: var(--primary-500); font-weight: 700;">
                        D
                    </div>
                    <h4 style="margin-bottom: 4px;">Dr. Sarah Mwamba</h4>
                    <p style="font-size: var(--text-xs); margin-bottom: var(--space-6);">General Practitioner</p>
                    <div style="display:flex; justify-content: center; gap: var(--space-4);">
                        <div style="width:40px; height:40px; border-radius:50%; background:var(--gray-100); display:flex; align-items:center; justify-content:center; color: var(--gray-600);">
                            <?= icon('phone') ?>
                        </div>
                        <div style="width:40px; height:40px; border-radius:50%; background:var(--gray-100); display:flex; align-items:center; justify-content:center; color: var(--gray-600);">
                            <?= icon('message-square') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="stats-bar" style="background:var(--gray-50); border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:var(--space-10) 0;">
    <div class="container" style="display:grid; grid-template-columns: repeat(3, 1fr); text-align:center;">
        <div class="stat-item">
            <div class="stat-number" style="font-size:2.5rem; font-weight:700; color:var(--primary-500);">2 400+</div>
            <div class="stat-desc" style="font-size:0.875rem; color:var(--text-muted);">Licensed Doctors</div>
        </div>
        <div class="stat-item" style="border-left:1px solid var(--border); border-right:1px solid var(--border);">
            <div class="stat-number" style="font-size:2.5rem; font-weight:700; color:var(--primary-500);">18k+</div>
            <div class="stat-desc" style="font-size:0.875rem; color:var(--text-muted);">Patients Served</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" style="font-size:2.5rem; font-weight:700; color:var(--primary-500);">24/7</div>
            <div class="stat-desc" style="font-size:0.875rem; color:var(--text-muted);">Medical Support</div>
        </div>
    </div>
</div>

<section class="how-it-works" id="how-it-works" style="padding:var(--space-20) 0;">
    <div class="container">
        <h2 style="text-align: center; margin-bottom: var(--space-4);">How it works</h2>
        <p style="text-align: center; margin-bottom: var(--space-12); max-width:600px; margin-left:auto; margin-right:auto;">Quality healthcare in three simple steps.</p>

        <div class="steps-grid" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:var(--space-10);">
            <div class="step-card" style="text-align:center;">
                <div class="step-number" style="width:48px; height:48px; border-radius:50%; background:var(--primary-100); color:var(--primary-500); display:flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto var(--space-4);">1</div>
                <h3>Search</h3>
                <p>Browse through our verified directory of healthcare professionals.</p>
            </div>
            <div class="step-card" style="text-align:center;">
                <div class="step-number" style="width:48px; height:48px; border-radius:50%; background:var(--primary-100); color:var(--primary-500); display:flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto var(--space-4);">2</div>
                <h3>Book</h3>
                <p>Pick a time that works for you and choose your consultation method.</p>
            </div>
            <div class="step-card" style="text-align:center;">
                <div class="step-number" style="width:48px; height:48px; border-radius:50%; background:var(--primary-100); color:var(--primary-500); display:flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto var(--space-4);">3</div>
                <h3>Consult</h3>
                <p>Connect with your doctor securely and get the care you need.</p>
            </div>
        </div>
    </div>
</section>

<section class="features-section" style="padding:var(--space-20) 0; background:var(--gray-50);">
    <div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-16); align-items: center;">
        <div>
            <h2 style="margin-bottom:var(--space-4);">Everything you need for your health</h2>
            <p style="margin-bottom: var(--space-10);">A complete digital healthcare suite designed for accessibility and trust.</p>

            <div class="features-list" style="display:flex; flex-direction:column; gap:var(--space-8);">
                <div class="feature-item" style="display:flex; gap:var(--space-4);">
                    <div style="width:48px; height:48px; background:var(--surface); border-radius:var(--radius); display:flex; align-items:center; justify-content:center; color:var(--primary-500); box-shadow:var(--shadow-sm);"><?= icon('video') ?></div>
                    <div>
                        <h4 style="margin-bottom:4px;">Video & Voice Calls</h4>
                        <p style="margin:0; font-size:0.875rem;">Face-to-face consultations from anywhere.</p>
                    </div>
                </div>
                <div class="feature-item" style="display:flex; gap:var(--space-4);">
                    <div style="width:48px; height:48px; background:var(--surface); border-radius:var(--radius); display:flex; align-items:center; justify-content:center; color:var(--success); box-shadow:var(--shadow-sm);"><?= icon('file-text') ?></div>
                    <div>
                        <h4 style="margin-bottom:4px;">Digital Prescriptions</h4>
                        <p style="margin:0; font-size:0.875rem;">Instant prescriptions valid at registered pharmacies.</p>
                    </div>
                </div>
                <div class="feature-item" style="display:flex; gap:var(--space-4);">
                    <div style="width:48px; height:48px; background:var(--surface); border-radius:var(--radius); display:flex; align-items:center; justify-content:center; color:var(--warning); box-shadow:var(--shadow-sm);"><?= icon('heart') ?></div>
                    <div>
                        <h4 style="margin-bottom:4px;">Health Records</h4>
                        <p style="margin:0; font-size:0.875rem;">Secure access to your medical history 24/7.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="features-visual" style="display:flex; justify-content:center;">
            <div class="card" style="padding: var(--space-10); border: none; box-shadow: var(--shadow-lg); width:100%; max-width:400px;">
                <div style="display:flex; align-items:center; gap:var(--space-4); margin-bottom: var(--space-8);">
                    <div style="width:48px; height:48px; border-radius:50%; background:var(--primary-100); display:flex; align-items:center; justify-content:center; color:var(--primary-500); font-weight:700;">M</div>
                    <div>
                        <div style="font-weight:600; color:var(--text);">Medical Summary</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">Patient Record • Confirmed</div>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                    <div style="height:8px; width:100%; background:var(--gray-100); border-radius:var(--radius-full);"></div>
                    <div style="height:8px; width:85%; background:var(--gray-100); border-radius:var(--radius-full);"></div>
                    <div style="height:8px; width:70%; background:var(--gray-100); border-radius:var(--radius-full);"></div>
                    <div style="height:8px; width:90%; background:var(--gray-100); border-radius:var(--radius-full); margin-top:var(--space-2);"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="for-doctors" id="for-doctors" style="padding:var(--space-20) 0; background:var(--primary-700); color:white; text-align:center;">
    <div class="container">
        <h2 style="color:white; margin-bottom:var(--space-4);">Are you a healthcare professional?</h2>
        <p style="color:rgba(255,255,255,0.8); max-width:600px; margin:0 auto var(--space-10);">Join Zambia's leading telemedicine network and extend your reach to thousands of patients.</p>
        <a href="/register?role=doctor" class="btn btn-secondary btn-lg" style="background:white; color:var(--primary-700); border:none;">Register as Provider</a>
    </div>
</section>

<style>
.hero { height: 80vh; min-height: 600px; display: flex; align-items: center; }
</style>
