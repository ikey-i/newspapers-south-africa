<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Publish\AuthController as PublishAuthController;
use App\Controllers\Publish\DashboardController as PublishDashboardController;
use App\Controllers\Publish\PasswordController as PublishPasswordController;
use App\Controllers\BrowseController;
use App\Controllers\EditionController;
use App\Controllers\HomeController;
use App\Controllers\NewspaperController;
use App\Controllers\PageController;
use App\Controllers\SearchController;
use App\Controllers\SitemapController;
use App\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/healthz', [HomeController::class, 'health']);
$router->get('/sitemap.xml', [SitemapController::class, 'xml']);
$router->get('/robots.txt', [SitemapController::class, 'robots']);
$router->get('/ads.txt', [SitemapController::class, 'adsTxt']);

$router->get('/search', [SearchController::class, 'index']);
$router->get('/privacy', [PageController::class, 'privacy']);
$router->get('/terms', [PageController::class, 'terms']);
$router->get('/list-your-newspaper', [PageController::class, 'listYourNewspaper']);

// ---------------------------------------------------------------------------
// Public directory
// ---------------------------------------------------------------------------
$router->get('/newspapers', [BrowseController::class, 'index']);
$router->get('/editions', [EditionController::class, 'latest']);
$router->get('/province/{slug}', [BrowseController::class, 'province']);
$router->get('/section/{slug}', [BrowseController::class, 'section']);

$router->get('/paper/{slug}', [NewspaperController::class, 'show']);
$router->get('/paper/{slug}/article/{article}', [NewspaperController::class, 'article']);
$router->get('/paper/{slug}/editions', [EditionController::class, 'archive']);
$router->get('/paper/{slug}/editions/{id}', [EditionController::class, 'show']);
$router->get('/paper/{slug}/editions/{id}/pdf', [EditionController::class, 'pdf']);

// ---------------------------------------------------------------------------
// Publisher accounts
// ---------------------------------------------------------------------------
$router->get('/publish/register', [PublishAuthController::class, 'showRegister']);
$router->post('/publish/register', [PublishAuthController::class, 'register']);
$router->get('/publish/register/sent', [PublishAuthController::class, 'registerSent']);
$router->get('/publish/verify', [PublishAuthController::class, 'verify']);
$router->post('/publish/verify/resend', [PublishAuthController::class, 'resendVerify']);
$router->get('/publish/login', [PublishAuthController::class, 'showLogin']);
$router->post('/publish/login', [PublishAuthController::class, 'login']);
$router->post('/publish/logout', [PublishAuthController::class, 'logout']);
$router->get('/publish/forgot', [PublishPasswordController::class, 'showForgot']);
$router->post('/publish/forgot', [PublishPasswordController::class, 'forgot']);
$router->get('/publish/reset', [PublishPasswordController::class, 'showReset']);
$router->post('/publish/reset', [PublishPasswordController::class, 'reset']);

$router->get('/publish', [PublishDashboardController::class, 'index']);

// ---------------------------------------------------------------------------
// Admin
// ---------------------------------------------------------------------------
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);

$router->get('/admin/settings', [AdminSettingsController::class, 'edit']);
$router->post('/admin/settings', [AdminSettingsController::class, 'update']);

return $router;
