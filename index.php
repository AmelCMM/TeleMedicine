<?php $pageTitle = APP_NAME . ' — Healthcare in Your Pocket'; ?>

<section class="hero">
    <div class="hero-content">
        <div class="hero-tagline">Telemedicine for Zambia</div>
        <h1>Healthcare in <span style="color:var(--primary-500);">Your Pocket</span></h1>
        <p>Connect with a licensed doctor from anywhere in Zambia. Consult via chat, voice, or video &mdash; no app download needed.</p>
        <div class="hero-actions">
            <a href="/register?role=patient" class="btn btn-accent btn-lg">Get started as patient</a>
            <a href="/patient/find-doctor" class="btn btn-secondary btn-lg">Find a doctor</a>
        </div>
    </div>
    <div class="hero-visual">
        <span style="color:var(--primary-500);font-family:var(--font-display);font-weight:700;font-size:var(--text-2xl);">Consultation Preview</span>
    </div>
</section>

<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-number">2 400+</div>
        <div class="stat-desc">Registered doctors</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">18 000+</div>
        <div class="stat-desc">Patients served</div>
    </div>
    <div class="stat-item">
        <div class="stat-number">4</div>
        <div class="stat-desc">Provinces covered</div>
    </div>
</div>

<section class="how-it-works" id="how-it-works">
    <h2 style="font-size: var(--text-3xl);">How it works</h2>
    <p style="color:var(--text-secondary);margin-top:var(--space-2);">Three simple steps to get the care you need</p>
    <div class="steps-grid">
        <div>
            <div class="step-number">1</div>
            <h3 class="step-title">Find a doctor</h3>
            <p class="step-desc">Browse our network of licensed doctors by specialization, availability, and fee.</p>
        </div>
        <div>
            <div class="step-number">2</div>
            <h3 class="step-title">Book a consultation</h3>
            <p class="step-desc">Choose your preferred time and consultation type &mdash; chat, voice, or video.</p>
        </div>
        <div>
            <div class="step-number">3</div>
            <h3 class="step-title">Get care</h3>
            <p class="step-desc">Connect with your doctor, receive e-prescriptions, and access your health records.</p>
        </div>
    </div>
</section>

<section class="features-section">
    <div>
        <h2 style="font-size:var(--text-3xl);margin-bottom:var(--space-4);">Everything you need in one place</h2>
        <p style="color:var(--text-secondary);margin-bottom:var(--space-8);">From booking to prescriptions, manage your healthcare journey seamlessly.</p>
        <div class="features-list">
            <div class="feature-item">
                <?= icon('message-square') ?>
                <div>
                    <div class="feature-item-title">Chat with your doctor</div>
                    <div class="feature-item-desc">Send messages and get responses for non-urgent concerns.</div>
                </div>
            </div>
            <div class="feature-item">
                <?= icon('video') ?>
                <div>
                    <div class="feature-item-title">Video consultations</div>
                    <div class="feature-item-desc">Face-to-face video calls from the comfort of your home.</div>
                </div>
            </div>
            <div class="feature-item">
                <?= icon('file-text') ?>
                <div>
                    <div class="feature-item-title">E-prescriptions</div>
                    <div class="feature-item-desc">Digital prescriptions with QR codes for easy pharmacy verification.</div>
                </div>
            </div>
            <div class="feature-item">
                <?= icon('calendar') ?>
                <div>
                    <div class="feature-item-title">Appointment scheduling</div>
                    <div class="feature-item-desc">Book and manage appointments based on doctor availability.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="features-visual">
        <div style="display:flex;align-items:center;gap:var(--space-4);margin-bottom:var(--space-6);">
            <span class="avatar avatar-lg" style="background-color:var(--primary-100);color:var(--primary-500);font-weight:700;font-size:var(--text-xl);">D</span>
            <div>
                <div style="font-weight:700;">Dr. Miriam Banda</div>
                <div style="font-size:var(--text-sm);color:var(--primary-500);">General Practitioner</div>
            </div>
            <span style="margin-left:auto;font-size:var(--text-xs);color:var(--success);background-color:var(--success-bg);padding:2px 8px;border-radius:var(--radius-full);">Available today</span>
        </div>
        <div style="background-color:var(--surface-alt);border-radius:var(--radius);padding:var(--space-4);display:flex;gap:var(--space-3);align-items:center;">
            <?= icon('message-square') ?>
            <span style="font-size:var(--text-sm);color:var(--text-secondary);">I have a persistent cough and fever for the past 3 days.</span>
        </div>
        <div style="background-color:var(--primary-500);border-radius:var(--radius);padding:var(--space-4);margin-top:var(--space-3);display:flex;gap:var(--space-3);align-items:flex-start;color:white;max-width:80%;margin-left:auto;">
            <span style="font-size:var(--text-sm);">Let me review your symptoms. Have you taken any medication so far?</span>
        </div>
    </div>
</section>

<section class="for-doctors" id="for-doctors">
    <h2>Join as a healthcare professional</h2>
    <p>Reach more patients, manage your schedule, and issue e-prescriptions &mdash; all from one platform.</p>
    <a href="/register?role=doctor" class="btn btn-accent btn-lg">Register as a doctor</a>
</section>
