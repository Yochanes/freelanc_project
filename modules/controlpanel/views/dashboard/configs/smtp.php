<?php 
	use yii\bootstrap\Dropdown;
?>
<div class="panel_menu flex hend">
	<section>
		<button class="btn btn-success" onclick="addElement(event, this)"><i class="fa fa-plus"></i>Добавить настройки</button>
		<form class="hidden">
			<input type="text" name="site_email" data-title="Email" placeholder="Email">
			<input type="text" name="host" data-title="Хост" placeholder="Хост">
			<input type="text" name="username" data-title="Логин" placeholder="Логин">
			<input type="text" name="password" data-title="Пароль" placeholder="Пароль">
			<input type="number" name="port" data-title="Порт" placeholder="Порт">
			<input type="checkbox" name="active" data-title="Активно"checked>
		</form>
	</section>
</div>
<div class="panel_content">
	<?= (empty($configs) ? '<h2 class="text-center">На данный момент вы не добавили ни одного элемента</h2>' : '') ?>
	<?php if (!empty($configs)) { ?>
	<ul class="makes-list drop-down-list flex hstart vstart flex-wrap">
	<?php foreach ($configs as $item) { ?>
		<li>
			<?= $item->site_email ?>
			<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deletesmtp/')"></i>
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatesmtp/', 'Редактирование настроек <?= $item->site_email ?>')"></i>
			<form class="hidden">
				<input type="text" name="site_email" data-title="Email" value="<?= $item->site_email ?>" placeholder="Email">
				<input type="text" name="host" data-title="Хост" value="<?= $item->host ?>" placeholder="Хост">
				<input type="text" name="username" data-title="Логин" value="<?= $item->username ?>" placeholder="Логин">
				<input type="text" name="password" data-title="Пароль" value="<?= $item->password ?>" placeholder="Пароль">
				<input type="number" name="port" data-title="Порт" value="<?= $item->port ?>" placeholder="Порт">
				<input type="checkbox" name="active" data-title="Активно" <?= $item->active ? 'checked value="1"' : '' ?>>
				<input type="hidden" name="id" value="<?= $item->id ?>">
			</form>
			<i class="fa fa-envelope" onclick="addLetter(event,this)"></i>
			<form class="hidden">
				<input type="text" name="to" data-title="Email" placeholder="Email">
				<input type="text" name="subject" data-title="Тема" placeholder="Тема">
				<input type="text" name="text" data-title="Текст" placeholder="Текст">
				<input type="hidden" name="id" value="<?= $item->id ?>">
			</form>
		</li>
	<?php } ?>
	</ul>
	<?php } ?>
</div>
<script type="text/javascript">	
	function addElement(ev, _this) {
		ev.preventDefault();
		ev.stopPropagation();
		createEditModal(_this.nextElementSibling, '/controlpanel/data/updatesmtp/', 'Добавление SMTP');
	}
	
	function addLetter(ev, _this) {
		ev.preventDefault();
		ev.stopPropagation();
		createEditModal(_this.nextElementSibling, '/controlpanel/data/createletter/', 'Отправить письмо');
	}
</script>