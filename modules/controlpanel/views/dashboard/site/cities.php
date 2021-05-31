<?php 
	use yii\widgets\LinkPager;
?>
<div class="panel_menu flex hend">
	<section>
		<button class="btn btn-primary" onclick="addElement(event, this)"><i class="fa fa-plus"></i> Добавить страну</button>
		<form class="hidden">
			<input type="text" name="name" data-title="Название города">
			<input type="text" data-title="Домен страны" name="domain">
            <input type="text" data-title="Код страны" name="code">
		</form>
	</section>
	<section>
		<button class="btn btn-primary" onclick="addCity(event, this)"><i class="fa fa-plus"></i> Добавить город</button>
		<form class="hidden">
			<select name="country_id" data-title="Страна">
				<?php foreach ($items as $item) { ?>
				<option value="<?=$item->id ?>"><?=$item->name ?></option>
				<?php } ?>
			</select>
			<input type="text" name="name" data-title="Название города">
			<input type="text" name="region" data-title="Регион">
			<input type="text" data-title="Префикс субдомена" name="domain">
		</form>
	</section>
    <section>
        <button class="btn btn-primary" onclick="syncCities(event, this)">Синхронизировать данные доставки</button>
        <form class="hidden">
            <input type="text" name="code" data-title="Код страны">
        </form>
    </section>
</div>
<div class="panel_content">
	<?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одной страны</h2>' : '') ?>
	<ul class="data-list drop-down-list flex vstart hstart flex-wrap">
	<?php foreach ($items as $item) { ?>
		<li id="country_<?= $item->id ?>">
			<?= (!empty($item->cities) ? '<i class="fa fa-plus" onclick="toggleList(this);"></i> ' : '<i class="fa fa-minus"></i> ') ?><?= $item->name ?>
			<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deletecountry/')"></i>
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatecountry/','Редактирование страны <?= $item->name ?>')"></i>
			<form class="hidden">
				<input type="text" data-title="Название страны" name="name" value="<?= $item->name ?>">
				<input type="text" data-title="Домен страны" name="domain" value="<?= $item->domain ?>">
                <input type="text" data-title="Код страны" name="code" value="<?= $item->code ?>">
				<input type="hidden" name="id" value="<?= $item->id ?>">
			</form>
			<i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatecity/','Добавление города')"></i>
			<form class="hidden" class="add-form">
				<select name="country_id" data-title="Страна">
					<?php foreach ($items as $country) { ?>
					<option value="<?=$country->id ?>" <?=$country->id == $item->id ? 'selected' : '' ?>><?=$country->name ?></option>
					<?php } ?>
				</select>
				<input type="text" name="name" data-title="Название города">
				<input type="text" name="region" data-title="Регион">
				<input type="text" data-title="Префикс субдомена" name="domain">
			</form>
			<ul class="drop-down-list sub-drop-down-list">
				<?php foreach ($item->cities as $city) { ?>
				<li id="city_<?= $city->id ?>">
					<?= $city->name ?>
					<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $city->id ?>, '/controlpanel/data/deletecity/')"></i>
					<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatecity/', 'Редактирование города <?= $city->name ?>')"></i>
					<form class="hidden" class="add-form">
						<select name="country_id" data-title="Страна">
							<?php foreach ($items as $country) { ?>
							<option value="<?=$country->id ?>" <?=$country->id == $city->country_id ? 'selected' : '' ?>><?=$country->name ?></option>
							<?php } ?>
						</select>
						<input type="text" name="name" data-title="Название города" value="<?=$city->name ?>">
						<input type="text" name="region" data-title="Регион" value="<?=$city->region ?>">
						<input type="text" data-title="Домен города" name="domain" value="<?=$city->domain ?>">
						<input type="hidden" name="id" value="<?= $city->id ?>">
					</form>
				</li>
				<?php } ?>
			</ul>
		</li>
	<?php } ?>
	</ul>
	<div class="flex hcentered vstart">
		<?= LinkPager::widget(['pagination' => $pagination]) ?>
	</div>
</div>
<script type="text/javascript">
	function addElement(ev, _this) {
		ev.preventDefault();
		ev.stopPropagation();
		createEditModal(_this.nextElementSibling, '/controlpanel/data/updatecountry/', 'Добавление страны');
	}
	
	function addCity(ev, _this) {
		ev.preventDefault();
		ev.stopPropagation();
		createEditModal(_this.nextElementSibling, '/controlpanel/data/updatecity/', 'Добавление города');
	}

	function syncCities(ev, _this) {
        ev.preventDefault();
        ev.stopPropagation();
        createEditModal(_this.nextElementSibling, '/controlpanel/data/syncgtdcities/', 'Синхронизация данных о доставке');
    }
</script>