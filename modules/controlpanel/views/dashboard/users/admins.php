<?php 
	use yii\bootstrap\Dropdown;
	
	use app\models\User;
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
	<?= (empty($users) ? '<h2 class="text-center">На данный момент пользователи по заданным критериям отсутствуют</h2>' : '') ?>
	<?php if (!empty($users)) { ?>
	<ul class="item-list row">
	<?php foreach ($users as $user) { ?>
		<li class="col-lg-12">
			<a href="<?=$user->url?>"><b><?= $user['username']; ?></b> (<?= $user['display_name']; ?>)</a>
			<div class="pull-right">
				
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