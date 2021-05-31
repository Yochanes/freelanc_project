<?php

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use app\modules\controlpanel\assets\AdminAsset;

use app\models\Products;
use app\models\user\Complaints;

AdminAsset::register($this);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
  <meta charset="<?= Yii::$app->charset ?>">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <?php $this->registerCsrfMetaTags() ?>
  <title><?= Html::encode($this->title) ?></title>
  <?php $this->head() ?>
  <link href="/web/assets/fontawesome/css/all.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet"/>
</head>
<body>
<script type="module">
  import { AJAXWrapper } from '/web/assets/js/http.js';
  window._http = new AJAXWrapper();
  document.addEventListener('DOMContentLoaded', () => { document.body.style.opacity = 1 })
</script>
<script type="text/javascript">
  (function () {
    if (!Element.prototype.matches) {
      Element.prototype.matches = Element.prototype.msMatchesSelector ||
        Element.prototype.webkitMatchesSelector;
    }
    if (!Element.prototype.closest) {
      Element.prototype.closest = function (s) {
        var el = this;
        do {
          if (el.matches(s)) return el;
          el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
      };
    }
  })();

  (function (arr) {
    arr.forEach(function (item) {
      if (item.hasOwnProperty('remove')) {
        return;
      }
      Object.defineProperty(item, 'remove', {
        configurable: true,
        enumerable: true,
        writable: true,
        value: function remove() {
          if (this.parentNode === null) {
            return;
          }
          this.parentNode.removeChild(this);
        }
      });
    });
  })([Element.prototype, CharacterData.prototype, DocumentType.prototype]);
</script>
<?php $this->beginBody() ?>
<div class="wrap">
  <?php
  NavBar::begin([
    'options' => [
      'class' => 'navbar-inverse navbar-fixed-top',
    ],
    'innerContainerOptions' => [
      'class' => 'container-fluid'
    ],
    'headerContent' => '<div class="sidenav_button"><span style="font-size:35px;cursor:pointer" id="toggleNavBtn" onclick="toggleNav(this)">&#9776;</span></div>'
  ]);

  echo Nav::widget([
    'options' => ['class' => 'nav navbar-nav navbar-right'],
    'items' => [
      [
        'label' => 'Вернуться а сайт',
        'url' => ['/'],
        'options' => [
          'class' => 'btn btn-danger navbar-btn',
          'onclick' => 'window.location.href=\'/\''
        ],
      ]
    ]
  ]);

  NavBar::end();
  ?>
  <div id="sidebar_nav" class="sidenav sidenav-hide">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="/controlpanel/dashboard/"<?= ($this->context->action->id == 'index' ? 'class="active"' : '') ?>>Главная</a>
    <a href="javascript:void(0)" onclick="this.nextElementSibling.classList.toggle('active');this.classList.toggle('active')"<?= (in_array($this->context->action->id, ['products', 'cars', 'tires', 'wheels', 'requests']) ? ' class="active"' : '') ?>>
      Объявления&nbsp;
      <i class="fa fa-caret-down<?= (in_array($this->context->action->id, ['products', 'cars', 'tires', 'wheels', 'requests']) ? ' active' : '') ?>"></i>
    </a>
    <ul
      class="menu-sublist<?= (in_array($this->context->action->id, ['products', 'cars', 'tires', 'wheels', 'requests']) ? ' active' : '') ?>">
      <li>
        <a<?= ($this->context->action->id == 'products' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/products/">
          Объявления
          <mark><?= Products::find()->where(['status' => Products::STATE_UNCHECKED])->count() ?></mark>
        </a>
      </li>
      <li><a<?= ($this->context->action->id == 'requests' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/requests/">Заявки на поиск</a></li>
    </ul>
    <a href="javascript:void(0)" onclick="this.nextElementSibling.classList.toggle('active');this.classList.toggle('active')"<?= (in_array($this->context->action->id, ['makegroups', 'productgroups', 'attributegroups', 'categories', 'uploadcolumns']) ? ' class="active"' : '') ?>>
      Параметры товаров&nbsp;
      <i class="fa fa-caret-down"></i>
    </a>
    <ul
      class="menu-sublist<?= (in_array($this->context->action->id, ['makegroups', 'productgroups', 'attributegroups', 'categories', 'uploadcolumns']) ? ' active' : '') ?>">
      <li><a<?= ($this->context->action->id == 'makegroups' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/makegroups/">Производители</a></li>
      <li><a<?= ($this->context->action->id == 'productgroups' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/productgroups/">Группы товаров</a></li>
      <li><a<?= ($this->context->action->id == 'categories' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/categories/">Категории товаров</a></li>
      <li><a<?= ($this->context->action->id == 'attributegroups' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/attributegroups/">Группы аттрибутов</a></li>
      <li><a<?= ($this->context->action->id == 'uploadcolumns' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/uploadcolumns/">Параметры загрузки</a></li>
    </ul>
    <a href="/controlpanel/dashboard/catalogs/" onclick="this.classList.toggle('active')"<?= (in_array($this->context->action->id, ['catalogs']) ? ' class="active"' : '') ?>>
      Каталоги&nbsp;
    </a>
    <a href="javascript:void(0)" onclick="this.nextElementSibling.classList.toggle('active');this.classList.toggle('active')"<?= (in_array($this->context->action->id, ['users', 'admins', 'complaints', 'messages', 'dialog']) ? ' class="active"' : '') ?>>
      Пользователи&nbsp;
      <i class="fa fa-caret-down<?= (in_array($this->context->action->id, ['users', 'admins', 'complaints', 'messages', 'dialog']) ? ' active' : '') ?>"></i>
    </a>
    <ul class="menu-sublist<?= (in_array($this->context->action->id, ['users', 'admins', 'complaints', 'messages', 'dialog']) ? ' active' : '') ?>">
      <li><a<?= ($this->context->action->id == 'users' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/users/">Пользователи</a></li>
      <li><a<?= ($this->context->action->id == 'admins' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/admins/">Админы</a></li>
      <li>
        <a<?= ($this->context->action->id == 'complaints' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/complaints/">Жалобы
          <mark><?= Complaints::find()->count() ?></mark>
        </a>
      </li>
      <li>
        <a<?= ($this->context->action->id == 'messages' || $this->context->action->id == 'dialog' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/messages/">Сообщения
          <mark><?= Yii::$app->user->identity->unreadNotifications ?></mark>
        </a>
      </li>
    </ul>
    <a href="javascript:void(0)" onclick="this.nextElementSibling.classList.toggle('active');this.classList.toggle('active')"<?= (in_array($this->context->action->id, ['pages', 'catalogpages', 'postpages', 'categorypages', 'productgroupstemp']) ? ' class="active"' : '') ?>>
      Страницы&nbsp;
      <i class="fa fa-caret-down<?= (in_array($this->context->action->id, ['pages', 'catalogpages', 'postpages', 'categorypages']) ? ' active' : '') ?>"></i>
    </a>
    <ul class="menu-sublist<?= (in_array($this->context->action->id, ['pages', 'catalogpages', 'postpages', 'categorypages', 'productgroupstemp']) ? ' active' : '') ?>">
      <li><a<?= ($this->context->action->id == 'pages' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/pages/">Основные</a></li>
      <li><a<?= ($this->context->action->id == 'postpages' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/postpages/">Статьи</a></li>
      <li><a<?= ($this->context->action->id == 'catalogpages' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/catalogpages/">Каталоги</a></li>
      <li><a<?= ($this->context->action->id == 'categorypages' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/categorypages/">Группы товаров</a></li>
      <li><a<?= ($this->context->action->id == 'productgroupstemp' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/productgroupstemp/">Шаблоны групп товаров</a></li>
    </ul>
    <a href="javascript:void(0)" onclick="this.nextElementSibling.classList.toggle('active');this.classList.toggle('active')"<?= (in_array($this->context->action->id, ['menus', 'cities', 'robots']) ? ' class="active"' : '') ?>>
      Сайт&nbsp;
      <i class="fa fa-caret-down<?= (in_array($this->context->action->id, ['menus', 'cities', 'robots', 'support']) ? ' active' : '') ?>"></i>
    </a>
    <ul class="menu-sublist<?= (in_array($this->context->action->id, ['menus', 'cities', 'robots', 'support']) ? ' active' : '') ?>">
      <li><a<?= ($this->context->action->id == 'cities' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/cities/">Страны и города</a></li>
      <li><a<?= ($this->context->action->id == 'robots' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/robots/">Robots.txt</a></li>
      <li><a<?= ($this->context->action->id == 'menus' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/menus/">Меню</a></li>
      <li><a<?= ($this->context->action->id == 'support' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/support/">Вопросы и ответы</a></li>
    </ul>
    <a href="javascript:void(0)" onclick="this.nextElementSibling.classList.toggle('active');this.classList.toggle('active')"<?= (in_array($this->context->action->id, ['smtp']) ? ' class="active"' : '') ?>>
      Настройки&nbsp;
      <i class="fa fa-caret-down<?= (in_array($this->context->action->id, ['smtp', 'schedule']) ? ' active' : '') ?>"></i>
    </a>
    <ul class="menu-sublist<?= (in_array($this->context->action->id, ['smtp', 'schedule']) ? ' active' : '') ?>">
      <li>
        <a<?= ($this->context->action->id == 'smtp' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/smtp/">SMTP</a>
      </li>
      <li>
        <a<?= ($this->context->action->id == 'schedule' ? ' class="active"' : '') ?> href="/controlpanel/dashboard/schedule/">Планировщик</a>
      </li>
    </ul>
  </div>
  <div class="container_wrap"><?= $content ?></div>
  <script type="text/javascript">
    const m = localStorage.getItem('admin_navbar_showed');

    if (m !== null) {
      document.getElementById('sidebar_nav').classList.remove('sidenav-hide');
      const el = document.getElementById('toggleNavBtn');
      el.classList.add('active');
      el.innerHTML = '&times;';
    }

    document.addEventListener('DOMContentLoaded', () => {
      let items = sessionStorage.getItem('drop_down_els');
      const action = "<?= $this->context->action->id ?>";

      try {
        if (items != null || items.length > 0) {
          items = JSON.parse(items);
        } else items = null;
      } catch (erro) {
        items = null;
      }

      if (items !== null && items[action]) {
        const arr = items[action].split(';');
        const l = arr.length;

        for (let i = 0; i < l; i++) {
          const e = document.getElementById(arr[i]);

          if (e != null) {
            e.classList.add('active');
            const fa = e.querySelector(':scope > .fa-plus');

            if (fa != null) {
              fa.classList.remove('fa-plus');
              fa.classList.add('fa-minus');
              fa.click();
            }
          }
        }
      }
    });
  </script>
</div>
<?php $this->endBody() ?>
</body>
<script type="text/javascript">
  function toggleNav(el) {
    if (el.classList.contains('active')) {
      closeNav();
      el.classList.remove('active');
      el.innerHTML = '&#9776;';
      localStorage.removeItem('admin_navbar_showed');
    } else {
      document.getElementById("sidebar_nav").classList.remove('sidenav-hide');
      el.classList.add('active');
      el.innerHTML = '&times;';
      localStorage.setItem('admin_navbar_showed', true);
    }
  }

  function closeNav() {
    document.getElementById('sidebar_nav').classList.add('sidenav-hide');
    const el = document.getElementById('toggleNavBtn');
    el.classList.remove('active');
    el.innerHTML = '&#9776;';
    localStorage.removeItem('admin_navbar_showed');
  }

  function createEditModal(form, action, title) {
    if (form === null || typeof form === 'undefined') return false;
    const els = form.querySelectorAll(':scope > input[type=hidden], :scope > input[type=text], :scope > input[type=number], :scope > input[type=checkbox]:not(.not_separate), :scope > input[type=radio], :scope > input[type=email], :scope > select, :scope > textarea, :scope > .file_container, :scope > .multi, :scope > .array, :scope > .copy-this');
    const l = els.length;
    if (l === 0) return false;
    const c = document.createElement('form');
    c.setAttribute('name', 'editForm');

    for (let i = 0; i < l; i++) {
      const inp = els[i];
      if (inp.classList.contains('file_container')) {
        const e = inp.cloneNode(true);
        c.insertAdjacentHTML('beforeend', '<section class="flex vstart hspaced"><header>Изображение</header><aside></aside></section>');
        c.lastElementChild.lastElementChild.appendChild(e);

        e.querySelector('[type=file]').onchange = function () {
          let img;

          if (this.files[0].type.indexOf('image/') >= 0) {
            if (this.previousElementSibling == null || this.previousElementSibling.tagName !== 'IMG') {
              img = document.createElement('img');
              e.insertBefore(img, this);
            } else img = this.previousElementSibling;

            if (FileReader && this.files && this.files.length) {
              const fr = new FileReader();
              fr.onload = function () {
                img.src = fr.result;
              };
              fr.readAsDataURL(this.files[0]);
            }
          } else {
            if (this.previousElementSibling == null || this.previousElementSibling.tagName !== 'P') {
              this.insertAdjacentHTML('beforebegin', '<p>' + this.files[0].name + '</p>');
            }
            this.previousElementSibling.innerHTML = this.files[0].name;
          }
        };
      } else if (inp.tagName === 'INPUT' && inp.type === 'hidden') {
        c.appendChild(inp.cloneNode(true));
      } else if (inp.tagName === 'DIV' && inp.classList.contains('multi')) {
        c.insertAdjacentHTML('beforeend', '<section class="flex vstart hspaced"><header>' + inp.getAttribute('data-title') + '</header><aside></aside></section>');
        c.lastElementChild.lastElementChild.innerHTML = inp.innerHTML;
      } else if (inp.tagName === 'DIV' && inp.classList.contains('array')) {
        c.insertAdjacentHTML('beforeend', '<section class="flex vstart hspaced"><header>' + inp.getAttribute('data-title') + '</header><aside class="array"></aside></section>');
        c.lastElementChild.lastElementChild.appendChild(inp.cloneNode(true));
      } else if (inp.tagName === 'DIV' && inp.classList.contains('copy-this')) {
        c.insertAdjacentHTML('beforeend', '<section class="flex vstart hspaced"><header>' + inp.getAttribute('data-title') + '</header><aside class="array"></aside></section>');
        c.lastElementChild.lastElementChild.innerHTML = inp.innerHTML;
      } else {
        let e = inp.cloneNode(true);
        e.className = "simple_input";
        c.insertAdjacentHTML('beforeend', '<section class="flex vcentered flex-wrap" data-name="' + inp.getAttribute('name') + '"><aside class="flex"></aside></section>');
        if (inp.getAttribute('data-title') != null) c.lastElementChild.insertAdjacentHTML('afterbegin', '<header>' + inp.getAttribute('data-title') + '</header>');

        if (inp.classList.contains('array')) {
          let val = inp.value.split(';');
          const cc = c.lastElementChild.lastElementChild;
          cc.classList.add('flex-wrap');

          for (let k = 0; k < val.length; k++) {
            e = e.cloneNode(true);
            const d = document.createElement('div');
            d.className = 'full-width';
            e.value = val[k];
            d.appendChild(e);
            d.insertAdjacentHTML('beforeend', '<i class="fa fa-minus" onclick="delArrayElement(this)"></i>');
            cc.appendChild(d);
          }

          cc.lastElementChild.insertAdjacentHTML('beforeend', '&nbsp;<i class="fa fa-plus" onclick="addArrayElement(this)"></i>');
        } else {
          c.lastElementChild.lastElementChild.appendChild(e);
        }

        if (e.getAttribute('type') == 'checkbox') {
          e.onchange = function () {
            this.value = this.checked ? 1 : 0;
          }
          e.value = e.checked ? 1 : 0;
        }
      }
    }

    c.insertAdjacentHTML('beforeend', '<div class="buttons"><button class="btn btn-primary" onclick="event.preventDefault();event.stopPropagation();saveForm(this.closest(\'form\'),\'' + action + '\', this);">Сохранить</button></div>');
    createModal(c, title);
    if (typeof tinymce == 'undefined') return;

    tinymce.init({
      plugins: 'code image',
      toolbar: 'code',
      selector: '[tinymce]',
      toolbar_drawer: 'floating',
      valid_children: '+body[style]',
      tinycomments_mode: 'embedded',
        images_upload_url: '/site/upload/',
        images_upload_handler: function (blobInfo, success, failure) {
            let xhr, formData;
            xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', '/site/upload/');

            xhr.setRequestHeader("X-CSRF-Token", yii.getCsrfToken());
            xhr.onload = function() {
                var json;
                if (xhr.status != 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }
                json = JSON.parse(xhr.responseText);
                if (!json || typeof json.location != 'string') {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }
                success(json.location);
            };
            formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            xhr.send(formData);
        },
    });
  }

  function saveForm(form, action, _this) {
    return new Promise((resolve, reject) => {
      if (form == null) return;
      if (form.querySelector('[tinymce]') != null) tinyMCE.triggerSave();
      if (typeof action !== 'string') action = window.location.pathname;
      let fData = new FormData(form);
      fData.append("<?= Yii::$app->request->csrfParam ?>", "<?= Yii::$app->request->getCsrfToken() ?>");
      const checkboxes = form.querySelectorAll('[type=checkbox], [type=radio]');
      for (let cb of checkboxes) {
        if (cb.getAttribute('data-value') != null) {
          fData.delete(cb.getAttribute('name'))
        }
      }
      for (cb of checkboxes) {
        fData.append(cb.getAttribute('name'), cb.checked ? (cb.getAttribute('data-value') != null ? cb.getAttribute('data-value') : '1') : '0');
      }
      if (action.substr(-1) !== '/') {
        action = action + '/';
      }
      sendPostRequest(fData, action, _this, form, reject, resolve);
    });
  }

  function sendObject(obj, action, _this) {
    return new Promise((resolve, reject) => {
      if (typeof obj != 'object' || obj == null) return;
      const fData = new FormData();
      for (let i in obj) fData.append(i, obj[i]);
      sendPostRequest(fData, action, _this, null, reject, resolve);
    });
  }

  function sendPostRequest(fData = new FormData(), action, _this, form = null, resolve = function () {
  }, reject = function () {
  }) {
    let btxt = _this.textContent;
    if (_this['origContent'] != undefined) btxt = _this['origContent'];
    _this.setAttribute('disabled', true);
    _this.textContent = 'Сохранение...';
    const request = new XMLHttpRequest();
    request.open("POST", action, true);

    request.onerror = function (e) {
      _this.removeAttribute('disabled');
      _this.textContent = btxt;
      reject(e.target);
    };

    const onloadf = function (_this, btxt, msg, success, response) {
      _this.textContent = msg;
      _this.removeAttribute('disabled');

      if (!window.disableReloadOnSubmit) {
        window.setTimeout(function () {
          _this.textContent = btxt;

          if (success && !window.disableReloadOnSubmit) {
            window.location.reload();
          } else if (success) {
            resolve(response);
          } else reject(response);
        }, 2000);
      } else {
        _this.textContent = btxt;

        if (success && !window.disableReloadOnSubmit) {
          window.location.reload();
        } else if (success) {
          resolve(response);
        } else reject(response);
      }
    };

    request.onload = function (e) {
      const txt = e.target.responseText;
      const errc = form != null ? form.querySelectorAll('.error-container, .with-error') : null;

      if (errc != null) {
        for (let j = 0; j < errc.length; j++) {
          if (errc[j].classList.contains('error-container')) {
            errc[j].remove();
          } else errc[j].classList.remove('.with-error');
        }
      }

      if (txt.length > 0) {
        try {
          const data = JSON.parse(txt);
          const ec = form != null ? form.querySelector('.error-msg') : null;
          if (ec !== null) ec.remove();

          if (data.error) {
            onloadf(_this, btxt, 'Ошибка!', false, data);

            if (form != null) {
              form.insertAdjacentHTML('afterbegin', '<section class="error-msg with-error">' + (data.error !== true ? data.error : 'Ошибка') + '</section>');
            }

            if (data.errors) {
              for (let er in data.errors) {
                const fi = form != null ? form.querySelector('[data-name="' + er + '"]') : null;

                if (fi != null) {
                  let etxt = "";
                  for (let b = 0; b < data.errors[er].length; b++) etxt += '<div>' + data.errors[er][b] + '</div>';
                  fi.insertAdjacentHTML('beforeend', '<div class="error-container">' + etxt + '</div>');
                  fi.classList.add('with-error');

                  fi.onmousedown = function () {
                    this.classList.remove('with-error');
                    if (this.querySelector('.error-container') != null) this.querySelector('.error-container').remove();
                  };
                }
              }
            }

            return;
          }

          if (data.success) {
            onloadf(_this, btxt, 'Сохранено!', true, data);
          }
        } catch (err) {
          console.log(err);
          onloadf(_this, btxt, 'Ошибка!', false, err);
        }
      } else onloadf(_this, btxt, 'Ошибка!', false, null);
    };

    request.send(fData);
  }

  function deleteElement(el, parent, id, url) {
    if (isNaN(id)) return false;
    if (typeof url !== 'string') url = window.location.pathname;
    if (!confirm('Вы действительно хотите удалить этот элемент?')) return;
    if (el.classList.contains('deleting')) return false;
    el.classList.add('deleting');
    const request = new XMLHttpRequest();
    request.open("POST", url, true);
    const fData = new FormData();
    fData.append("<?= Yii::$app->request->csrfParam ?>", "<?= Yii::$app->request->getCsrfToken() ?>");
    fData.append('id', id);

    request.onerror = function (e) {
      el.classList.remove('deleting');
      createAlert('Ошибка удаления');
    };

    request.onload = function (e) {
      const txt = e.target.responseText;
      el.classList.remove('deleting');

      if (txt.length > 0) {
        try {
          const data = JSON.parse(txt);

          if (data.error) {
            createAlert(data.error);
            return;
          }

          parent.remove();
        } catch (err) { createAlert('Ошибка удаления') }
      } else createAlert('Ошибка удаления');
    };

    request.send(fData);
  }

  function addArrayElement(e, p) {
    if (!p) p = e.parentNode;
    const clone = p.cloneNode(true);
    p.parentNode.appendChild(clone);
    p.parentNode.lastElementChild.firstElementChild.value = '';
    e.classList.remove('fa-plus');
    e.classList.add('fa-minus');
    e.setAttribute('onclick', 'delArrayElement(this)');
  }

  function delArrayElement(e) {
    const p = e.parentNode;

    if (p.nextElementSibling != null) {
      p.remove();
    } else {
      p.firstElementChild.value = '';
      e.remove();
      e.setAttribute('onclick', 'addArrayElement(this)');
    }
  }

  function createModal(data, title = '') {
    hideModal();
    if (typeof data === 'undefined') return false;
    const p = document.createElement('div');
    p.onclick = function (ev) { if (ev.target == this) hideModal() }

    p.className = 'modal-wrapper flex vcentered hcentered flex-wrap';
    document.body.appendChild(p);
    p.insertAdjacentHTML('beforeend', `<div class="col-lg-11 col-sm-12"><header>${title}<i onclick="hideModal()" class="fa fa-times"></i></header><article></article></div>`);
    const a = p.firstElementChild.lastElementChild;

    if (typeof data == 'string') {
      a.insertAdjacentHTML('beforeend', data);
    } else a.appendChild(data);
  }

  function hideModal() {
    if (typeof tinymce !== 'undefined') {
      for (let tmc of document.querySelectorAll('[tinymce]')) {
        if (tmc.id && tmc.id.indexOf('mce_') >= 0) {
          tinymce.remove(tmc.id);
          tinymce.execCommand('mceRemoveControl', true, tmc.id);
        }
      }
      tinymce.remove("[tinymce]");
    }
    const e = document.querySelector('.modal-wrapper');
    if (e !== null) e.remove();
  }

  function createAlert(txt) {
    alert(txt);
  }

  function toggleList(_this) {
    const p = _this.parentNode;
    p.classList.toggle('active');
    let items = sessionStorage.getItem('drop_down_els');

    try {
      if (items != null || items.length > 0) {
        items = JSON.parse(items);
      } else items = null;
    } catch (erro) {
      items = null;
    }

    const action = "<?= $this->context->action->id ?>";
    if (items == null) items = {};
    if (items[action] == undefined) items[action] = '';
    let id = p.id;

    if (id != undefined && id.length > 0) {
      items[action] = items[action].replace(id + ';', '');
    } else id = '';

    if (p.classList.contains('active')) {
      _this.classList.remove('fa-plus');
      _this.classList.add('fa-minus');
      items[action] += id + ';';
    } else {
      _this.classList.remove('fa-minus');
      _this.classList.add('fa-plus');
    }

    sessionStorage.setItem('drop_down_els', JSON.stringify(items));
  }

  function loadModelList(_this) {
    const id = _this.options[_this.selectedIndex].getAttribute('data-id');

    $.ajax({
      url: '/site/json',
      type: "POST",
      dataType: "json",
      data: {type: "select_model", make: id},
      success: function (data) {
        $("#select_model").html("").attr("disabled", "disabled");

        if (data.success) {
          $("#select_model").html(data.options_list).removeAttr('disabled');
        }
      }
    });
  }

  function loadGenerationsList(_this) {
    const id = _this.options[_this.selectedIndex].getAttribute('data-id');

    $.ajax({
      url: '/site/json',
      type: "POST",
      dataType: "json",
      data: {type: "select_generation", model: id},
      success: function (data) {
        $("#select_generation").html("").attr("disabled", "disabled");

        if (data.success) {
          $("#select_generation").html(data.options_list).removeAttr('disabled');
        }
      }
    });
  }

  function searchByParam(obj) {
    const s = window.location.search;
    const p = new URLSearchParams(s);

    for (let i in obj) {
      if (obj[i].toString().length > 0) {
        p.set(i, obj[i]);
      } else p.delete(i);
    }

    window.location.search = p.toString();
  }

  $(document).ready(function () {
    $('.input-select').select2();
  });

  function parseToObject(data) {
    if (data === undefined) return null;
    if (data === null) return data
    if (typeof data === 'string' && data.trim().length === 0) return null

    if (typeof data === 'string') {
      try {
        const o = JSON.parse(data);
        return o;
      } catch (e) {
        console.log("************ ERROR PARSING OBJECT ************");
        console.log(e);
        console.log(data);
        console.log("**********************************************");
        return null;
      }
    }

    if (typeof data === 'object') return data
    return null
  }

  function addPreloader (el) {
    el.classList.add('pos-relative');

    const w = el.clientWidth;
    const h = el.clientHeight;
    let p = 100;

    if (w > h) {
      p = h * 100 / 58;
    } else p = w * 100 / 58;

    p = p - p/4;
    el.insertAdjacentHTML('beforeend', `<div id="circularG-wrap"><div id="circularG" style="zoom:${p}%"><div id="circularG_1" class="circularG"></div><div id="circularG_2" class="circularG"></div><div id="circularG_3" class="circularG"></div><div id="circularG_4" class="circularG"></div><div id="circularG_5" class="circularG"></div><div id="circularG_6" class="circularG"></div><div id="circularG_7" class="circularG"></div><div id="circularG_8" class="circularG"></div></div></div>`);
  }

  function delPreloader (el) {
    el.classList.remove('pos-relative');
    const pr = el.querySelector('#circularG-wrap');
    if (pr !== null) pr.remove();
  }
</script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
</html>
<?php $this->endPage() ?>
