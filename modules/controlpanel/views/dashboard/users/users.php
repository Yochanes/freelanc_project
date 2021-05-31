<?php 
	use yii\bootstrap\Dropdown;
	use app\models\User;
	
	$user = Yii::$app->user->identity;
?>
<form class="panel_menu">
	<div class="row">
		<div class="col-lg-3">
			<label for="select_make">Тип пользователя</label>
			<select class="form-control input-select" name="status">
				<option value="">Не выбрано</option>
				<option <?= (isset($_GET['role']) && $_GET['role'] == User::ROLE_CLIENT) ? 'selected' : '' ?> value="<?= User::ROLE_CLIENT ?>">Пользователь</option>
				<option <?= (isset($_GET['role']) && $_GET['role'] == User::ROLE_COMPANY) ? 'selected' : '' ?> value="<?= User::ROLE_COMPANY ?>">Компания</option>
			</select>
		</div>
		<div class="col-lg-3">
			<label for="select_year">Логин</label>
			<input class="form-control input-field" name="username" value="<?= Yii::$app->request->get('username') ?>">
		</div>
		<div class="col-lg-3">
			<label for="select_year">Имя</label>
			<input class="form-control input-field" name="display_name" value="<?= Yii::$app->request->get('display_name') ?>">
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 text-right">
			<button class="btn btn-primary" onclick="searchByForm(event,this,this.closest('form'))"><i class="fa fa-search"></i>Искать</button>
		</div>
	</div>
</form>
<div class="panel_content">
	<?= (empty($users) ? '<h2 class="text-center">На данный момент пользователи по заданным критериям отсутствуют</h2>' : '') ?>
	<?php if (!empty($users)) { ?>
	<ul class="item-list row">
	<?php foreach ($users as $usr) { ?>
		<li class="col-lg-12">
			<a href="<?=$usr->url ?>"><b><?= $usr['username']; ?></b> (<?= $usr['display_name']; ?>)</a>
			<div class="buttons pull-right">
				<?php if ($user->id != $usr->id) { ?>
				<a href="javascript:void(0)" onclick="createEditModal(this.nextElementSibling,'/actions/sendntf/','Новое сообщение')">Написать сообщение</a>
				<form class="hidden" class="add-form">
					<textarea name="text" data-title="Текст сообщения"></textarea>
					<div class="file_container">
						<input type="file" name="imgs">
						<button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">Добавить изображение</button>
					</div>
					<input type="hidden" name="receiver_id" value="<?= $usr->id ?>">
				</form>
				<?php } ?>
				<?php if ($user->id != $usr->id && $usr->state == User::STATE_UNLOCKED) { ?>
				<a href="javascript:void(0)" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/lockuser/','Блокировка пользователя')">Заблокировать</a>
				<form class="hidden" class="add-form">
					<textarea name="reason" data-title="Причина"></textarea>
					<input type="hidden" name="user_id" value="<?= $usr->id ?>">
				</form>
				<?php } ?>
				<?php if ($user->id != $usr->id && $usr->state == User::STATE_LOCKED) { ?>
				<a href="javascript:void(0)" onclick="saveForm(this.nextElementSibling, '/controlpanel/data/unlockuser/', this)">Разблокировать</a>
				<form class="hidden" class="add-form">
					<input type="hidden" name="user_id" value="<?= $usr->id ?>">
				</form>
				<?php } ?>
			</div>
		</li>
	<?php } ?>
	</ul>
	<?php } ?>
</div>
<script type="text/javascript">
	function searchByForm(ev, _this, form) {
		ev.preventDefault();
		ev.stopPropagation();
		const inps = form.querySelectorAll('[name]');
		const l = inps.length;
		
		let str = "";
		
		for (let i = 0; i < l; i++) {
			const inp = inps[i];
			const name = inp.getAttribute('name');
			let val, id;
			
			if (inp.tagName == 'INPUT') {
				if (inp.type == 'checkbox' || inp.type == 'radio') {
					val = inp.checked ? 1 : 0;
				} else if (inp.value.trim().length > 0) val = encodeURIComponent(inp.value.trim());
				if (inp.getAttribute('data-id') != null) id = inp.getAttribute('data-id');
			} else if (inp.tagName == 'SELECT' && inp.value.trim().length > 0) {
				val = encodeURIComponent(inp.value.trim());
				if (inp.options[inp.selectedIndex].getAttribute('data-id') != null) id = inp.options[inp.selectedIndex].getAttribute('data-id');
			} else if (inp.tagName == 'TEXTAREA' && inp.innerHTML.trim().length > 0) {
				val = encodeURIComponent(inp.innerHTML.trim());
				if (inp.getAttribute('data-id') != null) id = inp.getAttribute('data-id');
			}
			
			if (typeof val != 'string' || val.length == 0) continue;
			str += (str.length > 0 ? '&' : '') + name + '=' + val;
			if (typeof id == 'string' && id.length > 0) str += (str.length > 0 ? '&' : '') + name + '_id=' + id;
		}

		window.location.search = str;
	}
</script>