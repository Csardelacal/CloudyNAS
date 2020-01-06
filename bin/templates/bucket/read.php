
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

<template id="file">
	<div class="row l5 m3 s3">
		<div class="span l4 m2 m2"><a id="name"></a></div>
		<div class="span l1 m1 s1"><span id="mime"></span></div>
	</div>
</template>

<div id="file-list"></div>

<div style="text-align: center; padding: 30px 0;">
	<a id="load-more" style="cursor: pointer;">Load more</a>
</div>

<script type="text/javascript">
(function () {
	var token = '<?= $authUser->token ?>';
	var leader = '<?= $leader->hostname ?>';
	var uniqid = '<?= $bucket->uniqid ?>';
	var until = undefined;
	
	var more = function () {
		var xhr = new XMLHttpRequest();
		
		if (!until) {
			xhr.open('GET', leader + '/media/all/' + uniqid + '.json?token=' + token);
		}
		else {
			xhr.open('GET', leader + '/media/all/' + uniqid + '.json?token=' + token + '&until=' + until);
		}
		
		xhr.onreadystatechange = function () {
			if (xhr.readyState === 4) {
				console.log(xhr.responseText);
				var json = JSON.parse(xhr.responseText);
				until = json.media[json.media.length - 1].uniqid;
				
				var template = document.querySelector('#file');
				
				for (var i = 0; i < json.media.length; i++) {
					var clone = document.importNode(template.content, true);
					clone.querySelector('#name').textContent = json.media[i].name;
					clone.querySelector('#name').href = leader + '/media/read/' + uniqid + '/' + json.media[i].name + '/?token=' + token;
					clone.querySelector('#mime').textContent = json.media[i].mime || 'Deleted';
					
					document.querySelector('#file-list').appendChild(clone);
				}
			}
		}
		xhr.send();
	}
	
	document.getElementById('load-more').addEventListener('click', more);
	more();
	
}());
</script>