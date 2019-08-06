
<?php
	$leader  = db()->table('server')->get('cluster', $bucket->cluster)->where('active', true)->all()->filter(function ($e) { return $e->role & \cloudy\Role::ROLE_MASTER; })->rewind();
	
	/*
	 * I'm not entirely sold on this mechanism. This requires revisiting.
	 */
	$request = request($leader->hostname . '/bucket/read/' . $bucket->uniqid . '.json');
	$request->header('Content-type', 'application/json');
	$request->post($keys->pack($leader->uniqid, base64_encode(random_bytes(150))));
	
	$stats = $request->send()->expect(200)->json()->payload;
?>

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top"><?= __($bucket->name) ?></div>
	</div>
</div>


<div class="spacer" style="height: 30px"></div>

<div class="row l1">
	<div class="span l1">
		<div class="heading" data-sticky="top">
			Usage statistics
		</div>
	</div>
</div>

<div class="spacer" style="height: 15px"></div>

<div class="row l2">
	<div class="span l1">

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>File count: <?= (int)$stats->count ?></strong></p>
		<p class="small secondary unpadded">The number of files this bucket currently holds.</p>

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Disk size: <?= new spitfire\io\Filesize($bucket->size()) ?></strong></p>
		<p class="small secondary unpadded">
			The combined storage capacity of all the servers that make up the cluster
			this bucket is being hosted on.
		</p>

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Disk available: <?= new spitfire\io\Filesize($bucket->available()) ?></strong></p>
		<p class="small secondary unpadded">
			The space available on the servers that host this bucket. Please note, that
			this is not 100% representative of the amount of data that can be uploaded.
			Replicas, for example, use additional space, versions do require additional
			space, and the space is shared across several buckets.
		</p>
		
	</div>
	
	<div class="span l1">
		
		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Cluster: <a href="<?= url('cluster', 'read', $bucket->cluster->_id) ?>"><?= $bucket->cluster->uniqid ?></a></strong></p>
		<p class="small secondary unpadded">A cluster is a group of servers that associate to provide more cumulative storage than individual machines.</p>
		<p class="small secondary unpadded">The slaves in this cluster contain the data, while the master dispatches it.</p>
		
		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Leader: <?= $leader->hostname ?> (<?= $leader->uniqid ?>)</strong></p>
		<p class="small secondary unpadded">This server acts as a directory (dispatcher) of the cluster this bucket resides on.</p>
		<p class="small secondary unpadded">It will be in charge of distributing files across the servers in the cluster.</p>
		
		<div class="spacer" style="height: 10px"></div>
	</div>
</div>


<div class="spacer" style="height: 30px"></div>

<div class="row l1">
	<div class="span l1">
		<div class="heading" data-sticky="top">
			File list.
		</div>
	</div>
</div>

<?php 
	/*
	 * I'm not entirely sold on this mechanism. This requires revisiting.
	 */
	$request = request($leader->hostname . '/media/all/' . $bucket->uniqid . '.json');
	$request->header('Content-type', 'application/json');
	$request->post($keys->pack($leader->uniqid, base64_encode(random_bytes(150))));
	
	$media = $request->send()->expect(200)->json()->media;
?>

<?php foreach ($media as $item): ?>
<div class="row l5 m3 s3">
	<div class="span l4 m2 m2"><?= __($item->name) ?></div>
	<div class="span l1 m1 s1"><?= $item->mime? : '<i>Deleted</i>' ?></div>
</div>
<?php endforeach; ?>

