<?php
/**
 * Queue Admin Layout
 *
 * Self-contained admin layout using Bootstrap 5 and Font Awesome 6 via CDN.
 * Completely isolated from host application's CSS/JS.
 *
 * @var \Cake\View\View $this
 */

use Cake\Core\Configure;

$autoRefresh = 0;
$request = $this->getRequest();
if ($request && $request->getParam('controller') === 'Queue' && $request->getParam('action') === 'index') {
	$autoRefresh = (int)Configure::read('Queue.dashboardAutoRefresh') ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $this->fetch('title') ? strip_tags($this->fetch('title')) . ' - ' : '' ?>Queue Admin</title>

	<!-- Bootstrap 5.3.3 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

	<!-- Font Awesome 6.7.2 -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous">
	<!-- Chart.js for stats page -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

	<!-- Flatpickr for datetime inputs -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
	<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>

	<style>
		:root {
			--queue-primary: #0d6efd;
			--queue-success: #198754;
			--queue-warning: #ffc107;
			--queue-danger: #dc3545;
			--queue-info: #0dcaf0;
			--queue-secondary: #6c757d;
			--queue-dark: #212529;
			--queue-light: #f8f9fa;
			--queue-sidebar-bg: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
			--queue-sidebar-width: 260px;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			background-color: #f4f6f9;
			min-height: 100vh;
		}

		/* Navbar */
		.queue-navbar {
			background: var(--queue-dark);
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.queue-navbar .navbar-brand {
			font-weight: 600;
			color: #fff;
		}

		.queue-navbar .navbar-brand i {
			color: var(--queue-primary);
		}

		/* Sidebar */
		.queue-sidebar {
			background: var(--queue-sidebar-bg);
			min-height: calc(100vh - 56px);
			width: var(--queue-sidebar-width);
			position: fixed;
			left: 0;
			top: 56px;
			padding: 1.5rem 0;
			overflow-y: auto;
		}

		/* Mobile nav offcanvas — same background as sidebar */
		.queue-mobile-nav-bg {
			background: var(--queue-sidebar-bg);
		}

		/* Column-width utilities (replaces inline `<th style="width:Npx">`). */
		.queue-col-w-50 { width: 50px; }
		.queue-col-w-200 { width: 200px; }

		/* Stats chart wrapper (replaces inline `style="position:relative;height:400px"`). */
		.queue-chart-wrapper {
			position: relative;
			height: 400px;
		}

		.queue-sidebar .nav-section {
			padding: 0 1rem;
			margin-bottom: 1.5rem;
		}

		.queue-sidebar .nav-section-title {
			color: rgba(255,255,255,0.5);
			font-size: 0.75rem;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			padding: 0 0.75rem;
			margin-bottom: 0.5rem;
		}

		.queue-sidebar .nav-link {
			color: rgba(255,255,255,0.8);
			padding: 0.6rem 0.75rem;
			border-radius: 0.375rem;
			margin-bottom: 0.25rem;
			transition: all 0.2s ease;
		}

		.queue-sidebar .nav-link:hover {
			color: #fff;
			background: rgba(255,255,255,0.1);
		}

		.queue-sidebar .nav-link.active {
			color: #fff;
			background: var(--queue-primary);
		}

		.queue-sidebar .nav-link i {
			width: 1.25rem;
			margin-right: 0.5rem;
		}

		/* Main Content */
		.queue-main {
			margin-left: var(--queue-sidebar-width);
			padding: 1.5rem;
			min-height: calc(100vh - 56px);
		}

		/* Stats Cards */
		.stats-card {
			border: none;
			border-radius: 0.5rem;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			overflow: hidden;
		}

		.stats-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
		}

		.stats-card .card-body {
			padding: 1.25rem;
		}

		.stats-card .stats-icon {
			width: 48px;
			height: 48px;
			border-radius: 0.5rem;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.25rem;
		}

		.stats-card .stats-value {
			font-size: 1.75rem;
			font-weight: 700;
			line-height: 1.2;
		}

		.stats-card .stats-label {
			color: var(--queue-secondary);
			font-size: 0.875rem;
		}

		/* Status Badges */
		.badge-pending {
			background-color: var(--queue-warning);
			color: #000;
		}

		.badge-running {
			background-color: var(--queue-primary);
		}

		.badge-completed {
			background-color: var(--queue-success);
		}

		.badge-failed {
			background-color: var(--queue-danger);
		}

		.badge-scheduled {
			background-color: var(--queue-info);
			color: #000;
		}

		/* Tables */
		.queue-table {
			background: #fff;
			border-radius: 0.5rem;
			overflow: hidden;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
		}

		.queue-table thead th {
			background: var(--queue-light);
			border-bottom: 2px solid #dee2e6;
			font-weight: 600;
			font-size: 0.875rem;
			text-transform: uppercase;
			letter-spacing: 0.025em;
			color: var(--queue-secondary);
		}

		.queue-table tbody tr:hover {
			background-color: rgba(13, 110, 253, 0.04);
		}

		/* Action Buttons */
		.btn-action {
			padding: 0.25rem 0.5rem;
			font-size: 0.875rem;
		}

		/* Status Banner */
		.status-banner {
			border-radius: 0.5rem;
			padding: 1rem 1.25rem;
			margin-bottom: 1.5rem;
		}

		.status-banner.status-running {
			background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
			border: 1px solid #b1dfbb;
		}

		.status-banner.status-idle {
			background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
			border: 1px solid #ffc107;
		}

		.status-banner .status-icon {
			font-size: 1.5rem;
		}

		/* Flash Messages */
		.queue-flash {
			margin-bottom: 1rem;
		}

		/* Footer */
		.queue-footer {
			margin-left: var(--queue-sidebar-width);
			padding: 1rem 1.5rem;
			background: #fff;
			border-top: 1px solid #dee2e6;
			color: var(--queue-secondary);
			font-size: 0.875rem;
		}

		/* Responsive */
		@media (max-width: 991.98px) {
			.queue-sidebar {
				position: relative;
				width: 100%;
				min-height: auto;
				top: 0;
			}

			.queue-main {
				margin-left: 0;
			}

			.queue-footer {
				margin-left: 0;
			}
		}

		/* Utilities */
		.text-truncate-2 {
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			overflow: hidden;
		}

		/* Yes/No Badges */
		.yes-no {
			display: inline-flex;
			align-items: center;
			padding: 0.25em 0.5em;
			font-size: 0.75rem;
			font-weight: 500;
			border-radius: 0.25rem;
		}

		.yes-no-yes {
			background-color: #d1e7dd;
			color: #0f5132;
		}

		.yes-no-no {
			background-color: #f8d7da;
			color: #842029;
		}

		/* Progress bars */
		.progress {
			height: 0.5rem;
			border-radius: 0.25rem;
		}

		/* Cards */
		.card {
			border: none;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			border-radius: 0.5rem;
		}

		.card-header {
			background: var(--queue-light);
			border-bottom: 1px solid #dee2e6;
			font-weight: 600;
		}

		/* Code blocks */
		code {
			background: #f1f3f4;
			padding: 0.125rem 0.375rem;
			border-radius: 0.25rem;
			font-size: 0.875em;
		}

		pre {
			background: #f8f9fa;
			padding: 1rem;
			border-radius: 0.375rem;
			border: 1px solid #dee2e6;
			overflow-x: auto;
			white-space: pre-wrap;
			word-wrap: break-word;
			max-width: 100%;
		}

		/* Collapsible sections */
		.collapse-icon {
			transition: transform 0.2s ease;
		}

		[aria-expanded="true"] .collapse-icon {
			transform: rotate(90deg);
		}
	</style>

	<?= $this->fetch('meta') ?>
	<?= $this->fetch('css') ?>
</head>
<body>
	<!-- Navbar -->
	<nav class="navbar navbar-expand-lg navbar-dark queue-navbar">
		<div class="container-fluid">
			<a class="navbar-brand" href="<?= $this->Url->build(['plugin' => 'Queue', 'prefix' => 'Admin', 'controller' => 'Queue', 'action' => 'index']) ?>">
				<i class="fas fa-layer-group me-2"></i>Queue Admin
			</a>
			<!-- Mobile menu button -->
			<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav ms-auto">
					<?php
					$adminBackUrl = \Cake\Core\Configure::read('Queue.adminBackUrl');
					$hasAdminBack = $adminBackUrl !== null && $adminBackUrl !== '';
					$adminBackLabel = (string)\Cake\Core\Configure::read('Queue.adminBackLabel', __d('queue', 'Back to App'));
					?>
					<?php if ($hasAdminBack): ?>
					<li class="nav-item">
						<a class="nav-link" href="<?= $this->Url->build($adminBackUrl) ?>">
							<i class="fas fa-arrow-left me-1"></i><?= h($adminBackLabel) ?>
						</a>
					</li>
					<?php endif; ?>
					<?php if (\Cake\Core\Plugin::isLoaded('QueueScheduler')): ?>
					<li class="nav-item">
						<a class="nav-link" href="<?= $this->Url->build(['plugin' => 'QueueScheduler', 'prefix' => 'Admin', 'controller' => 'QueueScheduler', 'action' => 'index']) ?>">
							<i class="fas fa-calendar-alt me-1"></i>Scheduler
						</a>
					</li>
					<?php endif; ?>
					<?= $this->element('Queue.Queue/connection_switcher') ?>
					<li class="nav-item">
						<span class="nav-link text-light" title="<?= __d('queue', 'Server Time') ?>">
							<i class="far fa-clock me-1"></i>
							<?= date('Y-m-d H:i:s') ?>
						</span>
					</li>
				</ul>
			</div>
		</div>
	</nav>

	<!-- Mobile Offcanvas Navigation -->
	<div class="offcanvas offcanvas-start queue-mobile-nav-bg" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
		<div class="offcanvas-header border-bottom border-secondary">
			<h5 class="offcanvas-title text-white" id="mobileNavLabel">
				<i class="fas fa-layer-group me-2"></i>Queue Admin
			</h5>
			<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body p-0">
			<?= $this->element('Queue.Queue/mobile_nav') ?>
		</div>
	</div>

	<div class="d-flex">
		<!-- Sidebar -->
		<?= $this->element('Queue.Queue/sidebar') ?>

		<!-- Main Content -->
		<main class="queue-main flex-grow-1">
			<!-- Flash Messages -->
			<div class="queue-flash">
				<?= $this->element('Queue.flash/flash') ?>
			</div>

			<?= $this->fetch('content') ?>
		</main>
	</div>

	<!-- Footer -->
	<footer class="queue-footer">
		<div class="d-flex justify-content-between align-items-center">
			<span>Queue Plugin for CakePHP</span>
			<span>
				<i class="fas fa-server me-1"></i>
				PHP <?= phpversion() ?>
			</span>
		</div>
	</footer>

	<?= $this->fetch('postLink') ?>

	<!-- Bootstrap 5.3.3 JS Bundle -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

	<?php $cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', ''); ?>
	<script<?= $cspNonce !== '' ? ' nonce="' . h($cspNonce) . '"' : '' ?>>
		document.addEventListener('DOMContentLoaded', function() {
			// Initialize tooltips
			var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
			var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
				return new bootstrap.Tooltip(tooltipTriggerEl);
			});

			// Initialize flatpickr datetime inputs with D M Y display format
			document.querySelectorAll('.flatpickr-datetime').forEach(function(el) {
				flatpickr(el, {
					enableTime: true,
					dateFormat: 'Y-m-d H:i',
					altInput: true,
					altFormat: 'd M Y H:i',
					time_24hr: true,
					allowInput: true,
				});
			});

			// Confirmation dialogs for postButton forms (CSP-safe replacement for postLink + confirm)
			document.querySelectorAll('form[data-confirm-message]').forEach(function(form) {
				form.addEventListener('submit', function(e) {
					if (!confirm(this.dataset.confirmMessage)) {
						e.preventDefault();
					}
				});
			});

			// Heatmap cell colors (CSP-safe replacement for inline style="background-color:…; color:…;")
			document.querySelectorAll('[data-bg-color]').forEach(function(el) {
				el.style.backgroundColor = el.dataset.bgColor;
			});
			document.querySelectorAll('[data-text-color]').forEach(function(el) {
				el.style.color = el.dataset.textColor;
			});

			<?php if ($autoRefresh > 0): ?>
			// Auto-refresh
			setTimeout(function() {
				window.location.reload();
			}, <?= $autoRefresh * 1000 ?>);
			<?php endif; ?>
		});
	</script>

	<?= $this->fetch('script') ?>
</body>
</html>
