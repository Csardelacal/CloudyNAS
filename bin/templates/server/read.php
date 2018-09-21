
<div class="row l1">
	<div class="span l1">
		<div class="heading" data-sticky="top">
			<?= $server->hostname ?>
		</div>
	</div>
</div>

<div class="spacer" style="height: 20px"></div>

<div class="row l1">
	<div class="span l1">
		<?php if ($server->active): ?>
		<div style="font-weight: bold">Server is active and <?= $server->writable? 'writable' : 'not writable' ?></div>
		<p class="secondary small unpadded">
			This server is active. It is receiving tasks from the pool and serving files.
			The server cannot receive a new role or be moved to a different cluster until
			it's disabled again.
		</p>
		<?php else: ?>
		<div style="font-weight: bold">Server is <span style="color: #900">inactive</span></div>
		<p class="secondary small unpadded">
			This server is not currently available to receive any tasks from the pool.
			In this mode you can change the server's role, and move it to another cluster.
		</p>
		<?php endif; ?>
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

<div class="row l3 has-dials">
	<div class="span l2">
		
		<p class="small unpadded"><strong>File system usage.</strong></p>
		<p class="small secondary unpadded">Indicates the overall usage of the server's storage media that contains it's uploads directory. If the uploads directory is on the same partition as the server's operating system or another application that makes use of the hard drive, it will be accounted for.</p>
		
		<div class="spacer" style="height: 10px"></div>
		
		<div class="progress-bar">
			<div class="progress" style="width: <?= round((1 - $server->free / $server->size) * 100) ?>%"><?= round((1 - $server->free / $server->size) * 100) ?>%</div>
		</div>

		<div class="spacer" style="height: 15px"></div>
		
		<p class="small unpadded"><strong>Queue length.</strong></p>
		<p class="small secondary unpadded">Provides an overview of how busy the server is. If the queue value is consistently high, the server is having trouble processing it's task backlog.</p>
		<p class="small secondary unpadded">Temporary activity and queue length spikes are normal during cluster shifting or health check operations.</p>
		
		<div class="spacer" style="height: 10px"></div>

		<div class="progress-bar">
			<div class="progress" style="width: <?= (int)$server->queueLen ?>%"><?= (int)$server->queueLen ?> tasks</div>
		</div>
		
		<p class="small unpadded"><strong>Last cron execution</strong></p>
		<p class="small secondary unpadded">The crons are required to run regularly on the system, to make sure that everything works properly.</p>
		<p class="small secondary unpadded">If the cron is not run for a long period of time, it may indicate the server is not properly configured.</p>
		
		<div class="spacer" style="height: 10px"></div>

		<div class="progress-bar">
			<div class="progress" style="width: <?= (time() - $server->lastCron) / 60 ?>%"><?= (int)((time() - $server->lastCron) / 60) ?> minutes</div>
		</div>
	</div>
	
	<div class="span l1 dials">
		<ul>
			<!-- Allow to enable or disable the server-->
			<?php if ($server->active): ?>
			<li>Enable &centerdot; <a href="<?= url('server', 'setActive', 0, ['returnto' => \spitfire\core\http\URL::current()]) ?>">Disable</a></li>
			<?php else: ?>
			<li><a href="<?= url('server', 'setActive', 1, ['returnto' => \spitfire\core\http\URL::current()]) ?>">Enable</a> &centerdot; Disable</li>
			<?php endif; ?>
			
			<!-- Change the server's cluster-->
			<?php if (!$server->active): ?>
			<li>
				<form method="POST" action="<?= url('server', 'setCluster', $server->_id, ['returnto' => \spitfire\core\http\URL::current()]) ?>">
					<div class="styled-select small">
						<select name="cluster" onchange="this.form.submit()">
							<option value="">None</option>
							<?php foreach(db()->table('cluster')->getAll()->all() as $cluster): ?>
							<option value="<?= $cluster->_id ?>" <?= $server->cluster && $cluster->_id === $server->cluster->_id? 'selected' : '' ?>><?= $cluster->name ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</form>
			</li>
			<?php endif; ?>
			
			<?php if (!$server->active): ?>
			<li>
				<form method="POST" action="<?= url('server', 'setRole', $server->_id, ['returnto' => \spitfire\core\http\URL::current()]) ?>">
					<div class="styled-select small">
						<select name="role" onchange="this.form.submit()">
							<option value="">None</option>
							<option value="<?= \cloudy\Role::ROLE_LEADER ?>" <?= $server->role == \cloudy\Role::ROLE_LEADER? 'selected' : '' ?>>Leader</option>
							<option value="<?= \cloudy\Role::ROLE_MASTER ?>" <?= $server->role == \cloudy\Role::ROLE_MASTER? 'selected' : '' ?>>Master</option>
							<option value="<?= \cloudy\Role::ROLE_SLAVE  ?>" <?= $server->role == \cloudy\Role::ROLE_SLAVE ? 'selected' : '' ?>>Worker</option>
						</select>
					</div>
				</form>
			</li>
			<?php endif; ?>
		</ul>
	</div>
</div>