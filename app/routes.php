<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\NewspapersController as AdminNewspapersController;
use App\Controllers\Admin\PublishersController as AdminPublishersController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Publish\ArticlesController as PublishArticlesController;
use App\Controllers\Publish\AuthController as PublishAuthController;
use App\Controllers\Publish\DashboardController as PublishDashboardController;
use App\Controllers\Publish\EditionsController as PublishEditionsController;
use App\Controllers\Publish\MediaController as PublishMediaController;
use App\Controllers\Publish\NewspaperController as PublishNewspaperController;
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

$router->get('/publish/articles', [PublishArticlesController::class, 'index']);
$router->get('/publish/articles/new', [PublishArticlesController::class, 'create']);
$router->post('/publish/articles', [PublishArticlesController::class, 'store']);
$router->get('/publish/articles/{id}/edit', [PublishArticlesController::class, 'edit']);
$router->post('/publish/articles/{id}', [PublishArticlesController::class, 'update']);
$router->post('/publish/articles/{id}/delete', [PublishArticlesController::class, 'destroy']);
$router->post('/publish/articles/{id}/{action}', [PublishArticlesController::class, 'setStatus']);
$router->post('/publish/media', [PublishMediaController::class, 'store']);

$router->get('/publish/editions', [PublishEditionsController::class, 'index']);
$router->get('/publish/editions/new', [PublishEditionsController::class, 'create']);
$router->post('/publish/editions', [PublishEditionsController::class, 'store']);
$router->get('/publish/editions/{id}/edit', [PublishEditionsController::class, 'edit']);
$router->post('/publish/editions/{id}', [PublishEditionsController::class, 'update']);
$router->post('/publish/editions/{id}/delete', [PublishEditionsController::class, 'destroy']);
$router->post('/publish/editions/{id}/{action}', [PublishEditionsController::class, 'setStatus']);

$router->get('/publish/newspaper', [PublishNewspaperController::class, 'edit']);
$router->post('/publish/newspaper', [PublishNewspaperController::class, 'update']);

// ---------------------------------------------------------------------------
// Admin
// ---------------------------------------------------------------------------
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->post('/admin/logout', [AdminAuthController::class, 'logout']);

$router->get('/admin/newspapers', [AdminNewspapersController::class, 'index']);
$router->get('/admin/newspapers/new', [AdminNewspapersController::class, 'create']);
$router->post('/admin/newspapers', [AdminNewspapersController::class, 'store']);
$router->get('/admin/newspapers/{id}/edit', [AdminNewspapersController::class, 'edit']);
$router->post('/admin/newspapers/{id}', [AdminNewspapersController::class, 'update']);
$router->post('/admin/newspapers/{id}/delete', [AdminNewspapersController::class, 'destroy']);
$router->post('/admin/newspapers/{id}/{action}', [AdminNewspapersController::class, 'setStatus']);

$router->get('/admin/publishers', [AdminPublishersController::class, 'index']);
$router->post('/admin/publishers/{id}/toggle', [AdminPublishersController::class, 'toggleActive']);
$router->post('/admin/publishers/{id}/resend-verify', [AdminPublishersController::class, 'resendVerify']);
$router->post('/admin/publishers/{id}/send-reset', [AdminPublishersController::class, 'sendReset']);

$router->get('/admin/settings', [AdminSettingsController::class, 'edit']);
$router->post('/admin/settings', [AdminSettingsController::class, 'update']);

return $router;
