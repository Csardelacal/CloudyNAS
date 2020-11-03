<?php if (isset($result) && $result): ?>
<?php current_context()->response->setBody('Redirect...')->getHeaders()->redirect(url('bucket', 'read', $result->uniqid)); ?>
<?php endif; ?>

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top">Update bucket</div>
	</div>
</div>

<div class="spacer" style="height: 40px"></div>

<form method="POST" action="" class="regular">
	<div class="row l4">
		<div class="span l2">
			<label>Name</label>
			<input name="name" type="text" placeholder="Name..." value="<?= $bucket->name ?>">
		</div><!--
		--><div class="span l1">
			<label>Cluster</label>
			<div><?= __($bucket->cluster->name) ?></div>
		</div><!--
		--><div class="span l1">
			<label>Replica count</label>
			<input name="replicas" type="number" step="1" value="<?= $bucket->replicas ?>">
		</div><!--
	--></div><!--
	--><div class="row l1">
		<div class="span l1" style="text-align: right">
			<input type="submit" value="Create">
		</div>
	</div>
</form>