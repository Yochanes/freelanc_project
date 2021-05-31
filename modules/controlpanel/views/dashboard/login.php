<?php
	use yii\helpers\Html;
	use yii\bootstrap\ActiveForm;
 ?>

<div class="flex vcentered flex-wrap full-height hcentered">
	<div class="col-lg-4"></div>
  <?php $form = ActiveForm::begin([
		'id' => 'login-form',
		'layout' => 'horizontal',
		'options' => [
			'class' => 'col-lg-4'
		],
		'fieldConfig' => [
			'inputOptions' => ['class' => 'col-sm-4 form-control'],
			'labelOptions' => ['class' => 'text-left col-lg-12'],
			'template' => "\n{label}\n<div class=\"col-lg-12\">{input}</div><div class=\"col-lg-12 form-error text-left\">{error}</div>",
		],
	]); ?>
		<?= $form->field($model, 'username')->textInput(['autofocus' => true])->label('Имя пользователя')?>
		<?= $form->field($model, 'password')->passwordInput()->label('Пароль')?>
		<div class="row flex vcentered">
      <div class="col-lg-6">
        <div class="form-group">
          <?= Html::submitButton('Войти', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>
      </div>
      <div class="col-lg-6">
        <?= $form->field($model, 'rememberMe')->checkbox([
          'template' => "<div class=\"col-lg-12\">{input} {label}</div>",
        ])->label('Запомнить меня') ?>
      </div>
    </div>
	<?php ActiveForm::end(); ?>
  <div class="col-lg-4"></div>
</div>

