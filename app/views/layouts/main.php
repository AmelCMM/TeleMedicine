<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f4f8;
            color: #1a1a2e;
            min-height: 100vh;
        }

        /* Mobile-first nav */
        nav {
            background: #0077b6;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav .logo {
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            text-decoration: none;
        }

        nav .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            font-size: 0.9rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            width: 100%;
        }

        .btn-primary {
            background: #0077b6;
            color: white;
        }

        .btn-success {
            background: #2dc653;
            color: white;
        }

        .btn-danger {
            background: #e63946;
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #0077b6;
            color: #0077b6;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #d1d9e0;
            border-radius: 8px;
            font-size: 1rem;
            background: #f8fafc;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0077b6;
            background: white;
        }

        /* Alerts */
        .alert {
            padding: 0.9rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .alert-error {
            background: #fde8e8;
            color: #c62828;
            border-left: 4px solid #e63946;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2dc653;
        }

        .alert-info {
            background: #e3f2fd;
            color: #0d47a1;
            border-left: 4px solid #0077b6;
        }

        /* Grid — 2 columns on wider screens */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 600px) {
            .grid-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Emergency strip */
        .emergency-strip {
            background: #e63946;
            color: white;
            padding: 0.7rem 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .emergency-strip a {
            color: white;
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <!-- Emergency strip — always visible -->
    <div class="emergency-strip">
        🚨 Emergency? <a href="/emergency/nearest">Find nearest hospital now</a> &nbsp;|&nbsp; Hotline: <a href="tel:991">991</a>
    </div>

    <nav>
        <a href="/" class="logo">🏥 TeleMed Zambia</a>
        <div class="nav-links">
            <a href="/doctors">Doctors</a>
            <a href="/appointments">Appointments</a>
            <a href="/login">Login</a>
        </div>
    </nav>

    <div class="container">
        <?= $content ?? '' ?>
    </div>

    <footer>
        &copy; <?= date('Y') ?> TeleMed Zambia &mdash; Bringing healthcare closer to you.
    </footer>

</body>

</html>