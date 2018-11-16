

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top"><?= __($cluster->name) ?></div>
	</div>
</div>


<div class="spacer" style="height: 30px"></div>

<div class="row l2">
	<div class="span l1">

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Server count: <?= $servers->count() ?></strong></p>
		<p class="small secondary unpadded">The number of files this bucket currently holds.</p>

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Disk size: <?= new spitfire\io\Filesize($cluster->size()) ?></strong></p>
		<p class="small secondary unpadded">
			The combined storage capacity of all the servers that make up the cluster
			this bucket is being hosted on.
		</p>

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Disk available: <?= new spitfire\io\Filesize($cluster->available()) ?></strong></p>
		<p class="small secondary unpadded">
			The space available on the servers that host this bucket. Please note, that
			this is not 100% representative of the amount of data that can be uploaded.
			Replicas, for example, use additional space, versions do require additional
			space, and the space is shared across several buckets.
		</p>
		
	</div>
	
	<div class="span l1">
		
		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong><a href="<?= url('cluster', 'addServer', $cluster->_id) ?>">Add server</a></strong></p>
		<p class="small secondary unpadded">This server acts as a directory (dispatcher) of the cluster this bucket resides on.</p>
		<p class="small secondary unpadded">It will be in charge of distributing files across the servers in the cluster.</p>
		
		<div class="spacer" style="height: 10px"></div>
	</div>
</div>


<div class="spacer" style="height: 30px"></div>

<div class="row l1">
	<div class="span l1">
		<div class="heading" data-sticky="top">
			Server list.
		</div>
	</div>
</div>

<?php foreach ($servers as $server): ?>
<div class="row l5 m3 s3">
	<div class="span l3 m1 s1"><a href="<?= url('server', 'read', $server->uniqid) ?>"><?= __($server->hostname) ?></a></div>
	<div class="span l1 m1 s1"><?= $server->role & cloudy\Role::ROLE_MASTER? 'Master' : 'Slave' ?></div>
	<div class="span l1 m1 s1"><?= Time::relative($server->lastSeen) ?></div>
</div>
<?php endforeach; ?>

