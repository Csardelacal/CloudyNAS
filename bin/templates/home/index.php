
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


<ul>
	<?php foreach ($clusters as $cluster): ?>
	<li>
		<span><?= $cluster->name ?> (<?= new spitfire\io\Filesize($cluster->available())  ?>)</span>
	</li>
	<?php endforeach; ?>
</ul>

<ul>
	<?php foreach ($buckets as $bucket): ?>
	<li>
		<span><?= $bucket->name ?> (<?= new \spitfire\io\Filesize($bucket->size()) ?>)</span>
	</li>
	<?php endforeach; ?>
</ul>