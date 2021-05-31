<?php
	$attribute_array = [
	  'make' => 'Марка',
    'model' => 'Модель',
    'year' => 'Год выпуска',
    'generation' => 'Поколение',
    'partnum' => 'Номер детали',
    'category' => 'Запчасть',
    'sku' => 'Артикул'
  ];

  foreach ($attribute_groups as $v) {
    $attribute_array['attribute_' . $v->attribute_group_id] = $v->name;
  }
?>
<style>
  aside > div > div {
    width: 20%;
    max-width: 20%;
    min-width: 20%;
    padding: 0 5px;
    text-align: left;
  }

  aside > div:first-child > div, aside > div > div:nth-child(4), aside > div > div:last-child {
    text-align: center;
  }

  aside > div > div > select, aside > div > div > [type=number] {
    width: 100%;
    height: 100%;
    border: none;
    border-radius: 0;
  }
</style>
<div class="panel_menu flex hend">
	<section>
		<button class="btn btn-success" onclick="addElement(event, this)"><i class="fa fa-plus"></i> Добавить группу</button>
		<form class="hidden">
			<input type="text" name="name" value="" required placeholder="Название группы" data-title="Название группы">
      <input data-title="URL группы" type="text" name="url">
      <select name="product_categories" required data-title="Категория товаров">
        <option value="">Не использовать</option>
        <option value="all">Все</option>
				<?php foreach($categories as $category) { ?>
				<option value="<?=$category->category_id ?>"><?=$category->name ?></option>
				<?php } ?>
			</select>
      <select name="main_attribute" required data-title="Основной атрибут">
        <option value="">Отсутствует</option>
        <option value="category">Категория</option>
        <option value="sku">Артикул</option>
        <?php foreach($attribute_groups as $attr) { ?>
          <option value="attribute;<?=mb_strtolower($attr->filter_name) ?>;<?=$attr->id?>">
            <?=$attr->name ?>
          </option>
        <?php } ?>
      </select>
			<div class="multi params-multi" data-title="Параметры товаров">
        <div class="flex hspaced vcentered">
          <div>Доступен</div>
          <div>Позиция</div>
          <div>Порядок</div>
          <div>Обязательный</div>
          <div>Выгрузка</div>
        </div>
				<?php foreach ($attribute_array as $k => $v) { ?>
				<div class="flex hspaced vcentered">
          <div>
					  <input type="checkbox" class="not_separate" onchange="this.value=this.checked?'<?=$k ?>':'';toggleNextEls(this,this.checked);" name="attribute_groups[]" data-value="<?=$k ?>">&nbsp;
					  <label><?=$v?></label>
          </div>
          <div style="display:none;">
            <select disabled name="attribute_groups_position[]">
              <option value="0">Основной</option>
              <option value="1">Дополнительный</option>
              <option value="2">Под формой</option>
            </select>
          </div>
          <div style="display:none;">
            <input type="number" disabled name="attribute_groups_sort[]" min="0" step="1" value="0">
          </div>
          <div style="display:none;">
            <input disabled type="checkbox" class="not_separate" value="<?=$k ?>_0" onchange="this.value=this.checked?'<?=$k ?>_1':'<?=$k ?>_0'" name="attribute_groups_required[]">&nbsp;
          </div>
          <div style="display:none;">
            <input disabled type="checkbox" class="not_separate" value="<?=$k ?>_0" onchange="this.value=this.checked?'<?=$k ?>_1':'<?=$k ?>_0'" name="attribute_groups_upload[]">&nbsp;
          </div>
				</div>
				<?php } ?>
			</div>
			<div class="multi" data-title="Группы производителей">
				<?php foreach ($make_groups as $v) { ?>
				<div class="flex hstart vcentered">
					<input type="checkbox" class="not_separate" onchange="this.value=this.checked?<?=$v->make_group_id ?>:''" name="make_groups[]" data-value="<?=$v->make_group_id ?>">&nbsp;
					<label><?= $v->name?></label>
				</div>
				<?php } ?>
			</div>
      <div class="file_container">
        <input type="file" name="image">
        <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
          Изменить изображение
        </button>
      </div>
      <input type="number" name="sort_order" data-title="Порядок сортировки" min="0" max="999" step="1">
      <input type="checkbox" name="is_default" data-title="Группа по-умолчанию" onchange="this.value=this.checked?1:0" value="0">
      <input type="checkbox" name="use_hint" data-title="Ссылка-подсказка" onchange="this.value=this.checked?1:0" value="1" checked>
      <input type="text" name="hint_link" data-title="Ссылка подсказки">
		</form>
	</section>
</div>
<div class="panel_content">
	<?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного элемента</h2>' : '') ?>
	<ul class="data-list drop-down-list flex hstart flex-wrap">
	<?php foreach ($items as $item) { ?>
		<li>
			<?= $item->name ?>
			<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->product_group_id ?>, '/controlpanel/data/deleteproductgroup/')"></i>
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updateproductgroup/', 'Редактирование элемента <?= $item->name ?>')"></i>
			<form class="hidden">
				<input data-title="Название группы" type="text" name="name" required value="<?= $item->name ?>">
        <input data-title="URL группы" type="text" name="url" value="<?= $item->url ?>">
				<select name="product_categories" required data-title="Категория товаров">
          <option value="">Не использовать</option>
          <option value="all" <?=$item->product_categories == 'all' ? 'selected' : '' ?>>Все</option>
					<?php foreach($categories as $category) { ?>
					<option value="<?=$category->category_id ?>" <?= $category->category_id == $item->product_categories ? 'selected' : '' ?>><?=$category->name ?></option>
					<?php } ?>
				</select>
        <select name="main_attribute" required data-title="Основной атрибут">
          <option value="">Отсутствует</option>
          <option value="category" <?=$item->main_attribute == 'category' ? 'selected' : '';?>>Категория</option>
          <option value="sku" <?=$item->main_attribute == 'sku' ? 'selected' : '';?>>Артикул</option>
          <?php foreach($attribute_groups as $attr) { ?>
            <option value="attribute;<?=mb_strtolower($attr->filter_name) ?>" <?=$item->main_attribute == 'attribute;' . mb_strtolower($attr->filter_name) ? 'selected' : ''?>>
              <?=$attr->name ?>
            </option>
          <?php } ?>
        </select>
        <div class="multi params-multi" data-title="Параметры товаров">
          <div class="flex hspaced vcentered">
            <div>Доступен</div>
            <div>Позиция</div>
            <div>Порядок</div>
            <div>Обязательный</div>
            <div>Выгрузка</div>
          </div>
        <?php foreach ($attribute_array as $k => $v) { ?>
          <?php $exists = isset($item->attribute_groups[$k]); ?>
          <div class="flex hspaced vcentered">
            <div>
              <input type="checkbox" class="not_separate" <?=$exists? 'checked value="' . $k . '"' : '' ?> class="not_separate" onchange="this.value=this.checked?'<?=$k ?>':'';toggleNextEls(this,this.checked);" name="attribute_groups[]" data-value="<?=$k ?>">&nbsp;
              <label><?=$v?></label>
            </div>
            <div style="<?=$exists ? '' : 'display:none;'?>">
              <select <?=$exists ? '' : 'disabled'?> name="attribute_groups_position[]">
                <option value="0" <?=$exists && $item->attribute_groups[$k]['position'] == 0 ? 'selected' : '' ?>>Основной</option>
                <option value="1" <?=$exists && $item->attribute_groups[$k]['position'] == 1 ? 'selected' : '' ?>>Дополнительный</option>
                <option value="2" <?=$exists && $item->attribute_groups[$k]['position'] == 2 ? 'selected' : '' ?>>Под формой</option>
              </select>
            </div>
            <div style="<?=$exists ? '' : 'display:none;'?>">
              <input type="number" <?=$exists ? '' : 'disabled'?> name="attribute_groups_sort[]" min="0" step="1" value="<?=$exists ? $item->attribute_groups[$k]['sort_order'] : 0 ?>">
            </div>
            <div style="<?=$exists ? '' : 'display:none;'?>">
              <input type="checkbox" <?=$exists ? '' : 'disabled'?> <?=$exists && $item->attribute_groups[$k]['is_required'] ? 'checked value="'.$k.'_1"' : ' value="'.$k.'_0"' ?> class="not_separate" onchange="this.value=this.checked?'<?=$k ?>_1':'<?=$k ?>_0'" name="attribute_groups_required[]">&nbsp;
            </div>
            <div style="<?=$exists ? '' : 'display:none;'?>">
              <input type="checkbox" <?=$exists ? '' : 'disabled'?> <?=$exists && @$item->attribute_groups[$k]['upload'] ? 'checked value="'.$k.'_1"' : ' value="'.$k.'_0"' ?> class="not_separate" onchange="this.value=this.checked?'<?=$k ?>_1':'<?=$k ?>_0'" name="attribute_groups_upload[]">&nbsp;
            </div>
          </div>
        <?php } ?>
        </div>
				<div class="multi" data-title="Группы производителей">
					<?php foreach ($make_groups as $v) { ?>
					<div class="flex hstart vcentered">
						<input type="checkbox" onchange="this.value=this.checked?<?=$v->make_group_id ?>:''" class="not_separate" name="make_groups[]" <?= in_array($v, $item->makeGroupsArray) ? 'checked value="'.$v->make_group_id.'"' : ''?> data-value="<?=$v->make_group_id ?>">&nbsp;
						<label><?= $v->name?></label>
					</div>
					<?php } ?>
				</div>
        <div class="file_container">
          <?= (file_exists(Yii::$app->basePath . $item->image) && !empty($item->image) ? '<img src="' . $item->image . '" />' : '') ?>
          <input type="file" name="image">
          <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
            Изменить изображение
          </button>
        </div>
        <input type="number" name="sort_order" data-title="Порядок сортировки" min="0" max="999" step="1" value="<?=$item->sort_order?>">
        <input type="checkbox" name="is_default" data-title="Группа по-умолчанию" onchange="this.value=this.checked?1:0" value="<?=$item->is_default ?>" <?=$item->is_default ? 'checked' : '' ?>>
        <input type="checkbox" name="use_hint" data-title="Ссылка-подсказка" onchange="this.value=this.checked?1:0" value="<?=$item->use_hint ?>" <?=$item->use_hint ? 'checked' : '' ?>>
        <input type="text" name="hint_link" data-title="Ссылка подсказки" value="<?=$item->hint_link ?>">
        <input type="hidden" name="product_group_id" value="<?=$item->product_group_id ?>">
			</form>
		</li>
	<?php } ?>
	</ul>
</div>
<script type="text/javascript">
	function addElement(ev, _this) {
		ev.preventDefault();
		ev.stopPropagation();
		createEditModal(_this.nextElementSibling, '/controlpanel/data/updateproductgroup/', 'Добавление элемента');
	}

	function toggleNextEls(_this, show) {
    if (show){
      $(_this).parent().nextAll().show().each((i, e) => {
        e.firstElementChild.removeAttribute('disabled')
      })
    } else {
      $(_this).parent().nextAll().hide().each((i, e) => {
        e.firstElementChild.setAttribute('disabled', 'disabled')
      })
    }
  }
</script>
