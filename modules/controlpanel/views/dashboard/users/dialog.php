<?php
	use yii\bootstrap\Dropdown;
	use app\models\User;
	use \app\models\helpers\Helpers;

	$user = Yii::$app->user->identity;
?>
<style>
	.dialog-header {
		background: #f3f3f3;
		border-bottom: 1px solid #e9e9e9;
		padding: 10px;
	}

	.dialog-header img {
		margin-right: 10px;
		vertical-align: top;
	}

	.dialog-body {
		position: relative;
		max-height: 300px;
		overflow-y: auto;
		border-bottom: 1px solid #f9f9f9;
	}

	.dialog-body .date-block {
		margin-top: 5px;
		text-align: center;
		color: #ccc;
	}

	.dialog-body .msg-box {
		margin: 5px 10px;
	}

	.dialog-body .msg-box:not(.mine) {
		text-align: left;
	}

	.dialog-body .msg-box.mine {
		text-align: right;
	}

	.dialog-body .msg-box header {
		margin-bottom: 0;
	}

	.dialog-body .msg-box footer {
		font-size: 11px;
		margin-bottom: 10px;
		color: #969696;
	}

	.dialog-body .msg-box > div {
		display: inline-block;
		background: #f3f3f3;
		padding: 10px;
		max-width: 70%;
		border-radius: 6px;
		text-align: left;
	}

	.dialog-body .msg-box .imgs-container {
		margin: 10px 0;
	}

	.dialog-body .msg-box .imgs-container > img {
		display: inline-block;
		max-width: 100px;
		cursor: pointer;
		vertical-align: middle;
	}

	.dialog-body .msg-box .imgs-container > img:not(:last-child) {
		margin-right: 5px;
	}

	.dialog-body .msg-box.mine > div {
		background: #f6f8ff;
	}

	.dialog-body .msg-box .msg-date {
		font-size: 11px;
		text-align: right;
		color: #969696;
	}

	.dialog-send {
		padding: 10px;
		background: #f3f3f3;
	}

	.dialog-send textarea {
		width: 75%;
		margin: 0 10px;
		resize: vertical;
		height: 100%;
		border: none !important;
		box-shadow: none !important;
	}

	.dialog-send .upload-img-wrapper input[type=file] {
		display: none;
	}

	.dialog-send .upload-img-wrapper .fa {
		cursor: pointer;
	}
</style>
<form class="panel_menu">
	<div class="row">
		<div class="col-lg-3">
			<label for="select_year">Логин</label>
			<input class="form-control input-field" name="username" value="<?= Yii::$app->request->get('username') ?>">
		</div>
		<div class="col-lg-3">
			<label for="select_year">Имя</label>
			<input class="form-control input-field" name="display_name" value="<?= Yii::$app->request->get('display_name') ?>">
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 text-right">
			<button class="btn btn-primary" onclick="searchByForm(event,this,this.closest('form'))"><i class="fa fa-search"></i>Искать</button>
		</div>
	</div>
</form>
<div class="panel_content">
	<?= (!$dialog ? '<h2 class="text-center">Диалог не найден</h2>' : '') ?>
	<?php if ($dialog) { ?>
	<div class="full-width flex flex-wrap vstretch">
		<div class="flex vstretch flex-wrap full-width dialog-wrapper">
			<div class="dialog-header flex vstart hspaced full-width">
				<div>
					<?php if ($user->id == $dialog->sender_id) { ?>
					<a href="javascript:void(0)">Я</a>
					<?php } else { ?>
					<a href="<?=$dialog->sender->url?>" target="_blank"><?=$dialog->sender->display_name?></a>
					<?php } ?>
					<div><?= Helpers::getDateDiff($dialog->date_updated) ?> назад</div>
				</div>
			</div>
			<div class="dialog-body full-width" data-max="<?= $dialog->getMessagesCount() ?>" data-current="5">
			<?php
				$messages = array_reverse($dialog->getMessages(0, 5));
				$cur = -1;

				foreach($messages as $message) {
					$diff = Helpers::getDateOffset($message->date_updated);
					$dt = new \DateTime($message->date_updated);
					$sender =  $message->sender;

					if ($diff->y > 0 && $diff->y * 100000 != $cur) {
						$cur = $diff->y * 100000;
						echo '<div class="date-block" data-cur="' . $cur . '">' . $dt->format('o') . '</div>';
					} else if ($diff->m > 0 && $diff->m * 10000 != $cur) {
						$cur = $diff->m * 10000;
						echo '<div class="date-block" data-cur="' . $cur . '">' . $dt->format('j') . ' ' . Helpers::getDayMonthTranslated($dt->format('F')) . '</div>';
					} else if ($diff->d > 0 && $diff->d * 1000 != $cur) {
						$cur = $diff->d * 1000;
						echo '<div class="date-block" data-cur="' . $cur . '">' . $dt->format('j') . ' ' . Helpers::getDayMonthTranslated($dt->format('F')) . '</div>';
					} else if (((int) $dt->format('H') - $diff->h) < 0 && $cur != 1) {
						$cur = 1;
						echo '<div class="date-block" data-cur="' . $cur . '">Вчера</div>';
					} else if (((int) $dt->format('H') - $diff->h) >= 0 && $cur != 0) {
						$cur = 0;
						echo '<div class="date-block" data-cur="' . $cur . '">Сегодня</div>';
					}
			?>
				<div class="msg-box <?= $user->id == $message->sender_id ? 'mine' : '' ?>">
					<?=($sender['id'] == $user->id ? '' : '<header><a href="<?=$sender->url?>">' . $sender['display_name'] . '</a></header>') ?>
					<div>
						<?= $message->text ?>
						<?php
							$images = $message->images;
							if ($images) {
								echo '<div class="imgs-container">';

								foreach ($images as $img) {
								    echo '<img src="' . Helpers::getImageByURL($img, 100, 100) . '" onclick="showImage(' . $img . ')" />';
								}

								echo '</div>';
							}
						?>
						<div class="msg-date"><?= $dt->format('H') . ':' . $dt->format('i') ?></div>
					</div>
					<?php if ($user->id == $message->sender_id) { ?>
					<footer><?= $message->state == 0 ? '<i class="fa fa-check"></i> Доставлено' : '<i class="fa fa-eye"></i> Просмотрено' ?></footer>
					<?php } ?>
				</div>
			<?php }?>
			</div>
			<form class="full-width dialog-send flex vcentered hspaced">
				<div class="upload-img-wrapper">
					<input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
					<input type="hidden" name="dialog_id" value="<?= $dialog->id ?>">
					<input type="hidden" name="receiver_id" value="<?= $user->id == $dialog->sender_id ? $dialog->receiver_id : $dialog->sender_id ?>">
					<input type="file" name="imgs" multiple max="3">
					<i class="fa fa-camera" onclick="this.previousElementSibling.click();"></i>
				</div>
				<textarea name="text" class="field_input"></textarea>
				<button class="btn btn-success" onclick="sendMessage(event,this,this.closest('form'));"><i class="fa fa-location-arrow"></i>Отправить</button>
			</form>
		</div>
	</div>
	<?php } ?>
</div>
<?php if ($dialog) { ?>
<script>
	window.disableReloadOnSubmit = true;

	document.addEventListener('DOMContentLoaded', function () {
		const b = document.querySelector('.dialog-body');
		b.scrollTop = b.scrollHeight;

		b.onscroll = function () {
			if (this.scrollTop == 0 && !this.fetching) {
				let max = parseInt(this.getAttribute('data-max'));
				let current = parseInt(this.getAttribute('data-current'));
				if (isNaN(max) || isNaN(current) || current >= max) return;
				this.fetching = true;
				let offset = current + 5 <= max ? (current + 5) : (max - current);

				const _this = this;
				const request = new XMLHttpRequest();
				request.open("POST", '/actions/getmsg/<?= $dialog->id ?>', true);
				const fData = new FormData();
				fData.append('current', current);
				fData.append('max', 5);
				fData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->getCsrfToken() ?>');

				request.onload = (ev) => {
					_this.fetching = false;
					const data = parseToObject(ev.target.responseText);
					let ot = null;

					if (data != null) {
						_this.setAttribute('data-current', offset);
						const me = <?= $user->id ?>;
						const l1 = data.length;
						let id = 0;

						for (let i = 0; i < l1; i++) {
							const msg = data[i];
							let db = _this.querySelector('[data-cur="' + msg.cur + '"]');

							const imgs = msg.images;
							const l = imgs.length;
							let txt = '';

							for (let j = 0; j < l; j++) {
								if (imgs[j] == null) continue;
								txt += '<img src="/images/get/' + imgs[j] + '?width=100&height=100" onclick="showImage(' + imgs[j] + ')"/>';
							}

							if (db == null) {
								_this.insertAdjacentHTML('afterbegin', '<div class="date-block" data-cur="' + msg.cur + '">' + msg.date_text + '</div>');
								db = _this.querySelector('[data-cur="' + msg.cur + '"]');
							}

							db.nextElementSibling.insertAdjacentHTML('beforebegin', '<div id="msg-' + msg.id + '" class="msg-box ' + (me == msg.sender_id ? 'mine' : '') + '">'+
								(me == msg.sender_id ? '' : '<header><a href="' + msg.sender.url + '">' + msg.sender_name + '</a></header>')+
								'<div>' + msg.text + (txt.length > 0 ? '<div class="imgs-container">' + txt + '</div>' : '') + '<div class="msg-date">' + msg.date + '</div>'+
								(me == msg.sender_id ? (msg.state == 0 ? '</div><footer><i class="fa fa-check"></i> Доставлено</footer>' : '<footer><i class="fa fa-eye"></i> Просмотрено</footer>') : '') + '</div>');

							if (i + 1 == l1) id = msg.id;
						}

						ot = document.getElementById('msg-' + id);
						if (ot != null) _this.scrollTop = ot.offsetTop;
					}
				};

				request.send(fData);
			}
		};
	});

	document.querySelector('.dialog-send textarea').onkeypress = function (e) {
		if (e.keyCode == 13) {
			sendMessage(event, this.nextElementSibling, this.closest('form'));
		}
	};

	function sendMessage(event, _this, form) {
		event.preventDefault();
		event.stopPropagation();
		if (form.querySelector('textarea').value.length == 0 && form.querySelector('[type=file]').files.length == 0) return;

		saveForm(form, '/actions/sendntf/', _this).then((response) => {
			const data = response;

			if (data != null) {
				if (data.success) {
					if (data.text || data.imgs) {
						const imgs = data.imgs;
						const l = imgs.length;
						let txt = '';

						for (let i = 0; i < l; i++) {
							txt += '<img src="' + imgs[i] + '" onclick="showImage(' + imgs[i].match(/\d+/g)[0] + ')"/>';
						}

						const b = document.querySelector('.dialog-body');
						if (b.querySelector('[data-cur="0"]') == null) b.insertAdjacentHTML('beforeend', '<div class="date-block" data-cur="0">Сегодня</div>');

						b.insertAdjacentHTML('beforeend', '<div class="msg-box mine">'+
							'<div>' + data.text + (txt.length > 0 ? '<div class="imgs-container">' + txt + '</div>' : '') + '<div class="msg-date">' + data.date + '</div></div>'+
							'<footer><i class="fa fa-check"></i> Доставлено</footer></div>');

						b.scrollTop = b.scrollHeight;
					}
				}
			} else {
				showError(data.error ? data.error : 'Что-то пошло не так...');
			}
        }).catch((response) => {
            showError('Что-то пошло не так...');
        });

		document.querySelector('.dialog-send textarea').value = '';
		const ic = document.querySelector('.dialog-send .upload-img-container');
		if (ic != null) ic.remove();
	}
</script>
<?php } ?>
