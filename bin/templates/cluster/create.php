<?php if (isset($result) && $result): ?>
<?php current_context()->response->setBody('Redirect...')->getHeaders()->redirect(url('cluster', 'read', $result->_id)); ?>
<?php endif; ?>

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top">Create cluster</div>
	</div>
</div>

<div class="spacer" style="height: 40px"></div>

<form method="POST" action="" class="regular">
	<div class="row l3">
		<div class="span l2">
			<label>Name</label>
			<input name="name" type="text" placeholder="Name...">
		</div><!--
	--></div><!--
	--><div class="row l1">
		<div class="span l1" style="text-align: right">
			<input type="submit" value="Create">
		</div>
	</div>
</form>