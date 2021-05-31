<?php

use yii\widgets\LinkPager;

?>
<div class="panel_menu flex hend">
  <section>
    <button class="btn btn-primary" onclick="addElement(event, this)">
      <i class="fa fa-plus"></i> Добавить страницу
    </button>
    <form class="hidden">
      <input type="text" name="name" data-title="Название страницы" placeholder="Название страницы">
      <input type="text" name="url" data-title="URL страницы" placeholder="URL">
      <input type="text" name="title" data-title="Title" placeholder="title">
      <textarea name="content" tinymce="true" data-title="Текст" placeholder="текст"></textarea>
      <input type="text" name="meta_title" data-title="meta title" placeholder="title">
      <textarea name="meta_keywords" data-title="meta keywords" placeholder="keywords"></textarea>
      <textarea name="meta_description" data-title="meta description" placeholder="description"></textarea>
      <input type="text" data-title="meta robots" name="meta_robots" placeholder="robots">
      <input type="text" data-title="meta author" name="meta_author" placeholder="author">
      <input type="hidden" name="informational" value="1">
      <input type="hidden" name="relative" value="0">
    </form>
  </section>
</div>
<form class="panel_menu">
  <div class="col-lg-3">
    <label for="select_make">URL</label>
    <input class="form-control input-select" name="url" value="<?=Yii::$app->request->get('url')?>">
  </div>
  <div class="row">
    <div class="col-lg-12 text-right">
      <button class="btn btn-primary" onclick="searchByForm(event,this,this.closest('form'))"><i class="fa fa-search"></i>Искать</button>
      <a class="btn btn-warning" onclick="window.location.href=window.location.pathname">Очистить</a>
    </div>
  </div>
</form>
<div class="panel_content">
  <?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одной страницы</h2>' : '') ?>
  <ul class="data-list drop-down-list flex hstart vstart flex-wrap">
    <?php foreach ($items as $item) { ?>
      <li>
        <?= $item->name ?>
        <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deletepage/')"></i>
        <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatepage/', 'Редактирование страницы <?= $item->name ?>')"></i>
        <form class="hidden">
          <input type="text" data-title="Название страницы" name="name" value="<?= $item->name ?>">
          <input type="text" name="url" data-title="URL страницы" value="<?=$item->url?>" placeholder="URL">
          <input type="text" data-title="Title" name="title" value="<?= $item->title ?>">
          <textarea name="content" tinymce="true" data-title="Текст"><?= $item->content ?></textarea>
          <input type="text" name="meta_title" data-title="meta title" value="<?= $item->meta_title ?>">
          <textarea name="meta_keywords" data-title="meta keywords"><?= $item->meta_keywords ?></textarea>
          <textarea name="meta_description" data-title="meta description"><?= $item->meta_description ?></textarea>
          <input type="text" name="meta_robots" data-title="meta robots" value="<?= $item->meta_robots ?>">
          <input type="text" name="meta_author" data-title="meta author" value="<?= $item->meta_author ?>">
          <input type="hidden" name="informational" value="1">
          <input type="hidden" name="relative" value="0">
          <input type="hidden" name="id" value="<?= $item->id ?>">
        </form>
      </li>
    <?php } ?>
  </ul>
  <div class="flex hcentered vstart"><?= LinkPager::widget(['pagination' => $pagination]) ?></div>
</div>
<script type="text/javascript">
  function addElement(ev, _this) {
    ev.preventDefault();
    ev.stopPropagation();
    createEditModal(_this.nextElementSibling, '/controlpanel/data/updatepage/', 'Добавление страницы');
  }
</script>
<script src="https://cdn.tiny.cloud/1/jbupo8lc5k1w0cfke858e2ngwkfxol0ivqpxo331lvlt7swv/tinymce/5/tinymce.min.js" referrerpolicy="origin">
