

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top"><?= __($media->name) ?></div>
	</div>
</div>


<div class="spacer" style="height: 30px"></div>

<div class="row l1">
	<div class="span l1">
		<div class="heading" data-sticky="top">
			File information
		</div>
	</div>
</div>

<div class="spacer" style="height: 15px"></div>

<div class="row l2">
	<div class="span l1">

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Created: <?= date('d/m Y', $media->created) ?></strong></p>
		<p class="small secondary unpadded">The number of files this bucket currently holds.</p>

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Expires: <?= $media->expires? date('d/m Y', $media->expires) : 'Never' ?></strong></p>
		<p class="small secondary unpadded">
			The combined storage capacity of all the servers that make up the cluster
			this bucket is being hosted on.
		</p>

		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Mime type: <?= db()->table('revision')->get('media', $media)->where('expires', null)->first()->mime ?></strong></p>
		<p class="small secondary unpadded">
			The space available on the servers that host this bucket. Please note, that
			this is not 100% representative of the amount of data that can be uploaded.
			Replicas, for example, use additional space, versions do require additional
			space, and the space is shared across several buckets.
		</p>
		
	</div>
	
	<div class="span l1">
		
		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Revisions: <?= $revs->count() ?></strong></p>
		<p class="small secondary unpadded">A cluster is a group of servers that associate to provide more cumulative storage than individual machines.</p>
		<p class="small secondary unpadded">The slaves in this cluster contain the data, while the master dispatches it.</p>
		
		<div class="spacer" style="height: 25px"></div>
		
		<p class="small unpadded"><strong>Bucket: <?= $bucket->uniqid ?> (<?= url('bucket', 'read', $bucket->uniqid) ?>)</strong></p>
		<p class="small secondary unpadded">This server acts as a directory (dispatcher) of the cluster this bucket resides on.</p>
		<p class="small secondary unpadded">It will be in charge of distributing files across the servers in the cluster.</p>
		
		<div class="spacer" style="height: 10px"></div>
	</div>
</div>



<div class="spacer" style="height: 30px"></div>

<div class="row l1">
	<div class="span l1">
		<div class="heading" data-sticky="top">
			Revisions
		</div>
	</div>
</div>

<div class="spacer" style="height: 15px"></div>

<?php foreach ($revs as $revision): ?>
<div class="row l3">
	<div class="span l2">
		<?php $file = db()->table('file')->get('revision', $revision)->where('expires', null)->first(); ?>
		<a href="<?= url('file', 'retrieve', 'uniqid', $file->uniqid, $revision->uniqid, ['token' => $authUser->token]) ?>">
		<?= $revision->uniqid ?>
		</a>
	</div>
	<div class="span l1" style="text-align: right">
		<?= date('Y-m-d H:i:s', $revision->created) ?>
	</div>
</div>
<?php endforeach; ?>

