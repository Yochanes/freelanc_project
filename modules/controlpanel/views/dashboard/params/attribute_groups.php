<?php

?>
<div class="panel_menu flex hend">
  <section>
    <button class="btn btn-success" onclick="addElement(event, this)"><i class="fa fa-plus"></i> Добавить группу
    </button>
    <form class="hidden">
      <input type="text" name="name" value="" placeholder="Название группы" data-title="Название группы">
      <input type="text" name="filter_name" value="" placeholder="Название фильтра" data-title="Название фильтра">
      <input data-title="Синонимы" type="text" class="array" name="alt_names[]">
      <input type="text" name="url_template" value="" placeholder="" data-title="Шаблон URL">
      <input type="text" name="catalog_prefix" value="" placeholder="" data-title="Текст каталога перед значением">
      <input type="text" name="catalog_suffix" value="" placeholder="" data-title="Текст каталога после значения">
      <input type="checkbox" name="use_in_car_form" data-title="Использовать при добавлении авто" onchange="this.value=this.checked?1:0" value="0">
      <input type="checkbox" name="use_in_category" data-title="Использовать импорте категорий" onchange="this.value=this.checked?1:0" value="0">
      <input type="checkbox" name="important" onchange="this.value=this.checked?1:0" value="1" checked data-title="Отображение в списке товаров">
    </form>
  </section>
  <section>
    <button class="btn btn-success" onclick="addAttribute(event, this)"><i class="fa fa-plus"></i> Добавить аттрибут
    </button>
    <form class="hidden">
      <select name="attribute_group_id" data-title="Группа атрибутов">
        <?php foreach ($items as $group) { ?>
          <?php if (!$group->attribute_group_id) continue; ?>
          <option value="<?= $group->attribute_group_id ?>"<?= $group->attribute_group_id == $group->attribute_group_id ? ' selected' : '' ?>><?= $group->name ?></option>
        <?php } ?>
      </select>
      <input type="text" name="value" data-title="Значение аттрибута">
    </form>
  </section>
</div>
<div class="panel_content">
  <?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного элемента</h2>' : '') ?>
  <ul class="data-list drop-down-list flex hstart vstart flex-wrap">
    <?php foreach ($items as $item) { ?>
      <li id="attr_group_<?= $item->attribute_group_id ?>">
        <?= (!empty($item->attributesArray) ? '<i class="fa fa-plus pull-left" onclick="toggleList(this);"></i> ' : '') ?>
        <?= $item->name ?>
        <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->attribute_group_id ?>, '/controlpanel/data/deleteattributegroup/')"></i>
        <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updateattributegroup/', 'Редактирование элемента <?= $item->name ?>')"></i>
        <form class="hidden">
          <input data-title="Название группы" type="text" name="name" value="<?= $item->name ?>">
          <input type="text" name="filter_name" placeholder="Название фильтра" data-title="Название фильтра" value="<?= $item->filter_name ?>">
          <input type="text" name="url_template" value="<?= $item->url_template ?>" placeholder="" data-title="Шаблон URL">
          <input type="text" name="catalog_prefix" value="<?= $item->catalog_prefix ?>" placeholder="" data-title="Текст каталога перед значением">
          <input type="text" name="catalog_suffix" value="<?= $item->catalog_suffix ?>" placeholder="" data-title="Текст каталога после значения">
          <input data-title="Синонимы" type="text" class="array" name="alt_names[]" value="<?= $item->alt_names ?>">
          <input type="checkbox" name="use_in_car_form" data-title="Использовать при добавлении авто" onchange="this.value=this.checked?1:0" value="<?= $item->use_in_car_form ?>" <?= $item->use_in_car_form ? 'checked' : '' ?>>
          <input type="checkbox" name="use_in_category" data-title="Использовать импорте категорий" onchange="this.value=this.checked?1:0" value="<?= $item->use_in_category ?>" <?= $item->use_in_category ? 'checked' : '' ?>>
          <input type="checkbox" name="important" onchange="this.value=this.checked?1:0" value="<?= $item->important ?>" <?= $item->important ? 'checked' : '' ?> data-title="Отображение в списке товаров">
          <input type="hidden" name="attribute_group_id" value="<?= $item->attribute_group_id ?>">
        </form>
        <i class="fa fa-upload" onclick="openImportAttributesForm(event, this, this.nextElementSibling, 'Импорт атрибутов в группу <?= $item->name ?>')"></i>
        <form class="hidden">
          <select name="sheet_name" data-title="Страница">
            <option value="">Выберите файл для импорта</option>
          </select>
          <div class="file_container">
            <input type="file" name="upload_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
            <button class="btn btn-primary" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">Добавить
              файл
            </button>
          </div>
          <input type="hidden" name="attribute_group_id" value="<?= $item->attribute_group_id ?>">
        </form>
        <i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updateproductattribute/','Добавление аттрибута')"></i>
        <form class="hidden" class="add-form">
          <select name="attribute_group_id" data-title="Группа атрибутов">
            <?php foreach ($items as $group) { ?>
              <?php if (!$group->attribute_group_id) continue; ?>
              <option value="<?= $group->attribute_group_id ?>"<?= $item->attribute_group_id == $group->attribute_group_id ? ' selected' : '' ?>><?= $group->name ?></option>
            <?php } ?>
          </select>
          <input type="text" name="value" data-title="Значение аттрибута">
          <input type="text" name="catalog_text" data-title="Текст в каталоге">
          <input data-title="Варианты значений" type="text" class="array" name="alt_values[]">
        </form>
        <?php if (!empty($item->attributesArray)) { ?>
          <ul class="drop-down-list sub-drop-down-list">
            <?php foreach ($item->attributesArray as $attr) { ?>
              <li>
                <?= $attr->value ?>
                <i class="fa fa-times" onclick="deleteElement(this,this.closest('li'), <?= $attr->attribute_id ?>, '/controlpanel/data/deleteproductattribute/')"></i>
                <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updateproductattribute/', 'Редактирование элемента <?= $attr->value ?>')"></i>
                <form class="hidden">
                  <select name="attribute_group_id" data-title="Группа атрибутов">
                    <?php foreach ($items as $group) { ?>
                      <?php if (!$group->attribute_group_id) continue; ?>
                      <option value="<?= $group->attribute_group_id ?>"<?= $attr->attribute_group_id == $group->attribute_group_id ? ' selected' : '' ?>><?= $group->name ?></option>
                    <?php } ?>
                  </select>
                  <input type="text" name="value" data-title="Значение аттрибута" value="<?= $attr->value ?>">
                  <input type="text" name="catalog_text" data-title="Текст в каталоге" value="<?= $attr->catalog_text ?>">
                  <input data-title="Варианты значений" type="text" class="array" name="alt_values[]" value="<?= $attr->alt_values ?>">
                  <input type="hidden" name="attribute_id" value="<?= $attr->attribute_id ?>">
                </form>
              </li>
            <?php } ?>
          </ul>
        <?php } ?>
      </li>
    <?php } ?>
  </ul>
</div>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<?php
  use \app\modules\controlpanel\assets\AdminAsset;
  AdminAsset::register($this);
  $this->assetBundles['app\modules\controlpanel\assets\AdminAsset']->js[] = 'js/attributes_import.js';
?>
<script type="text/javascript">
  const __csrf = ['<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->getCsrfToken() ?>'];

  function addElement(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updateattributegroup/', 'Добавление элемента');
  }

  function addAttribute(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updateattribute/', 'Добавление элемента');
  }

  function importAttributes(ev, _this) {

  }
</script>
