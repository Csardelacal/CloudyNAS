<?php if (isset($result) && $result): ?>
<?php current_context()->response->setBody('Redirect...')->getHeaders()->redirect(url('bucket', 'read', $result->_id)); ?>
<?php endif; ?>

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top">Create bucket</div>
	</div>
</div>

<div class="spacer" style="height: 40px"></div>

<form method="POST" action="" class="regular">
	<div class="row l4">
		<div class="span l2">
			<label>Name</label>
			<input name="name" type="text" placeholder="Name...">
		</div><!--
		--><div class="span l1">
			<label>Cluster</label>
			<div class="styled-select">
				<select name="cluster">
					<?php foreach(db()->table('cluster')->getAll()->all() as $cluster) : ?>
					<option value="<?= $cluster->_id ?>"><?= __($cluster->name) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div><!--
		--><div class="span l1">
			<label>Replica count</label>
			<input name="replicas" type="number" step="1" value="3">
		</div><!--
	--></div><!--
	--><div class="row l1">
		<div class="span l1" style="text-align: right">
			<input type="submit" value="Create">
		</div>
	</div>
</form>