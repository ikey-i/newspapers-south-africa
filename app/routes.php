<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
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
// Admin
// ---------------------------------------------------------------------------
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);

$router->get('/admin/settings', [AdminSettingsController::class, 'edit']);
$router->post('/admin/settings', [AdminSettingsController::class, 'update']);

return $router;
