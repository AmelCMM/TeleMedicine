<?php

// Role constants
define('ROLE_PATIENT', 'patient');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_PHARMACY', 'pharmacy');
define('ROLE_ADMIN', 'admin');

// Appointment statuses
define('APPT_PENDING', 'pending');
define('APPT_CONFIRMED', 'confirmed');
define('APPT_IN_PROGRESS', 'in_progress');
define('APPT_COMPLETED', 'completed');
define('APPT_CANCELLED', 'cancelled');

// Prescription statuses
define('RX_ACTIVE', 'active');
define('RX_DISPENSED', 'dispensed');
define('RX_EXPIRED', 'expired');
define('RX_CANCELLED', 'cancelled');

// Consultation types
define('CONS_TYPE_CHAT', 'chat');
define('CONS_TYPE_VOICE', 'voice');
define('CONS_TYPE_VIDEO', 'video');

// Payment statuses
define('PAY_PENDING', 'pending');
define('PAY_COMPLETED', 'completed');
define('PAY_FAILED', 'failed');
define('PAY_REFUNDED', 'refunded');

// Payment methods
define('PAY_MTN_MOMO', 'mtn_momo');
define('PAY_AIRTEL_MONEY', 'airtel_money');
define('PAY_ZAMTEL_KWACHA', 'zamtel_kwacha');
define('PAY_CARD', 'card');

// Notification types
define('NOTIF_APPOINTMENT', 'appointment');
define('NOTIF_CONSULTATION', 'consultation');
define('NOTIF_PRESCRIPTION', 'prescription');
define('NOTIF_PAYMENT', 'payment');
define('NOTIF_SYSTEM', 'system');

// File upload limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);

// Prescription expiry (days)
define('RX_EXPIRY_DAYS', 30);

// Appointment cancellation window (hours)
define('CANCEL_WINDOW_HOURS', 2);
