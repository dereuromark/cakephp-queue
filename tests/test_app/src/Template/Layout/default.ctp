<!DOCTYPE HTML>
<html>
<head>
</head>
<body>

	<section class="content">

		<div class="xrow messages">
			<?= $this->Flash->render() ?>
		</div>

		<div class="row">
			<?= $this->fetch('content') ?>
		</div>

	</section>
</body>
</html>
