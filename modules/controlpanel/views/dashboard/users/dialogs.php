<?php 
	use yii\bootstrap\Dropdown;
	use app\models\User;
	
	$user = Yii::$app->user->identity;
	use \app\models\helpers\Helpers;
?>
<form class="panel_menu">
	<div class="row">
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
	<?= (empty($dialogs) ? '<h2 class="text-center">На данный момент диалоги по заданным критериям отсутствуют</h2>' : '') ?>
	<?php if (!empty($dialogs)) { ?>
	<ul class="item-list row">
	<?php foreach ($dialogs as $dialog) { ?>
		<?php $message = $dialog->lastMessage; ?>
		<li class="col-lg-12">
			<?php if ($user->id == $message->sender_id) { ?>
			<a href="dialog/<?= $dialog->id ?>/">Я<?= $message && $message->text ? ': ' . $message->text : '' ?></a>
			<?php } else { ?>
			<a href="dialog/<?= $dialog->id ?>/" target="_blank"><?= $message->sender['display_name']; ?><?= $message && $message->text ? ': ' . $message->text : '' ?></a>
			<?php } ?>
			<div class="buttons pull-right">
				<a href="/controlpanel/dashboard/messages/?sender_id=<?= $dialog->sender_id ?>">Диалоги отправителя</a>
				<a href="/controlpanel/dashboard/messages/?receiver_id=<?= $dialog->receiver_id ?>">Диалоги с получателем</a>
				<a href="javascript:void(0)" onclick="createEditModal(this.nextElementSibling,'/actions/sendntf/','Новое сообщение')">Написать сообщение</a>
				<form class="hidden" class="add-form">
					<textarea name="text" data-title="Текст сообщения"></textarea>
					<div class="file_container">
						<input type="file" name="imgs">
						<button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">Добавить изображение</button>
					</div>
					<input type="hidden" name="receiver_id" value="<?= $user->id == $dialog->sender_id ? $dialog->receiver_id : $dialog->sender_id ?>">
					<input type="hidden" name="dialog_id" value="<?= $dialog->id ?>">
				</form>
				<i class="fa fa-trash" onclick="deleteElement(this,this.closest('li'),<?= $dialog->id ?>,'/controlpanel/data/deletedialog/')"></i>
				<span><?= Helpers::getDateDiff($dialog->date_updated) ?> назад</span>
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