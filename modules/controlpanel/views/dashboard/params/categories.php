<?php
	use yii\widgets\LinkPager;
?>
<div class="panel_menu flex hend">
	<section>
		<button class="btn btn-primary" onclick="addElement(event, this)">
      <i class="fa fa-plus"></i> Добавить элемент
    </button>
		<form class="hidden">
			<input type="text" name="name" value="" data-title="Название категории">
      <input data-title="Показывать" type="text" class="array" name="connected_category[]">
			<input data-title="Синонимы" type="text" class="array" name="synonym[]">
      <select name="catalog_category_id" data-title="Категория каталога">
      <?php foreach ($catalog as $cat) { ?>
        <optgroup label="<?=$cat['name'] ?>">
          <?php foreach ($cat->children as $catc) { ?>
            <option value="<?=$catc->id ?>">
              <?=$catc->name ?>
            </option>
          <?php } ?>
        </optgroup>
      <?php } ?>
      </select>
      <div class="multi" data-title="Обязательные параметры">
        <?php foreach ($attribute_groups as $v) { ?>
          <div class="flex hstart vcentered">
            <input type="checkbox" onchange="this.value=this.checked?<?= $v->attribute_group_id ?>:''" class="not_separate" name="attributes_required[]" data-value="<?= $v->attribute_group_id ?>">&nbsp;
            <label><?= $v->name ?></label>
          </div>
        <?php } ?>
      </div>
      <input type="checkbox" name="generation_required" data-title="Поколение обязательно" onchange="this.value=this.checked?1:0" value="0">
      <input type="checkbox" name="partnum_required" data-title="Номер обязателен" onchange="this.value=this.checked?1:0" value="0">
    </form>
	</section>
	<section>
		<input type="text" name="name" class="simple_input" value="<?= Yii::$app->request->get('name') ?>" placeholder="Название категории">
		<button class="btn btn-primary" onclick="search('name',this.previousElementSibling.value)"><i class="fa fa-search"></i> Искать</button>
	</section>
  <section>
    <button class="btn btn-success" id="buildTreeBtn" onclick="buildTree(event,this)">Обновить связи категорий</button>
    <button class="btn btn-success" onclick="importCategories(event,this)"><i class="fa fa-plus"></i>
      Импортировать список запчастей
    </button>
    <form class="hidden">
      <input type="checkbox" name="clear" data-title="Удалить существующие">
      <div class="file_container">
        <input type="file" name="upload_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
        <button class="btn btn-primary" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
          Добавить файл
        </button>
      </div>
    </form>
  </section>
</div>
<div class="panel_content">
	<?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного элемента</h2>' : '') ?>
	<ul class="data-list drop-down-list flex hstart flex-wrap">
	<?php foreach ($items as $item) { ?>
		<li>
			<?= $item->name ?>
			<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->category_id ?>, '/controlpanel/data/deletecategory/')"></i>
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatecategory/','Редактирование элемента <?= $item->name ?>')"></i>
			<form class="hidden">
				<input data-title="Название категории" type="text" name="name" value="<?= $item->name ?>">
        <input data-title="Показывать" type="text" class="array" name="connected_category[]" value="<?= $item->connected_category ?>">
        <input data-title="Синонимы" type="text" class="array" name="synonym[]" value="<?= $item->synonym ?>">
        <select name="catalog_category_id" data-title="Категория каталога">
        <?php foreach ($catalog as $cat) { ?>
          <optgroup label="<?=$cat['name'] ?>">
            <?php foreach ($cat->children as $catc) { ?>
              <option value="<?=$catc->id ?>" <?=$item->catalog_category_id == $catc->id ? 'selected' : '' ?>>
                <?=$catc->name ?>
              </option>
            <?php } ?>
          </optgroup>
        <?php } ?>
        </select>
        <div class="multi" data-title="Обязательные параметры">
          <?php foreach ($attribute_groups as $v) { ?>
            <div class="flex hstart vcentered">
              <input type="checkbox" <?=in_array($v->attribute_group_id, $item->attributes_required) ? 'checked' : '' ?> onchange="this.value=this.checked?<?= $v->attribute_group_id ?>:''" class="not_separate" name="attributes_required[]" data-value="<?= $v->attribute_group_id ?>">&nbsp;
              <label><?= $v->name ?></label>
            </div>
          <?php } ?>
        </div>
        <input type="checkbox" name="generation_required" <?=$item->generation_required ? 'checked' : '' ?> data-title="Поколение обязательно" onchange="this.value=this.checked?1:0" value="0">
        <input type="checkbox" name="partnum_required" <?=$item->partnum_required ? 'checked' : '' ?> data-title="Номер обязателен" onchange="this.value=this.checked?1:0" value="0">
				<input type="hidden" name="category_id" value="<?= $item->category_id ?>">
			</form>
		</li>
	<?php } ?>
	</ul>
	<div class="flex hcentered vstart">
		<?= LinkPager::widget(['pagination' => $pagination]) ?>
	</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.8.0/jszip.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.8.0/xlsx.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xls/0.7.4-a/xls.js"></script>
<script type="text/javascript">
  function addElement(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updatecategory/', 'Добавление элемента');
  }

  function search(key, val) {
    const sp = new URLSearchParams(window.location.search);

    if (val.length > 0) {
      if (sp.has('page')) sp.delete('page');
      sp.set(key, val);
    } else sp.delete(key);

    window.location.search = sp.toString();
  }

  const category_columns = {
    'каталог': 'name',
    'поколение': 'generation_required',
    'номер': 'partnum_required',
    'кузов': 'attributes_required',
    'объем': 'attributes_required',
    'топливо': 'attributes_required',
    'коробка': 'attributes_required',
    'привод': 'attributes_required',
    'точное вхождение кс': 'synonym',
    'вордстат копия кс': 'synonym',
    'главная': 'override_parent',
    'аналог 1': 'synonym',
    'аналог 2': 'synonym',
    'аналог 3': 'synonym',
    'аналог 4': 'synonym',
    'аналог 5': 'synonym',
    'аналог 6': 'synonym',
    'аналог 7': 'synonym',
    'аналог 8': 'synonym',
    'аналог 9': 'synonym',
    'аналог 10': 'synonym',
    'аналог 11': 'synonym',
    'аналог 12': 'synonym',
    'аналог 13': 'synonym',
    'аналог 14': 'synonym',
    'аналог 15': 'synonym',
    'аналог 16': 'synonym',
    'аналог 17': 'synonym',
    'аналог 18': 'synonym',
    'аналог 19': 'synonym',
    'аналог 20': 'synonym',
    'категория': 'catalog_category',
    'подкатегория': 'catalog_subcategory'
  };

  const attribute_groups = {
    <?php foreach($attribute_groups as $attr) {

      $attributesArray = [];

      if ($attr->use_in_category) {
        foreach ($attr->attributesArray as $aa) {
          $arr = $aa->getAttributes(['attribute_id', 'value', 'alt_values', 'url']);
          $arr['values'] = [];

          if ($arr['alt_values']) {
            $arr['values'] = explode(';', $arr['alt_values']);
          }

          $arr['values'][] = $arr['value'];
          $attributesArray[] = $arr;
        }
      }

      echo '"' . trim(mb_strtolower($attr['name'])) . '": {id:' . $attr['attribute_group_id'] . ',values:' . json_encode($attributesArray) . '},';
      echo '"' . trim(mb_strtolower($attr['filter_name'])) . '":{id:' . $attr['attribute_group_id'] . ',values:' . json_encode($attributesArray) . '},';

      if ($attr->alt_names) {
        $synonyms = explode(';', $attr->alt_names);
        foreach ($synonyms as $syn) {
          echo '"' . trim(mb_strtolower($syn)) . '":{id:' . $attr['attribute_group_id'] . ',values:' . json_encode($attributesArray) . '},';
        }
      }
    } ?>
  };

  console.log(attribute_groups);
  const attr_group_values = {};
  for (let i in attribute_groups) {
    const attr = attribute_groups[i];
    if (!attr.values || attr.values.length === 0) continue;
    for (let j of attr.values) {
      const av = j;
      if (!av.values || av.values.length === 0) continue;
      av.values.map(avv => attr_group_values[avv.toLowerCase().trim()] = {
        attribute_group_id: attr.id,
        attribute_id: av.attribute_id,
        value: av.value,
        url: av.url
      });
    }
  }
  console.log(attr_group_values);
  const __csrf = ['<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->getCsrfToken() ?>'];
</script>
<?php
  use \app\modules\controlpanel\assets\AdminAsset;
  AdminAsset::register($this);
  $this->assetBundles['app\modules\controlpanel\assets\AdminAsset']->js[] = 'js/catalog.js';
?>
