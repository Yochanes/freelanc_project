<?php 
	use yii\widgets\LinkPager;
?>
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
	<ul class="data-list drop-down-list flex hstart flex-wrap">
	<?php foreach ($items as $item) { ?>
		<li>
			<?= $item->name ?>
			<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deletepage/')"></i>
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatepage/', 'Редактирование страницы <?= $item->name ?>')"></i>
			<form class="hidden">
				<input type="text" data-title="Название страницы" name="name" value="<?= $item->name ?>">
				<input type="text" data-title="Title" name="title" value="<?= $item->title ?>">
				<textarea name="content" tinymce="true" data-title="Текст"><?= $item->content ?></textarea>
				<input type="text" name="meta_title" data-title="meta title" value="<?= $item->meta_title ?>">
				<textarea name="meta_keywords" data-title="meta keywords"><?= $item->meta_keywords ?></textarea>
				<textarea name="meta_description" data-title="meta description"><?= $item->meta_description ?></textarea>
				<input type="text" name="meta_robots" data-title="meta robots" value="<?= $item->meta_robots ?>">
				<input type="text" name="meta_author" data-title="meta author" value="<?= $item->meta_author ?>">
				<input type="hidden" name="id" value="<?=$item->id ?>">
        <input type="hidden" name="url" value="<?=$item->url?>">
        <input type="hidden" name="relative" value="0">
        <input type="hidden" name="informational" value="0">
        <input type="hidden" name="real_url" value="<?=$item->real_url?>">
			</form>
		</li>
	<?php } ?>
	</ul>
	<div class="flex hcentered vstart">
		<?= LinkPager::widget(['pagination' => $pagination]) ?>
	</div>
</div>
<script src="https://cdn.tiny.cloud/1/jbupo8lc5k1w0cfke858e2ngwkfxol0ivqpxo331lvlt7swv/tinymce/5/tinymce.min.js" referrerpolicy="origin">