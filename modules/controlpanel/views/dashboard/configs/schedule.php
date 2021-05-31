<div class="panel_content">
	<?= (empty($configs) ? '<h2 class="text-center">На данный момент вы не добавили ни одного элемента</h2>' : '') ?>
	<?php if (!empty($configs)) { ?>
	<ul class="makes-list drop-down-list flex hstart vstart flex-wrap">
	<?php foreach ($configs as $item) { ?>
		<li>
			Планировщик
			<i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updateschedule/', 'Редактирование настроек')"></i>
			<form class="hidden">
        <input type="text" name="token" data-title="Email" placeholder="Пароль" value="<?=$item->token?>">
        <input type="checkbox" name="curs_schedule" data-title="Обновление курса" data-value="1" <?=$item->curs_schedule ? 'checked' : ''?>>
        <input type="number" min="30" name="curs_schedule_rate" data-title="Частота обновления курса, минуты" value="<?=$item->curs_schedule_rate?>">
        <input type="checkbox" name="request_schedule" data-title="Деактивация запросов" data-value="1" <?=$item->request_schedule ? 'checked' : ''?>>
        <input type="number" min="12" name="request_schedule_rate" data-title="Частота деактивации запросов, часы" value="<?=$item->request_schedule_rate?>">
				<input type="hidden" name="id" value="<?= $item->id ?>">
			</form>
		</li>
	<?php } ?>
	</ul>
	<?php } ?>
</div>