
<ul>
	<?php foreach ($servers as $server): ?>
	<li>
		<span><?= $server->hostname ?> (<?= $server->size? round($server->free / $server->size * 100) . '% free' : 'Unknown capacity'  ?>)</span>
		<span>
			<?= $server->role & cloudy\Role::ROLE_LEADER? 'Leader' : '' ?>
			<?= $server->role & cloudy\Role::ROLE_MASTER? 'Master' : '' ?>
			<?= $server->role & cloudy\Role::ROLE_SLAVE ? 'Slave'  : '' ?>
		</span>
	</li>
	<?php endforeach; ?>
</ul>