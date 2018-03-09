<?php namespace cron;

abstract class Cron
{
	
	abstract public function getName();
	abstract public function getInterval();
	abstract public function execute($state);
	
	protected function getModel() {
		$model = db()->table('cron');
		return $model->get('name', $this->getName())->fetch();
	}
	
	protected function makeModel() {
		$model = db()->table('cron')->newRecord();
		$model->name = $this->getName();
		$model->lastrun = time();
		$model->state = null;
		$model->store();
		return $model;
	}
	
	public function run() {
		$fh = fopen(spitfire()->getCWD() . '/bin/usr/.cron.lock', 'w+');
		
		if (flock($fh, LOCK_EX)) {
			$model = $this->getModel()? : $this->makeModel();
			
			if ( $model->lastrun + $this->getInterval() < time() ) {
				$model->state = $this->execute($model->state);
				$model->lastrun = time();
				$model->store();
			}
			
			flock($fh, LOCK_UN);
		}
	}
}
