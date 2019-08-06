<?php if (isset($result) && $result): ?>
<?php current_context()->response->setBody('Redirect...')->getHeaders()->redirect(url('cluster', 'read', $result->_id)); ?>
<?php endif; ?>

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top">Add server to the cluster</div>
	</div>
</div>

<div class="spacer" style="height: 40px"></div>

<form method="POST" action="" class="regular">
	<div class="row l3">
		<div class="span l2">
			<label>Server</label>
			<select name="server">
				<?php foreach($servers as $server): ?>
				<option value="<?= $server->_id ?>"><?= __($server->hostname) ?></option>
				<?php endforeach; ?>
			</select>
		</div><!--
	--><div class="span l1">
		<p class="secondary small">
			Please note that only disabled servers are being listed on this page.
			Also note, servers that have been disabled will have a grace period of 
			30 days to remove the last traces from their drive before resuming work.
		</p>
		</div><!--
	--></div><!--
	--><div class="row l1">
		<div class="span l1" style="text-align: right">
			<input type="submit" value="Add">
		</div>
	</div>
</form>