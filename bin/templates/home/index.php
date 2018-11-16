
<div class="dashboard">
	<?php $every = new Every(2, '</div><div class="spacer" style="height: 20px"></div><div class="row l2">'); ?>
	<div class="row">
		<div class="span">
			<div class="heading topbar" data-sticky>
				Servers
			</div>
		</div>
	</div>
	
	<div class="spacer" style="height: 20px"></div>
	
	<div class="row l2">
		<?php foreach ($servers as $server): ?>
		<div class="span l1 tile">
			<div class="row l4">
				<div class="span l1" style="text-align:  center;">
					<img src="<?= spitfire\core\http\URL::asset('img/server.png') ?>" style="width: 80%;">
				</div>
				<div class="span l3">
					<div  style="font-weight: bold;">
						<a href="<?= url('server', 'read', $server->_id) ?>"><?= $server->hostname ?></a>
					</div>
					<div>
						<?= $server->role & cloudy\Role::ROLE_LEADER? 'Leader' : '' ?>
						<?= $server->role & cloudy\Role::ROLE_MASTER? 'Master' : '' ?>
						<?= $server->role & cloudy\Role::ROLE_SLAVE ? 'Slave'  : '' ?>
						&CenterDot;
						<?= $server->active? 'Active' : 'Paused' ?>
						&CenterDot;
						<?= Time::relative($server->lastSeen) ?>
					</div>

					<div class="spacer" style="height: 15px"></div>
					
					<div class="progress-bar">
						<div class="progress" style="width: <?= round((1 - $server->free / $server->size) * 100) ?>%"><?= round((1 - $server->free / $server->size) * 100) ?>%</div>
					</div>

					<div class="spacer" style="height: 5px"></div>
					
					<div class="progress-bar">
						<div class="progress" style="width: <?= (int)$server->queueLen ?>%"><?= (int)$server->queueLen ?> tasks</div>
					</div>
				</div>
			</div>
		</div>

		<?= $every->next(); ?>
		<?php endforeach; ?>
	</div>

	<div class="spacer" style="height: 50px"></div>
	
	<div class="row">
		<div class="span">
			<div class="heading topbar" data-sticky>
				Clusters
			</div>
		</div>
	</div>
	
	<div class="spacer" style="height: 20px"></div>
	
	<div class="row l2">
		<?php $every = new Every(2, '</div><div class="spacer" style="height: 20px"></div><div class="row l2">'); ?>
		<?php foreach ($clusters as $cluster): ?>
		<div class="span l1 tile">
			<div class="row l4">
				<div class="span l1">
					<img src="<?= spitfire\core\http\URL::asset('img/cluster.png') ?>" style="width: 80%;">
				</div>
				<div class="span l3">
					<div  style="font-weight: bold;">
						<a href="<?= url('cluster', 'read', $cluster->_id) ?>"><?= $cluster->name ?></a>
					</div>
					<div>
						Cluster (<?= new spitfire\io\Filesize($cluster->available()) ?> Free)
					</div>
					<div>
						<?= db()->table('server')->get('cluster', $cluster)->count() ?> servers
					</div>

					<div class="spacer" style="height: 15px"></div>
					
					<div class="progress-bar">
						<div class="progress" style="width: <?= round((1 - $cluster->available() / $cluster->size()) * 100) ?>%"><?= round((1 - $cluster->available() / $cluster->size()) * 100) ?>%</div>
					</div>
				</div>
			</div>
		</div>
		<?= $every->next(); ?>
		<?php endforeach; ?>
	</div>

	<div class="spacer" style="height: 50px"></div>
	
	<div class="row">
		<div class="span">
			<div class="heading topbar" data-sticky>
				Buckets
			</div>
		</div>
	</div>
	
	<div class="spacer" style="height: 20px"></div>
	
	<div class="row l2">
		<?php $every = new Every(2, '</div><div class="spacer" style="height: 20px"></div><div class="row l2">'); ?>
		<?php foreach ($buckets as $bucket): ?>
		<div class="span l1 tile">
			<div class="row l4">
				<div class="span l1">
					<img src="<?= spitfire\core\http\URL::asset('img/bucket.png') ?>" style="width: 80%;">
				</div>
				<div class="span l3">
					<div  style="font-weight: bold;">
						<a href="<?= url('bucket', 'read', $bucket->uniqid) ?>"><?= $bucket->name ?></a>
					</div>
					<div>
						<?= $bucket->name ?> (<?= new \spitfire\io\Filesize($bucket->size()) ?> - estimated)
					</div>

					<div class="spacer" style="height: 15px"></div>
					
					<div class="progress-bar" title="Bucket size and available storage is estimated.">
						<div class="progress" style="width: <?= round((1 - $bucket->available() / $bucket->size()) * 100) ?>%"><?= round((1 - $bucket->available() / $bucket->size()) * 100) ?>%</div>
					</div>
				</div>
			</div>
		</div>
		<?= $every->next(); ?>
		<?php endforeach; ?>
	</div>
</div>

<div class="spacer" style="height: 50px"></div>