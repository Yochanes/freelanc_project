<?php 
	use yii\bootstrap\Dropdown;
	use app\models\Products;

	$get = Yii::$app->request;
?>
<form class="panel_menu">
	<div class="row">
		<div class="col-lg-3">
			<label for="select_make">Статус объявления</label>
			<select class="form-control input-select" name="status">
				<option <?= (!isset($_GET['status']) ? 'selected' : '') ?> value="">Любой статус</option>
				<option <?= ($get->get('status') === Products::STATE_ACTIVE) ? 'selected' : '' ?> value="<?= Products::STATE_ACTIVE ?>">Активно</option>
				<option <?= ($get->get('status') === Products::STATE_LOCKED) ? 'selected' : '' ?> value="<?= Products::STATE_LOCKED ?>">Заблокировано</option>
			</select>
		</div>
		<div class="col-lg-3">
			<label for="select_make">Марка авто</label>
			<select class="form-control input-select" name="make" id="select_make" onchange="loadModelList(this);"><?=$option_make ?></select>
		</div>
		<div class="col-lg-3">
			<label for="select_model">Модель авто</label>
			<select class="form-control input-select" name="model" id="select_model" <?= (isset($option_model) ? '' : 'disabled') ?> onchange="loadGenerationsList(this)">
				<?= (isset($option_model) ? $option_model : '') ?>
			</select>
		</div>
		<div class="col-lg-3">
			<label for="select_city">Город</label>
			<select class="form-control input-select" name="city" id="select_city"><?=$select_city ?></select>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-3">
			<label for="select_year">Год</label>
			<select class="form-control input-select" name="year" id="select_year"><?=$select_year ?></select>
		</div>
		<div class="col-lg-3">
			<label for="select_generation">Поколение</label>
			<select class="form-control input-select" name="generation" id="select_generation" <?= (isset($gen_list) ? '' : 'disabled') ?> >
				<?= isset($gen_list) ? $gen_list : '' ?>
			</select>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 text-right">
			<button class="btn btn-primary" onclick="searchByForm(event,this,this.closest('form'))"><i class="fa fa-search"></i>Искать</button>
			<a class="btn btn-warning" onclick="window.location.href=window.location.pathname">Очистить</a>
		</div>
	</div>
</form>
<div class="panel_content">
	<?= (empty($products) ? '<h2 class="text-center">На данный момент заявки по заданным критериям отсутствуют</h2>' : '') ?>
	<?php if (!empty($products)) { ?>
	<ul class="item-list row">
	<?php foreach ($products as $product) { ?>
		<li class="col-lg-12">
			<a href="<?= $product->url ?>"><?=$product->name?></a>
			<div class="buttons pull-right">
				<a href="javascript:void(0)" onclick="searchByParam({'user_id':<?= $product['user_id'] . (!$get->get('status') ? ',status:' . Products::STATE_UNCHECKED : '') ?>})">
					Заявки пользователя
				</a>
				<?php if ($product['status'] == Products::STATE_ACTIVE) { ?>
				<a href="javascript:void(0)" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/lockrequest/','Блокировка объявления')">Заблокировать</a>
				<form class="hidden" class="add-form">
					<textarea name="reason" data-title="Причина"></textarea>
					<input type="hidden" name="id" value="<?= $product->id ?>">
				</form>
				<?php } else if ($product['status'] == Products::STATE_LOCKED) { ?>
				<a href="javascript:void(0)" onclick="saveForm(this.nextElementSibling,'/controlpanel/data/unlockrequest/',this)">Разблокировать</a>
				<form class="hidden" class="add-form"><input type="hidden" name="id" value="<?= $product->id ?>"></form>
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