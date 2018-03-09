<?php

class CronModel extends spitfire\Model
{
	public function definitions(\spitfire\storage\database\Schema $schema) {
		$schema->name    = new StringField(20);
		$schema->state   = new TextField();
		$schema->lastrun = new IntegerField();
		
		unset($schema->_id);
		$schema->name->setPrimary(true);
		$schema->index($schema->name)->setPrimary(true);
	}

}

