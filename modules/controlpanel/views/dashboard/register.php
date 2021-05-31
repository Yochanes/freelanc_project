<?php
	use yii\helpers\Html;
	use yii\bootstrap\ActiveForm;
 ?>

<div class="flex vcentered flex-wrap full-height hcentered">
	<?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'layout' => 'horizontal',
		'options' => [
            'class' => 'col-lg-4 col-sm-12'
        ],
        'fieldConfig' => [
            'labelOptions' => ['class' => 'text-left col-lg-12'],
			'template' => "\n{label}\n<div class=\"col-lg-12\">{input}</div><div class=\"col-lg-12 form-error text-left\">{error}</div>",
        ],
    ]); ?>
		<h2 class="full-width">Вы еще не добавили ни однго администратора</h2>
        <?= $form->field($model, 'username')->textInput(['autofocus' => true])->label('Имя пользователя')?>

        <?= $form->field($model, 'password')->passwordInput()->label('Пароль')?>
		
		<?= $form->field($model, 'repeat_password')->passwordInput()->label('Повторите пароль')?>

        <div class="form-group">
            <div class="col-lg-12">
                <?= Html::submitButton('Добавить', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            </div>
        </div>

    <?php ActiveForm::end(); ?>
</div>

