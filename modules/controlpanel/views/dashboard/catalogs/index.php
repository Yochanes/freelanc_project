<?php
  use app\models\catalogs\Catalog_Params;
?>

<div class="panel_menu flex hend">
  <section>
    <button class="btn btn-primary" onclick="addElement(event, this)"><i class="fa fa-plus"></i> Добавить каталог</button>
    <form class="hidden">
      <input type="text" name="title" value="" data-title="Заголовок каталога">
      <input name="subtitle" type="text" value="" data-title="Подзаголовок каталога">
      <input name="url" type="text" value="" data-title="URL каталога">
      <select name="product_group_id" data-title="Группа товаров">
        <?php foreach ($groups as $g) { ?>
          <option value="<?=$g->product_group_id?>"><?=$g->name?></option>
        <?php } ?>
      </select>
      <div class="array" data-title="Дополнительные ссылки">
        <div class="flex vcentered">
          <input placeholder="Текст ссылки" class="simple_input" type="text" name="catalog_link_text[]">
          <input placeholder="URL ссылки" class="simple_input" type="text" name="catalog_link_href[]">
        </div>
        <i class="fa fa-plus" onclick="addArrayElement(this)"></i>
      </div>
      <div class="array addable" data-title="Параметр">
        <div class="flex vcentered">
          <input placeholder="Заголовок параметра" class="simple_input" type="text" name="param_title[]">
          <select class="simple_input" name="param[]">
            <?php foreach (Catalog_Params::getParameterDefinitions() as $key => $val) { ?>
              <?php if (!$val) continue; ?>
              <option value="<?=$key?>"><?=$val?></option>
            <?php } ?>
            <?php foreach ($attribute_groups as $ag) { ?>
              <option value="<?=Catalog_Params::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP + $ag->attribute_group_id?>">
                <?=$ag->name?>
              </option>
            <?php } ?>
          </select>
        </div>
        <i class="fa fa-plus" onclick="addArrayElement(this)"></i>
      </div>
    </form>
  </section>
</div>
<div class="panel_content">
  <?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного каталога</h2>' : '') ?>
  <ul class="data-list drop-down-list flex hstart flex-wrap">
    <?php foreach ($items as $item) { ?>
      <li>
        <?= $item->title ?>
        <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deletecatalog/')"></i>
        <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatecatalog/','Редактирование каталога <?= $item->title ?>')"></i>
        <form class="hidden">
          <input type="hidden" name="id" value="<?=$item->id?>">
          <input type="text" name="title" value="<?=$item->title?>" data-title="Заголовок каталога">
          <input name="subtitle" type="text" value="<?=$item->subtitle?>" data-title="Подзаголовок каталога">
          <input name="url" type="text" value="<?=$item->url?>" data-title="URL каталога">
          <select name="product_group_id" data-title="Группа товаров">
            <?php foreach ($groups as $g) { ?>
              <option value="<?=$g->product_group_id?>" <?=$item->product_group_id == $g->product_group_id ? 'selected' : '' ?>>
                <?=$g->name?>
              </option>
            <?php } ?>
          </select>
          <div class="array" data-title="Дополнительные ссылки">
            <div class="copy-this">
              <?php $n = 0; ?>
              <?php foreach ($item->linksArray as $c) { ?>
                <div class="flex vcentered">
                  <input placeholder="Текст ссылки" class="simple_input" value="<?=$c->text?>" type="text" name="catalog_link_text[]">
                  <input placeholder="URL ссылки" class="simple_input" value="<?=$c->href?>" type="text" name="catalog_link_href[]">
                </div>
                <i class="fa fa-minus" onclick="delArrayElement(this.previousElementSibling.firstElementChild);this.remove()"></i>
              <?php } ?>
            </div>
            <div>
              <div class="flex vcentered">
                <input placeholder="Текст ссылки" class="simple_input" type="text" name="catalog_link_text[]">
                <input placeholder="URL ссылки" class="simple_input" type="text" name="catalog_link_href[]">
              </div>
              <i class="fa fa-plus" onclick="addArrayElement(this)"></i>
            </div>
          </div>
          <div class="array" data-title="Параметр">
            <div class="copy-this">
              <?php $n = 0; ?>
              <?php foreach ($item->paramsArray as $c) { ?>
                <div class="flex vcentered">
                  <input placeholder="Заголовок параметра" class="simple_input" value="<?=$c->param_title?>" type="text" name="param_title[]">
                  <select class="simple_input" name="param[]">
                    <?php foreach (Catalog_Params::getParameterDefinitions() as $key => $val) { ?>
                      <?php if (!$val) continue; ?>
                      <option value="<?=$key?>" <?=$c->param_type == $key ? 'selected' : '' ?>>
                        <?=$val?>
                      </option>
                    <?php } ?>
                    <?php foreach ($attribute_groups as $ag) { ?>
                      <option value="<?=Catalog_Params::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP + $ag->attribute_group_id?>" <?=$c->param_type == Catalog_Params::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP + $ag->attribute_group_id ? 'selected' : '' ?>>
                        <?=$ag->name?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
                <i class="fa fa-minus" onclick="delArrayElement(this.previousElementSibling.firstElementChild);this.remove()"></i>
              <?php } ?>
            </div>
            <div>
              <div class="flex vcentered">
                <input placeholder="Заголовок параметра" class="simple_input" type="text" name="param_title[]">
                <select class="simple_input" name="param[]">
                  <?php foreach (Catalog_Params::getParameterDefinitions() as $key => $val) { ?>
                    <?php if (!$val) continue; ?>
                    <option value="<?=$key?>"><?=$val?></option>
                  <?php } ?>
                  <?php foreach ($attribute_groups as $ag) { ?>
                    <option value="<?=Catalog_Params::CATALOG_PARAM_TYPE_PRODUCT_ATTRIBUTE_GROUP + $ag->attribute_group_id?>">
                      <?=$ag->name?>
                    </option>
                  <?php } ?>
                </select>
              </div>
              <i class="fa fa-plus" onclick="addArrayElement(this)"></i>
            </div>
          </div>
        </form>
      </li>
    <?php } ?>
  </ul>
</div>
<script type="text/javascript">
  function addElement(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updatecatalog/', 'Добавление каталога');
  }
</script>
