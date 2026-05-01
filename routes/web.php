<?php

/** @var Router $router */

$router->get('/', function () {
    view('patient/home', ['pageTitle' => 'TeleMed Zambia — Healthcare in Your Pocket']);
});

$router->get('/login', function () {
    view('auth/login', ['pageTitle' => 'Login']);
});

$router->get('/register', function () {
    view('auth/register', ['pageTitle' => 'Create Account']);
});

$router->get('/doctors', function () {
    view('doctor/list', ['pageTitle' => 'Find a Doctor']);
});

$router->get('/doctors/:id', function ($id) {
    view('doctor/profile', ['pageTitle' => 'Doctor Profile', 'doctorId' => $id]);
});

$router->get('/emergency/nearest', function () {
    view('emergency/nearest', ['pageTitle' => 'Find Nearest Hospital']);
});