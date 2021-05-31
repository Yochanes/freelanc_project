<?php
$make_to_group = [];
$groups_arr = [];

foreach ($groups as $group) {
  $groups_arr[] = $group->attributes;

  foreach ($group->makes as $make) {
    if (!isset($make_to_group[$make->id])) $make_to_group[$make->id] = [];
    $make_to_group[$make->id][] = $group->make_group_id;
  }
}
?>
<script type="text/javascript">
  const makeGroups = <?=json_encode($groups_arr)?>;
  const makeGroupsByName = {};
  for (let g of makeGroups) {
    makeGroupsByName[g.import_filter.toLowerCase()] = g.make_group_id;
  }
  const makeToGroup = <?=json_encode($make_to_group)?>;
</script>
<div class="panel_menu flex hend">
  <section>
    <button class="btn btn-success" onclick="addGroup(event, this)"><i class="fa fa-plus"></i> Добавить группу</button>
    <form class="hidden">
      <input type="text" name="name" data-title="Название группы" placeholder="Название группы">
      <input type="text" name="filter_name" data-title="Поле фильтра" placeholder="Поле фильтра">
      <input type="text" name="import_filter" data-title="Фильтр импорта" placeholder="Фильтр импорта">
      <input type="text" name="url" data-title="URL" placeholder="URL">
      <input type="checkbox" name="use_in_car_form" data-title="Использовать при добавлении авто" onchange="this.value=this.checked?1:0" value="0">
    </form>
  </section>
  <section>
    <button class="btn btn-success" onclick="addMake(event,this)"><i class="fa fa-plus"></i> Добавить марку</button>
    <form class="hidden">
      <div class="multi" data-title="Группы производителей">
        <?php foreach ($groups as $v) { ?>
          <div class="flex hstart vcentered">
            <input type="checkbox" onchange="this.value=this.checked?<?= $v->make_group_id ?>:''" class="not_separate" name="make_group_id[]" data-value="<?= $v->make_group_id ?>">&nbsp;
            <label><?= $v->name ?></label>
          </div>
        <?php } ?>
      </div>
      <input type="text" name="name" data-title="Название марки" placeholder="Название марки">
      <div class="file_container">
        <input type="file" name="image">
        <button class="btn btn-primary" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">Изменить
          изображение
        </button>
      </div>
    </form>
  </section>
  <section>
    <button class="btn btn-success" onclick="importMakesWithGroups(event,this)"><i class="fa fa-plus"></i>
      Импортировать список по группам
    </button>
    <form class="hidden">
      <select data-title="Страница файла">
        <option value="0">Страница 1</option>
        <option value="1">Страница 2</option>
        <option value="2">Страница 3</option>
        <option value="3">Страница 4</option>
        <option value="4">Страница 5</option>
        <option value="5">Страница 6</option>
        <option value="6">Страница 7</option>
        <option value="7">Страница 8</option>
        <option value="8">Страница 9</option>
        <option value="9">Страница 10</option>
      </select>
      <div class="file_container">
        <input type="file" name="upload_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
        <button class="btn btn-primary" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">Добавить
          файл
        </button>
      </div>
    </form>
  </section>
  <section>
    <button class="btn btn-success" onclick="importMakes(event,this)"><i class="fa fa-plus"></i> Импортировать список
      марок
    </button>
    <form class="hidden">
      <input type="checkbox" name="clear_makes" type="checkbox" data-title="Удалить старые">
      <select name="make_group_id" data-title="Группа марок">
        <option value="">Выберите</option>
        <?php foreach ($groups as $group) { ?>
          <?php if (!$group->make_group_id) continue; ?>
          <option value="<?= $group->make_group_id ?>"><?= $group->name ?></option>
        <?php } ?>
      </select>
      <select name="sheet_num" data-title="Страница файла">
        <option value="0">Страница 1</option>
        <option value="1">Страница 2</option>
        <option value="2">Страница 3</option>
        <option value="3">Страница 4</option>
        <option value="4">Страница 5</option>
        <option value="5">Страница 6</option>
        <option value="6">Страница 7</option>
        <option value="7">Страница 8</option>
        <option value="8">Страница 9</option>
        <option value="9">Страница 10</option>
      </select>
      <div class="file_container">
        <input type="file" name="upload_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
        <button class="btn btn-primary" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
          Добавить файл
        </button>
      </div>
    </form>
  </section>
  <section>
    <input type="text" name="name" class="simple_input" value="<?= Yii::$app->request->get('name') ?>" placeholder="Название группы">
    <button class="btn btn-primary" onclick="search('name',this.previousElementSibling.value)"><i class="fa fa-search"></i> Искать
    </button>
  </section>
</div>
<div class="panel_content">
  <?= (empty($groups) ? '<h2 class="text-center">На данный момент вы не добавили ни одного элемента</h2>' : '') ?>
  <ul class="makes-list drop-down-list flex hstart vstart flex-wrap">
    <?php foreach ($groups as $group) { ?>
      <li id="make_group_<?= $group->make_group_id ?>">
        <!-- Список групп марок -->
        <?= ($group->makesCount ? '<i class="fa fa-plus pull-left" onclick="toggleList(this);loadMakes(' . $group->make_group_id . ',this)"></i> ' : '') ?>
        <?= $group->name ?>
        <?php if ($group->make_group_id) { ?>
          <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'),<?= $group->make_group_id ?>,'/controlpanel/data/deletemakegroup/')"></i>
          <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatemakegroup/','Редактирование группы марок <?= $group->name ?>')"></i>
          <form class="hidden">
            <input type="text" name="name" data-title="Название группы" value="<?= $group->name ?>">
            <input type="text" name="filter_name" data-title="Поле фильтра" value="<?= $group->filter_name ?>" placeholder="Поле фильтра">
            <input type="text" name="import_filter" data-title="Фильтр импорта" value="<?= $group->import_filter ?>" placeholder="Фильтр импорта">
            <input type="text" name="url" data-title="URL" value="<?= $group->url ?>" placeholder="URL">
            <input type="checkbox" name="use_in_car_form" data-title="Использовать при добавлении авто" onchange="this.value=this.checked?1:0" value="<?= $group->use_in_car_form ?>" <?= $group->use_in_car_form ? 'checked' : '' ?>>
            <input type="hidden" name="make_group_id" value="<?= $group->make_group_id ?>">
          </form>
          <i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatemake/','Добавление марки')"></i>
          <form class="hidden" class="add-form">
            <div class="multi" data-title="Группы производителей">
              <?php foreach ($groups as $v) { ?>
                <div class="flex hstart vcentered">
                  <input type="checkbox" onchange="this.value=this.checked?<?= $v->make_group_id ?>:''" class="not_separate" name="make_group_id[]" data-value="<?= $v->make_group_id ?>">&nbsp;
                  <label><?= $v->name ?></label>
                </div>
              <?php } ?>
            </div>
            <input type="text" name="name" data-title="Название">
            <div class="file_container">
              <input type="file" name="image">
              <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
                Изменить изображение
              </button>
            </div>
          </form>
        <?php } ?>
      </li>
    <?php } ?>
  </ul>
</div>
<script type="text/javascript">
  function loadMakes(id, _this) {
    const c = document.getElementById(`make_group_${id}`);
    if (id && c && !c.makesLoaded) {
      window._http.get(`/controlpanel/data/getmakes/${id}/`, { button: _this })
        .then((resp) => {
          if (!Array.isArray(resp)) return;
          c.makesLoaded = true;
          let str = '<ul class="drop-down-list sub-drop-down-list">';
          for (let m of resp) {
            let grStr = '';
            for (let g of makeGroups) {
              const mtg = makeToGroup[m.id];
              grStr += `<div class="flex hstart vcentered">
                        <input type="checkbox"
                          onchange="this.value=(this.checked?'${g.make_group_id}':'')"
                          class="not_separate"
                          name="make_group_id[]"
                          ${mtg && mtg.indexOf(g.make_group_id) ? `checked value="${g.make_group_id}"` : ''}
                          data-value="${g.make_group_id}">&nbsp;
                        <label>${g.name}</label></div>`;
            }

            str += `<li id="make_${m.id}_${id}">${m.name}
              <i class="fa fa-plus pull-left" onclick="toggleList(this);loadModels(${id},${m.id},this)"></i>
              <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'),${m.id},'/controlpanel/data/deletemake/')"></i>
              <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatemake/','Редактирование марки ${m.name}')"></i>
              <form class="hidden">
                <div class="multi" data-title="Группы производителей">
                  ${grStr}
                </div>
                <input type="text" name="name" value="${m.name}" data-title="Название марки" placeholder="Название марки">
                <div class="file_container">
                  ${m.image && m.image.length > 0 ? `<img alt="${m.name}" src="${m.image}" />` : ''}
                  <input type="file" name="image">
                  <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
                    Изменить изображение
                  </button>
                </div>
                <input type="hidden" name="id" value="${m.id}">
              </form>
              <i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatemodel/','Добавление модели')"></i>
              <form class="hidden" class="add-form">
                <input type="text" name="name" data-title="Название">
                <input type="hidden" name="make_id" value="${m.id}">
                <input type="hidden" name="make_group_id" value="${id}">
              </form>
              </li>`;
          }

          str += '</ul>';
          if (c.lastElementChild !== null && c.lastElementChild.tagName === 'UL') {
            c.lastElementChild.outerHTML = str;
          } else {
            c.insertAdjacentHTML('beforeend', str)
          }
        })
        .catch((err) => console.log(err))
    }
  }

  function loadModels(gid, id, _this) {
    const c = document.getElementById(`make_${id}_${gid}`);
    if (id && c && !c.modelsLoaded) {
      window._http.get(`/controlpanel/data/getmodels/${id}/`, { button: _this })
        .then((resp) => {
          if (!Array.isArray(resp)) return;
          c.modelsLoaded = true;
          let str = `<!-- Список моделей в марке -->
            <ul class="drop-down-list sub-drop-down-list">`;

          for (let m of resp) {
            str += `<li id="model_${m.id}_${id}">${m.name}
            <i class="fa fa-plus pull-left" onclick="toggleList(this);loadGenerations(${id},${m.id},this)"></i>&nbsp;
            <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'),${m.id},'/controlpanel/data/deletemodel/')"></i>
            <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatemodel/','Редактирование модели ${m.name}')"></i>
            <form class="hidden">
            <input type="text" name="name" data-title="Название" value="${m.name}">
            <input type="hidden" name="make_id" value="${id}">
            <input type="hidden" name="id" value="${m.id}">
            </form>
            <i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updategeneration/','Добавление поколения')"></i>
            <form class="hidden" class="add-form">
            <input type="text" name="name" data-title="Название">
            <input type="text" name="alt_name" data-title="Альтернативное название">
            <input type="text" name="years" data-title="Годы">
            <input type="hidden" name="model_id" value="${m.id}">
            </form></li>`;
          }

          str += '</ul>';
          if (c.lastElementChild !== null && c.lastElementChild.tagName === 'UL') {
            c.lastElementChild.outerHTML = str;
          } else {
            c.insertAdjacentHTML('beforeend', str)
          }
        })
        .catch((err) => console.log(err))
    }
  }

  function loadGenerations(mid, id, _this) {
    const c = document.getElementById(`model_${id}_${mid}`);
    if (id && c && !c.generationsLoaded) {
      window._http.get(`/controlpanel/data/getgenerations/${id}/`, { button: _this })
        .then((resp) => {
          if (!Array.isArray(resp)) return;
          c.generationsLoaded = true;
          let str = '<!-- Список поколений в модели --><ul class="drop-down-list sub-drop-down-list">';
          for (let g of resp) {
            str += `<li id="generation_${mid}_${id}_${g.id}">${g.name}
            <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'),${g.id},'/controlpanel/data/deletegeneration/')"></i>
            <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updategeneration/','Редактирование поколения ${g.name}')"></i>
            <form class="hidden">
            <input type="text" name="name" data-title="Название" value="${g.name}">
            <input type="text" name="alt_name" data-title="Альтернативное название" value="${g.alt_name}">
            <input type="text" name="years" data-title="Годы" value="${g.years}">
            <input type="hidden" name="model_id" value="${id}">
            <input type="hidden" name="id" value="${g.id}">
            </form>
            </li>`;
          }
          str += '</ul>';
          if (c.lastElementChild !== null && c.lastElementChild.tagName === 'UL') {
            c.lastElementChild.outerHTML = str;
          } else {
            c.insertAdjacentHTML('beforeend', str)
          }
        })
    }
  }

  function addGroup(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updatemakegroup/', 'Добавление группы');
  }

  function addMake(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updatemake/', 'Добавление марки');
  }

  function importMakes(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/uploadmakes/', 'Импортирование марок');
    const btn = document.querySelector('[name=editForm] .buttons .btn');
    btn.action = '/controlpanel/data/uploadmakes/';
    btn.removeAttribute('onclick');

    btn.onclick = function (evt) {
      evt.preventDefault();
      evt.stopPropagation();
      let sheet_num = 0;
      let group_id = '';

      const gs = document.querySelector('[name=editForm] [name=make_group_id]');
      if (gs != null && !isNaN(gs.value)) group_id = gs.value;
      const ss = document.querySelector('[name=editForm] [name=sheet_num]');
      if (ss != null && !isNaN(ss.value)) sheet_num = parseInt(ss.value);
      const file_inp = document.querySelector('[name=editForm] [type=file]');

      if (file_inp.files.length) {
        processFile(file_inp.files[0], file_inp, this, sheet_num, make_columns, group_id);
      } else {
        this.origTxt = this.textContent;
        this.textContent = 'Необходимо загрузить файл импорта';

        setTimeout(function () {
          this.textContent = this.origTxt;
        }, 3000)
      }
    };
  }

  function importMakesWithGroups(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/uploadmakes/', 'Импортирование марок');
    const btn = document.querySelector('[name=editForm] .buttons .btn');
    btn.action = '/controlpanel/data/uploadmakeswithgroups/';
    btn.removeAttribute('onclick');

    btn.onclick = function (evt) {
      evt.preventDefault();
      evt.stopPropagation();
      sheet_num = 0;

      const ss = document.querySelector('[name=editForm] select');
      if (ss != null && !isNaN(ss.value)) sheet_num = parseInt(ss.value);
      const file_inp = document.querySelector('[name=editForm] [type=file]');

      if (file_inp.files.length) {
        processFile(file_inp.files[0], file_inp, this, sheet_num, make_group_columns);
      } else {
        this.origTxt = this.textContent;
        this.textContent = 'Необходимо загрузить файл импорта';

        setTimeout(function () {
          this.textContent = this.origTxt;
        }, 3000)
      }
    };
  }

  function search(key, val) {
    const sp = new URLSearchParams(window.location.search);
    if (val.length > 0) {
      sp.set(key, val);
    } else {
      sp.delete(key);
    }
    window.location.search = sp.toString();
  }
</script>
<script typr="text/javascript">
  const make_groups = {
    <?php foreach ($groups as $group) {?>
    '<?=mb_strtolower($group->import_filter)?>':<?=$group->make_group_id?>,
    <?php } ?>
  };
  console.log(make_groups);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.8.0/jszip.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.8.0/xlsx.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xls/0.7.4-a/xls.js"></script>
<?php
use \app\modules\controlpanel\assets\AdminAsset;
AdminAsset::register($this);
$this->assetBundles['app\modules\controlpanel\assets\AdminAsset']->js[] = 'js/makes_upload.js';
?>
