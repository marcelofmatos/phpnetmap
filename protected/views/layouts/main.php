<?php /* @var $this Controller */ ?>
<?php
	// PRÉVIA (Fase 1 em andamento): TbHtml::nav/menuLink/breadcrumbs já
	// patcheados pra emitir markup Bootstrap 5 (ver
	// protected/extensions/bootstrap/helpers/TbHtml.php), então o menu
	// principal e o "Operations" (column2.php) voltam a usar os widgets de
	// verdade — preserva toda a lógica original (active item, visible,
	// confirm/ajax de delete, target=_blank) em vez de recriada à mão.
	// TbTabs (host/view.php) e os 3 TbActiveForm ainda não foram tratados.
	$layoutWebroot = dirname(Yii::app()->basePath);
	$cssVersion = function ($file) use ($layoutWebroot) {
		return '?v=' . filemtime($layoutWebroot . '/css/' . $file);
	};
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- main.css loads BEFORE bootstrap5 on purpose: it still defines
	     app-specific classes Phase 1 hasn't touched yet (diagnostic cards,
	     Host Face editor, colorpicker, host header) that we don't want to
	     lose, but it also has old BS2-era rules for names BS5 also uses
	     (.card, .breadcrumb, .table, .text-nowrap) — loading it first lets
	     BS5's versions of those win the cascade instead of the reverse. -->
	<link rel="stylesheet" href="<?php echo Yii::app()->baseUrl; ?>/css/main.css<?php echo $cssVersion('main.css'); ?>">
	<link rel="stylesheet" href="<?php echo Yii::app()->baseUrl; ?>/css/bootstrap5/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo Yii::app()->baseUrl; ?>/css/map.css<?php echo $cssVersion('map.css'); ?>" type="text/css">
	<link rel="stylesheet" href="<?php echo Yii::app()->baseUrl; ?>/css/welcome.css<?php echo $cssVersion('welcome.css'); ?>" type="text/css">
	<script type="text/javascript" src="<?php echo Yii::app()->baseUrl; ?>/js/d3/d3.min.js"></script>

	<style>
		body { padding-top: 4.5rem; }
		[data-bs-theme="dark"] body { background-color: #14181f; }
		.pnm-navbar-brand { font-family: ui-monospace, "SF Mono", Consolas, monospace; letter-spacing: .02em; }
		.theme-toggle { font-size: 1.1rem; line-height: 1; }

		/* Yii core's CForm AND CActiveForm/TbActiveForm-based views (10
		   _form.php/_search.php across the app) all render plain
		   <div class="row"> field wrappers — a legacy semantic class name
		   that collides with Bootstrap 5's grid .row (flex + negative
		   margins), which is what was stretching every field edge-to-edge.
		   :not(.pnm-grid) exempts the one place we intentionally use a real
		   Bootstrap grid row (column2.php's Operations sidebar split).
		   Spacing lives on the row wrapper and the label — NOT on the input
		   — so every field group gets the same rhythm regardless of what
		   kind of control it holds (a combobox's wrapped <input>, a tiny
		   size=4 port field, a <select>, ...); putting the gap on the input
		   itself made spacing follow the input type instead of being
		   uniform, which is what made the form look uneven. */
		.card-body .row:not(.pnm-grid) {
			display: block;
			margin: 0 0 1rem;
		}
		.card-body .row:not(.pnm-grid) label {
			display: block;
			margin-bottom: .25rem;
			font-weight: 500;
		}
		/* :not([size]) leaves fields that already declare a HTML size= (the
		   port-number inputs in connection/_form.php, meant to stay a few
		   characters wide) at their natural compact width instead of
		   stretching them to 420px. */
		.card-body .row:not(.pnm-grid) input[type=text]:not([size]),
		.card-body .row:not(.pnm-grid) input[type=email]:not([size]),
		.card-body .row:not(.pnm-grid) input[type=password]:not([size]),
		.card-body .row:not(.pnm-grid) select,
		.card-body .row:not(.pnm-grid) textarea {
			display: block;
			width: 100%;
			max-width: 420px;
			padding: .375rem .75rem;
			border: 1px solid var(--bs-border-color);
			border-radius: .375rem;
			background-color: var(--bs-body-bg);
			color: var(--bs-body-color);
		}
		.card-body .row:not(.pnm-grid) input[type=text][size],
		.card-body .row:not(.pnm-grid) input[type=password][size] {
			padding: .375rem .75rem;
			border: 1px solid var(--bs-border-color);
			border-radius: .375rem;
			background-color: var(--bs-body-bg);
			color: var(--bs-body-color);
		}
		.card-body .row.buttons input[type=submit] {
			width: auto;
		}
		/* Checkboxes rendered by CForm/CActiveForm come as <label> then
		   <input type=checkbox> in the DOM — with the label forced to
		   display:block above, the checkbox was left to the browser's
		   default sizing, which stretched it to the row's full width
		   instead of its natural small size. Force the compact native size
		   and lay the row out as a flex row with the checkbox visually
		   first (order), label right next to it on the same line. */
		.card-body .row:not(.pnm-grid) input[type=checkbox],
		.card-body .row:not(.pnm-grid) input[type=radio] {
			width: 1.1em;
			height: 1.1em;
			margin: 0;
		}
		.card-body .row:not(.pnm-grid):has(> input[type=checkbox]),
		.card-body .row:not(.pnm-grid):has(> input[type=radio]) {
			display: flex;
			align-items: center;
			flex-wrap: wrap;
			gap: .5rem;
		}
		.card-body .row:not(.pnm-grid):has(> input[type=checkbox]) label,
		.card-body .row:not(.pnm-grid):has(> input[type=radio]) label {
			display: inline;
			margin: 0;
			order: 2;
			/* flex-basis:auto (even with grow/shrink set) sizes a long
			   label by its max-content width for the wrapping decision
			   itself — since that's wider than the row, flex-wrap moved the
			   WHOLE label to its own line instead of wrapping its text
			   within a shared line. flex-basis:0 forces sizing purely from
			   flex-grow instead, so it fills the remaining space next to
			   the checkbox and wraps its text inside that. */
			flex: 1 1 0%;
		}
		.card-body .row:not(.pnm-grid):has(> input[type=checkbox]) input[type=checkbox],
		.card-body .row:not(.pnm-grid):has(> input[type=radio]) input[type=radio] {
			order: 1;
		}

		/* Helper/hint text (the "Fields with * are required" note, field
		   errors, the wizard step banner) previously had zero styling of
		   its own — it just inherited plain browser defaults, which read as
		   inconsistent next to the rest of the redesigned form. */
		.card-body p.note {
			color: var(--bs-secondary-color);
			font-size: .875rem;
		}
		.card-body span.required {
			color: var(--bs-danger);
		}
		.card-body .errorMessage {
			display: block;
			color: var(--bs-danger);
			font-size: .875rem;
			margin-top: -.5rem;
		}
		.card-body .errorSummary {
			color: var(--bs-danger-text-emphasis);
			background-color: var(--bs-danger-bg-subtle);
			border: 1px solid var(--bs-danger-border-subtle);
			border-radius: .375rem;
			padding: .75rem 1rem;
			margin-bottom: 1rem;
		}
		.card-body .errorSummary ul {
			margin: .25rem 0 0 1.25rem;
			padding: 0;
		}
		.card-body .info {
			background-color: var(--bs-info-bg-subtle);
			border: 1px solid var(--bs-info-border-subtle);
			border-radius: .375rem;
			padding: .75rem 1rem;
			margin-bottom: 1rem;
		}

		/* Flex items default to min-width:auto, meaning they refuse to
		   shrink below their content's natural width — a wide grid (many
		   columns) was forcing .col-lg-9 wider than its 75%, pushing into
		   (behind) the Operations sidebar card next to it instead of
		   scrolling within its own column. min-width:0 lets it actually
		   respect the column width, and TbGridView's .table-responsive
		   wrapper (see TbGridView.php) then scrolls the overflow instead of
		   spilling it. */
		.pnm-grid > [class*="col-"] {
			min-width: 0;
		}

		/* CGridView's filter-row <input> (.filter-container, one per
		   column) has no size= and no width of its own, so every column
		   got the browser's default text-input width (~170-200px)
		   regardless of whether that column's actual data is a single
		   digit or a long string — inflating the whole table and the
		   horizontal scroll it now needs. width:100% makes each filter
		   input fill its own <td> instead, so the column's width follows
		   its DATA (the rows below it), not the filter input. */
		.card-body .filter-container input {
			width: 100%;
			box-sizing: border-box;
			padding: .25rem .5rem;
			border: 1px solid var(--bs-border-color);
			border-radius: .25rem;
			background-color: var(--bs-body-bg);
			color: var(--bs-body-color);
		}
	</style>

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
</head>

<body>

<nav class="navbar navbar-expand-lg fixed-top bg-body-tertiary border-bottom">
	<div class="container-fluid">
		<a class="navbar-brand pnm-navbar-brand" href="<?php echo Yii::app()->getBaseUrl(true); ?>"><?php echo CHtml::encode(Yii::app()->name); ?></a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pnmNav">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="pnmNav">
			<?php $this->widget('bootstrap.widgets.TbNav', array(
				'htmlOptions' => array('class' => 'navbar-nav me-auto mb-2 mb-lg-0'),
				'items' => array(
					array('label' => 'SNMP Templates', 'url' => array('/snmpTemplate/admin')),
					array('label' => 'Hosts', 'url' => array('/host/admin')),
					array('label' => 'Vlans', 'url' => array('/vlan/admin')),
					array('label' => 'Connections', 'url' => array('/connection/admin')),
					array('label' => 'Search', 'url' => array('/search/index')),
					array('label' => 'MCP Tokens', 'url' => array('/mcpToken/admin')),
					array('label' => 'Users', 'url' => array('/user/admin')),
					array('label' => 'Configuration', 'url' => array('/config/index')),
					array('label' => 'About', 'url' => array('/site/page', 'view' => 'about')),
					array('label' => 'Login', 'url' => array('/site/login'), 'visible' => Yii::app()->user->isGuest),
					array('label' => 'Logout (' . Yii::app()->user->name . ')', 'url' => array('/site/logout'), 'visible' => !Yii::app()->user->isGuest),
				),
			)); ?>
			<button type="button" class="btn btn-outline-secondary theme-toggle" id="pnmThemeToggle" title="Toggle light/dark theme">🌙</button>
		</div>
	</div>
</nav>

<div class="container-fluid mt-3">

	<?php if (!empty($this->breadcrumbs)): ?>
		<nav aria-label="breadcrumb">
			<?php $this->widget('bootstrap.widgets.TbBreadcrumb', array(
				'links' => $this->breadcrumbs,
			)); ?>
		</nav>
	<?php endif; ?>

	<div class="card">
		<div class="card-body">
			<?php echo $content; ?>
		</div>
	</div>

	<footer class="text-body-secondary my-4 small">
		<?php echo CHtml::encode(Yii::app()->name); ?> <?php echo date('Y'); ?>
	</footer>

</div>

<script src="<?php echo Yii::app()->baseUrl; ?>/js/bootstrap5/bootstrap.bundle.min.js"></script>
<script>
	(function () {
		var root = document.documentElement;
		var toggle = document.getElementById('pnmThemeToggle');
		var stored = localStorage.getItem('pnm-theme');
		if (stored) {
			root.setAttribute('data-bs-theme', stored);
			toggle.textContent = stored === 'dark' ? '☀️' : '🌙';
		}
		toggle.addEventListener('click', function () {
			var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
			root.setAttribute('data-bs-theme', next);
			localStorage.setItem('pnm-theme', next);
			toggle.textContent = next === 'dark' ? '☀️' : '🌙';
		});
	})();
</script>

</body>
</html>
