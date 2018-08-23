<!DOCTYPE html>
<html>
	<head>
		
		<title><?= isset(${'page.title'})? ${'page.title'} : 'CloudyNAS - Distributed Network Storage' ?></title>
	</head>
	<body>
		<?= $this->content() ?> 
	</body>
</html>