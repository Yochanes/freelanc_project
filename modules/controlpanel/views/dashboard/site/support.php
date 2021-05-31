<div class="panel_menu flex hend">
	<section>
		<button class="btn btn-primary" onclick="addElement(event, this)"><i class="fa fa-plus"></i> Добавить раздел</button>
		<form class="hidden">
			<input type="text" name="title" data-title="Название раздела" placeholder="Имя">
      <input type="number" name="sort_order" value="0" data-title="Порядок">
      <div class="file_container">
        <input type="file" name="image">
        <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
          Изменить иконку
        </button>
      </div>
		</form>
	</section>
</div>
<div class="panel_content">
	<?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного раздела</h2>' : '') ?>
	<ul class="data-list drop-down-list flex hstart flex-wrap">
	<?php foreach ($items as $item) { ?>
		<li id="scat_<?= $item['id'] ?>">
			<?= (!empty($item['categories']) ? '<i class="fa fa-plus" onclick="toggleList(this);"></i> ' : '<i class="fa fa-minus"></i> ') ?><?= $item['title'] ?>
			<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item['id'] ?>, '/controlpanel/data/deletesupportcategory/')"></i>
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatesupportcategory/', 'Редактирование раздела <?= $item['title'] ?>')"></i>
			<form class="hidden" class="edit-form">
				<input type="text" name="title" data-title="Название раздела" value="<?= $item['title'] ?>">
        <input type="number" name="sort_order" value="<?= $item['sort_order'] ?>" data-title="Порядок">
        <div class="file_container">
          <?= (!empty($item['image']) && file_exists(Yii::$app->basePath . $item['image']) ? '<img src="' . $item['image'] . '" />' : '') ?>
          <input type="file" name="image">
          <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
            Изменить иконку
          </button>
        </div>
				<input type="hidden" name="id" value="<?= $item['id'] ?>">
			</form>

            <i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatesupportcategory/','Добавление подраздела')"></i>
            <form class="hidden">
                <input type="text" name="title" data-title="Название подраздела">
                <input type="number" name="sort_order" value="0" data-title="Порядок">
                <input type="hidden" name="parent_id" value="<?= $item['id'] ?>">
            </form>

			<ul class="drop-down-list sub-drop-down-list">
			<?php
            if(isset($item['categories'])) {
            foreach ($item['categories'] as $catItem) { ?>
				<li id="scat_item_<?= $catItem['id'] ?>">
                  <?= (!empty($catItem['questions']) ? '<i class="fa fa-plus" onclick="toggleList(this);"></i> ' : '<i class="fa fa-minus"></i> ') ?>
          <span style="display:inline-block;max-width:75%"><?= htmlentities($catItem['title']) ?></span>
					<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $catItem['id'] ?>, '/controlpanel/data/deletesupportcategory/')"></i>
					<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatesupportcategory/', 'Редактирование подраздела <?= htmlentities($catItem['title']) ?>')"></i>
					<form class="hidden">
						<input type="text" name="title" value="<?= htmlentities($catItem['title']) ?>" data-title="Название подраздела">
            <input type="number" name="sort_order" data-title="Порядок" value="<?= $catItem['sort_order'] ?>">
            <input type="hidden" name="parent_id" value="<?= $item['id'] ?>">
						<input type="hidden" name="id" value="<?= $catItem['id'] ?>">
					</form>

                    <i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatesupportcategoryitem/','Добавление вопроса')"></i>
                    <form class="hidden">
                        <input type="text" name="title" data-title="Текст вопроса">
                        <textarea tinymce="true" type="text" name="text" data-title="Ответ"></textarea>
                        <input type="number" name="sort_order" value="0" data-title="Порядок">
                        <input type="hidden" name="category_id" value="<?= $catItem['id'] ?>">
                        <input type="hidden" name="id">
                    </form>

                    <ul class="drop-down-list sub-drop-down-list">

                      <?php
                      if(isset($catItem['questions'])) {
                      foreach ($catItem['questions'] as $questionItem) { ?>
                        <li id="scat_item_<?= $questionItem['id'] ?>">
                            <span style="display:inline-block;max-width:75%"><?= htmlentities($questionItem['title']) ?></span>
                            <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $questionItem['id'] ?>, '/controlpanel/data/deletesupportcategoryitem/')"></i>
                            <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatesupportcategoryitem/', 'Редактирование вопроса <?= htmlentities($questionItem['title']) ?>')"></i>
                            <form class="hidden">
                                <input type="text" name="title" value="<?= htmlentities($questionItem['title']) ?>" data-title="Текст вопроса">
                                <textarea tinymce="true" type="text" name="text" data-title="Ответ"><?= $questionItem['text'] ?></textarea>
                                <input type="number" name="sort_order" data-title="Порядок"" value="<?= $questionItem['sort_order'] ?>">
                                <input type="hidden" name="category_id" value="<?= $catItem['id'] ?>">
                                <input type="hidden" name="id" value="<?= $questionItem['id'] ?>">
                            </form>
                        </li>
                      <?php }} ?>

                    </ul>
				</li>
			<?php }} ?>
			</ul>
		</li>
	<?php } ?>
	</ul>
</div>
<script type="text/javascript">
	function addElement(ev, _this) {
		ev.preventDefault();
		ev.stopPropagation();
		createEditModal(_this.nextElementSibling, '/controlpanel/data/updatesupportcategory/', 'Добавление раздела');
	}
</script>
<script src="https://cdn.tiny.cloud/1/jbupo8lc5k1w0cfke858e2ngwkfxol0ivqpxo331lvlt7swv/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
