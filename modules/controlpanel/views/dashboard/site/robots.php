<?php
use yii\widgets\LinkPager;
?>
<div class="panel_menu flex hend">
  <section>
    <button class="btn btn-primary" onclick="addElement(event, this)"><i class="fa fa-plus"></i> Добавить robots.txt</button>
    <form class="hidden">
      <input type="text" name="url" data-title="URL robots.txt" placeholder="URL robots.txt">
      <textarea name="content" data-title="Содержание robots.txt" placeholder="Содержание robots.txt"></textarea>
      <input type="checkbox" name="default_flag" data-title="Файл по-умолчанию" onchange="this.value=this.checked?1:0" value="0">
    </form>
  </section>
  <section>
    <button class="btn btn-success" onclick="genElement(event, this)"><i class="fa fa-plus"></i> Сгенерировать robots.txt по городам</button>
    <form class="hidden">
      <textarea name="content" data-title="Содержание robots.txt" placeholder="Содержание robots.txt"></textarea>
    </form>
  </section>
</div>
<div class="panel_content">
  <?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного файла robots.txt</h2>' : '') ?>
  <ul class="data-list drop-down-list flex hstart flex-wrap">
    <?php foreach ($items as $item) { ?>
      <li>
        <?= $item->url ?>
        <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deleterobots/')"></i>
        <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updaterobots/', 'Редактирование robots.txt <?= $item->url ?>')"></i>
        <form class="hidden">
          <input type="text" name="url" data-title="URL robots.txt" placeholder="URL robots.txt" value="<?= $item->url ?>">>
          <textarea name="content" data-title="Содержание robots.txt" placeholder="Содержание robots.txt"><?= $item->content ?></textarea>
          <input type="checkbox" name="default_flag" data-title="Файл по-умолчанию" onchange="this.value=this.checked?1:0" <?= $item->default_flag ? 'checked' : '' ?> value="<?= $item->default_flag ?>">
          <input type="hidden" name="id" value="<?= $item->id ?>">
        </form>
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
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updaterobots/', 'Добавление robots.txt');
  }

  function genElement(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/generaterobots/', 'Создание robots.txt');
  }
</script>