<div class="panel_content">
	<ul class="data-list flex flex-wrap">
		<li>
			<header>Пользователи</header>
			<section class="flex hspaced">Всего<b><?= $users_num ?></b></section>
			<section class="flex hspaced">Продавцы<b><?= $users_client_num ?></b></section>
			<section class="flex hspaced">Компании<b><?= $users_company_num ?></b></section>
			<section class="flex hspaced">С админ правами<b><?= $users_admin_num ?></b></section>
		</li>
		<li>
			<header>Запчасти</header>
			<section class="flex hspaced">Всего объявлений<b><?= $products_num ?></b></section>
			<section class="flex hspaced">На проверке<b><?= $products_check_num ?></b></section>
			<section class="flex hspaced">Опубликовано<b><?= $products_active_num ?></b></section>
      <section class="flex hspaced">Неактивных<b><?= $products_inactive_num ?></b></section>
		</li>
		<li>
			<header>Заявки на поиск</header>
			<section class="flex hspaced">Всего<b><?= $requests_num ?></b></section>
		</li>
	</ul>
</div>
