<!DOCTYPE html>
<html>
	<head>
		
		<title><?= isset(${'page.title'})? ${'page.title'} : 'CloudyNAS - Distributed Network Storage' ?></title>
		<link rel="stylesheet" type="text/css" href="<?= spitfire\core\http\URL::asset('css/app.css') ?>">
		<link rel="stylesheet" type="text/css" href="<?= spitfire\core\http\URL::asset('css/ui-layout.css') ?>">
		
	</head>
	<body>
		
		<div class="navbar">
			<div class="left">
				<span class="toggle-button dark"></span>
				<a href="<?= url() ?>">CloudyNAS</a>
			</div>
			<div class="right">
				<?php if(isset($authUser)): ?>
				<div class="has-dropdown" style="display: inline-block">
					<span class="app-switcher toggle" data-toggle="app-drawer"></span>
					<div class="dropdown right-bound unpadded" data-dropdown="app-drawer">
						<div class="app-drawer" id="app-drawer"></div>
					</div>
				</div>
				<span class="h-spacer" style="display: inline-block; width: 20px;"></span>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="auto-extend">
			<div class="contains-sidebar">
				<div class="sidebar">
					<div class="spacer" style="height: 20px"></div>
					
					<div class="menu-title">Storage</div>
					<div class="menu-entry"><a href="<?= url() ?>">Dashboard</a></div>
					
					<div class="spacer" style="height: 50px"></div>
					
					<div class="menu-title">Add...</div>
					<div class="menu-entry"><a href="<?= url('pool', 'acquire') ?>">Server</a></div>
					<div class="menu-entry"><a href="<?= url('cluster', 'create') ?>">Cluster</a></div>
					<div class="menu-entry"><a href="<?= url('bucket', 'create') ?>">Bucket</a></div>
					
				</div>
			</div><!--
			--><div class="content">
				
				<div class="spacer" style="height: 20px"></div>
				
				<div  data-sticky-context>
					<?= $this->content() ?>
				</div>
			</div>
			
		</div>
		
		<footer>
			<div class="row1">
				<div class="span1">
					<span style="font-size: .8em; color: #777">
						&copy; <?= date('Y') ?> Magic3W - This software is licensed under MIT License
					</span>
				</div>
			</div>
		</footer>
		
		<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function () {
			var ae = document.querySelector('.auto-extend');
			var wh = window.innerheight || document.documentElement.clientHeight;
			var dh = document.body.clientHeight;
			
			ae.style.minHeight = Math.max(ae.clientHeight + (wh - dh), 0) + 'px';
		});
		</script>
		
		<script src="<?= spitfire\core\http\URL::asset('js/depend.js') ?>" type="text/javascript"></script>
		<script src="<?= spitfire\core\http\URL::asset('js/depend/router.js') ?>" type="text/javascript"></script>
		<script type="text/javascript" src="<?= spitfire\core\http\URL::asset('js/ui-layout.js') ?>"></script>
		
		<script type="text/javascript">
		(function () {
			depend(['depend/router'], function(router) {
				router.all().to(function(e) { return '<?= \spitfire\SpitFire::baseUrl() . '/assets/js/' ?>' + e + '.js'; });
				router.equals('phpas/app/drawer').to( function() { return '<?= $sso->getAppDrawerJS() ?>'; });
			});
			
			depend(['ui/dropdown'], function (dropdown) {
				dropdown('.app-switcher');
			});
			
			depend(['phpas/app/drawer'], function (drawer) {
				console.log(drawer);
			});
		}());
		</script>
		
		<script type="text/javascript">
			depend(['sticky'], function (sticky) {
				
				/*
				 * Create elements for all the elements defined via HTML
				 */
				var els = document.querySelectorAll('*[data-sticky]');

				for (var i = 0; i < els.length; i++) {
					sticky.stick(els[i], sticky.context(els[i]), els[i].getAttribute('data-sticky'));
				}
			});
		</script>
	</body>
</html>