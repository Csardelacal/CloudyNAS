<?php if (isset($result) && $result): ?>
<?php current_context()->response->setBody('Redirect...')->getHeaders()->redirect(url('bucket', 'read', $result->uniqid)); ?>
<?php endif; ?>

<div class="row l1">
	<div class="span">
		<div class="heading" data-sticky="top">Error deleting</div>
	</div>
</div>

<div class="spacer" style="height: 40px"></div>

<div class="row l1">
	<div class="span l1">
		<p>Due to a unforeseen error, the bucket could not be deleted</p>
	</div>
</div>