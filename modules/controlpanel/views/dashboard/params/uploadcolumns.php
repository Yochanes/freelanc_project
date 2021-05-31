<?php

use app\models\helpers\Helpers;

$column_vals = [
  'марка' => 'make',
  'модель' => 'model',
  'поколение' => 'generation',
  'год' => 'year',
  'категория' => 'category',
  'артикул' => 'sku',
  'номер запчасти' =>'partnum',
  'количество' => 'quantity',
  'описание' => 'text',
  'краткое описание' => 'short_description',
  'валюта' => 'currency',
  'цена' => 'price',
  'скидка' => 'sale',
  'фото' => 'image',
];

$column_val_names = [];

foreach ($column_vals as $key => $val) {
  $column_val_names[$val] = $key;
}

$groups_id = [];

foreach ($groups as $g) {
  $groups_id[$g->product_group_id] = $g;
}

$param_names = [];

foreach ($items as $item) {
  if (!isset($param_names[$item->param_name])) {
    $param_names[$item->param_name] = [];
  }

  $param_names[$item->param_name][] = $item;
}

?>
<div class="panel_menu flex hend">
  <section>
    <button class="btn btn-primary" onclick="addElement(event,this);const form=document.querySelector('.modal-wrapper form');handleGroupChange(form.querySelector('select').value,form)">
      <i class="fa fa-plus"></i> Добавить параметры
    </button>
    <form class="hidden">
      <select name="group_id" data-title="Группа товаров (необязательно)">
        <option value="">Не выбрано</option>
        <?php foreach ($groups as $g) { ?>
        <option value="<?=$g->product_group_id?>"><?=$g->name?></option>
        <?php } ?>
      </select>
      <input type="text" name="text_value" data-title="Название в файле">
      <select class="simple_input" name="param_name" data-title="Параметр">
        <option value="">Не выбрано</option>
        <?php foreach ($column_vals as $key => $val) { ?>
          <option value="<?=$val?>"><?=Helpers::mb_ucfirst($key)?></option>
        <?php } ?>
      </select>
    </form>
  </section>
</div>
<div class="panel_content">
  <?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного параметра</h2>' : '') ?>
  <ul class="data-list drop-down-list flex hstart vstart flex-wrap">
    <?php foreach ($param_names as $param_name => $items) { ?>
      <li>
        <i class="fa fa-plus pull-left" onclick="toggleList(this)"></i>
        <?=(isset($column_val_names[$param_name]) ? Helpers::mb_ucfirst($column_val_names[$param_name]) : $param_name); ?>
        <i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/uploadcolumn/','Добавление параметров')"></i>
        <form class="hidden" class="add-form">
          <select name="group_id" data-title="Группа товаров (необязательно)">
            <option value="">Не выбрано</option>
            <?php foreach ($groups as $g) { ?>
              <option value="<?=$g->product_group_id?>">
                <?=$g->name?>
              </option>
            <?php } ?>
          </select>
          <input type="text" name="text_value" data-title="Название в файле" value="">
          <select class="simple_input" name="param_name" data-title="Параметр">
            <option value="">Не выбрано</option>
            <?php foreach ($column_vals as $key => $val) { ?>
              <option value="<?=$val?>" <?=$param_name == $val ? 'selected' : ''?>>
                <?=Helpers::mb_ucfirst($key)?>
              </option>
            <?php } ?>
          </select>
          <input name="id" type="hidden" value="<?=$item->id?>">
        </form>
        <ul class="drop-down-list sub-drop-down-list">
          <?php foreach ($items as $item) { ?>
            <li>
              <?=$item->text_value?>
              <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deleteuploadcolumn/')"></i>
              <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/uploadcolumn/', 'Редактирование параметров')"></i>
              <form class="hidden">
                <select name="group_id" data-title="Группа товаров (необязательно)">
                  <option value="">Не выбрано</option>
                  <?php foreach ($groups as $g) { ?>
                    <option value="<?=$g->product_group_id?>" <?=$item->group_id == $g->id ? 'selected' : ''?>>
                      <?=$g->name?>
                    </option>
                  <?php } ?>
                </select>
                <input type="text" name="text_value" data-title="Название в файле" value="<?=$item->text_value?>">
                <select class="simple_input" name="param_name" data-title="Параметр">
                  <option value="">Не выбрано</option>
                  <?php foreach ($column_vals as $key => $val) { ?>
                    <option value="<?=$val?>" <?=$item->param_name == $val ? 'selected' : ''?>>
                      <?=Helpers::mb_ucfirst($key)?>
                    </option>
                  <?php } ?>
                </select>
                <input name="id" type="hidden" value="<?=$item->id?>">
              </form>
            </li>
          <?php } ?>
        </ul>
      </li>
    <?php } ?>
  </ul>
</div>
<script type="text/javascript">
  function addElement(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/uploadcolumn/', 'Добавление параметров');
  }
</script>
