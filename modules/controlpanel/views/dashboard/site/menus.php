<div class="panel_menu flex hend">
	<section>
		<button class="btn btn-primary" onclick="addElement(event, this)"><i class="fa fa-plus"></i> Добавить меню</button>
		<form class="hidden">
			<input type="text" name="name" data-title="Название меню" placeholder="Имя">
			<select name="position" data-title="Расположение">
				<option value="header">Хэдер</option>
				<option value="footer">Футер</option>
        <option value="main_mob">Главная-мобильная</option>
			</select>
			<input type="checkbox" checked name="active" data-title="Меню активно">
		</form>
	</section>
</div>
<div class="panel_content">
	<?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного меню</h2>' : '') ?>
	<ul class="data-list drop-down-list flex hstart flex-wrap">
	<?php foreach ($items as $item) { ?>
		<li id="menu_<?= $item->id ?>">
			<?= (!empty($item->menuItems) ? '<i class="fa fa-plus" onclick="toggleList(this);"></i> ' : '<i class="fa fa-minus"></i> ') ?><?= $item->name ?>
			<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->id ?>, '/controlpanel/data/deletemenu/')"></i>
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatemenu/', 'Редактирование меню <?= $item->name ?>')"></i>
			<form class="hidden" class="edit-form">
				<input type="text" name="name" data-title="Название меню" value="<?= $item->name ?>">
				<input type="checkbox" name="active" data-title="Меню активно"<?= ($item->active ? ' checked' : '') ?>>
				<select name="position" data-title="Расположение">
					<option value="header"<?= $item->position == 'header' ? ' selected' : '' ?>>Хэдер</option>
					<option value="footer"<?= $item->position == 'footer' ? ' selected' : '' ?>>Футер</option>
          <option value="main_mob"<?= $item->position == 'main_mob' ? ' selected' : '' ?>>Главная-мобильная</option>
				</select>
				<input type="hidden" name="id" value="<?= $item->id ?>">
			</form>
			<i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling,'/controlpanel/data/updatemenuitem/','Добавление пункта меню')"></i>
			<form class="hidden" class="add-form">
        <input type="text" name="label" data-title="Текст кнопки">
        <input type="text" name="url" data-title="URL кнопки">
        <input type="text" name="not_loggedin_url" data-title="URL неавторизованным">
        <input type="text" name="info" data-title="Подсказка">
        <input type="number" name="sort_order" value="0" data-title="Порядок">
        <input type="text" name="classname" data-title="CSS класс">
        <div class="file_container">
          <input type="file" name="icon">
          <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
            Изменить иконку
          </button>
        </div>
				<input type="hidden" name="menu_id" value="<?= $item->id ?>">
			</form>
			<ul class="drop-down-list sub-drop-down-list">
			<?php foreach ($item->menuItems as $menuItem) { ?>
				<?php if (!empty($menuItem->parent_id)) continue; ?>
				<li id="menu_item_<?= $menuItem->id ?>">
					<?= (!empty($menuItem->children) ? '<i class="fa fa-plus" onclick="toggleList(this);"></i> ' : '<i class="fa fa-minus"></i> ') ?>
          <span style="display:inline-block;max-width:75%"><?= htmlentities($menuItem->label) ?></span>
					<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $menuItem->id ?>, '/controlpanel/data/deletemenuitem/')"></i>
					<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatemenuitem/', 'Редактирование пункта меню <?= htmlentities($menuItem->label) ?>')"></i>
					<form class="hidden">
						<input type="text" name="label" value="<?= htmlentities($menuItem->label) ?>" data-title="Текст кнопки">
						<input type="text" name="url" value="<?= $menuItem->url ?>" data-title="URL кнопки">
						<input type="text" name="not_loggedin_url" value="<?= $menuItem->not_loggedin_url ?>" data-title="URL неавторизованным">
            <input type="text" name="info" value="<?= $menuItem->info ?>" data-title="Подсказка">
            <input type="number" name="sort_order" value="<?= $menuItem->sort_order ?>" data-title="Порядок">
            <input type="text" name="classname" value="<?= $menuItem->classname ?>" data-title="CSS класс">
            <div class="file_container">
              <?= (!empty($menuItem->icon) && file_exists(Yii::$app->basePath . $menuItem->icon) ? '<img src="' . $menuItem->icon . '" />' : '') ?>
              <input type="file" name="icon">
              <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
                Изменить иконку
              </button>
            </div>
						<input type="hidden" name="menu_id" value="<?= $item->id ?>">
						<input type="hidden" name="id" value="<?= $menuItem->id ?>">
					</form>
					<i class="fa fa-plus-circle" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatemenuitem/', 'Добавление пункта меню')"></i>
					<form class="hidden" class="add-form">
						<input type="text" name="label" data-title="Текст кнопки">
						<input type="text" name="url" data-title="URL кнопки">
						<input type="text" name="not_loggedin_url" data-title="URL неавторизованным">
            <input type="text" name="info" data-title="Подсказка">
						<input type="number" name="sort_order" value="0" data-title="Порядок">
            <input type="text" name="classname" data-title="CSS класс">
            <div class="file_container">
              <input type="file" name="icon">
              <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
                Изменить иконку
              </button>
            </div>
						<input type="hidden" name="parent_id" value="<?= $menuItem->id ?>">
						<input type="hidden" name="menu_id" value="<?= $item->id ?>">
					</form>
					<ul class="drop-down-list sub-drop-down-list">
					<?php foreach ($menuItem->children as $child) { ?>
						<li id="menu_item_<?= $child->id ?>">
              <span style="display:inline-block;max-width:75%"><?=htmlentities($child->label); ?></span>
							<i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $child->id ?>, '/controlpanel/data/deletemenuitem/')"></i>
							<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updatemenuitem/', 'Редактирование пункта меню')"></i>
							<form class="hidden">
								<input type="text" name="label" value="<?= htmlentities($child->label) ?>" data-title="Текст кнопки">
								<input type="text" name="url" value="<?= $child->url ?>" data-title="URL кнопки">
								<input type="text" name="not_loggedin_url" value="<?= $child->not_loggedin_url ?>" data-title="URL неавторизованным">
                <input type="text" name="info" value="<?= $child->info ?>" data-title="Подсказка">
                <input type="number" name="sort_order" value="<?= $child->sort_order ?>" data-title="Порядок">
                <input type="text" name="classname" value="<?= $child->classname ?>" data-title="CSS класс">
                <div class="file_container">
                  <?= (!empty($child->icon) && file_exists(Yii::$app->basePath . $child->icon) ? '<img src="' . $child->icon . '" />' : '') ?>
                  <input type="file" name="icon">
                  <button class="btn btn-link" onclick="event.preventDefault();event.stopPropagation();this.previousElementSibling.click()">
                    Изменить иконку
                  </button>
                </div>
								<input type="hidden" name="menu_id" value="<?= $item->id ?>">
								<input type="hidden" name="parent_id" value="<?= $menuItem->id ?>">
								<input type="hidden" name="id" value="<?= $child->id ?>">
							</form>
						</li>
					<?php } ?>
					</ul>
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
		createEditModal(_this.nextElementSibling, '/controlpanel/data/updatemenu/', 'Добавление меню');
	}
</script>
