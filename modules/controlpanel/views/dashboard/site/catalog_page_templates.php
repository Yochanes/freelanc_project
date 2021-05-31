<?php
  $param_names = [
    'make' => 'марка',
    'model' => 'модель',
    'generation' => 'поколение',
    'year' => 'год',
    'sku' => 'артикул',
    'category' => 'запчасть',
    'partnum' => 'Номер запчасти'
  ];

  foreach ($product_attributes as $attr) {
    $param_names['attribute_' . $attr->id] = mb_strtolower($attr->name);
  }

  $getGroupTemplate = function ($g) use ($param_names) {
    $params = $g->attribute_groups;

    $required = [];
    $required_sort = [];

    foreach ($params as $key => $val) {
      if ($val['is_required']) {
        $required[$key] = $val;
        $required_sort[$key] = $val['sort_order'];
      }
    }

    array_multisort($required_sort, SORT_ASC, $required);
    $str = '<div class="copy-this" data-title="Шаблоны">';
    $url_template = '';
    $url_template_friendly = '';

    foreach ($required as $key => $val) {
      $url_template .= '/' . $key;
      $url_template_friendly .= '/' . $param_names[$key];

      $template = false;

      foreach ($g->seoTemplates as $tmp) {
        if ($tmp->url_template == $url_template) {
          $template = $tmp;
          break;
        }
      }

      $str .= '<div style="margin-bottom:10px;text-align:left;padding:10px;background:rgba(0,0,0,0.1)">
        <div style="font-size:18px;color:#0000cc">
          <input type="hidden" name="url_template_name[]" value="' . $url_template_friendly . '">
          <input type="hidden" name="url_template[]" value="' . $url_template . '">
          <input type="checkbox" ' . ($template ? 'checked' : '') . ' data-value="1" name="url_template_on[]" onchange="toggleVisible(this, this.parentNode.nextElementSibling)">&nbsp;
          ' . $url_template_friendly . '
        </div>
        <div class="' . ($template ? '' : 'hidden') . '">
          <div>
            <p style="font-weight:bold;text-align:center;">Title страницы</p>
            <textarea name="url_template_title[]" style="width:100%">' . ($template ? $template->title : '') . '</textarea>
          </div>
          <div>
            <p style="font-weight:bold;text-align:center;">meta title</p>
            <textarea name="url_template_meta_title[]" style="width:100%">' . ($template ? $template->meta_title : '') . '</textarea>
          </div>
          <div>
            <p style="font-weight:bold;text-align:center;">Description страницы</p>
            <textarea name="url_template_meta_description[]" style="width:100%">' . ($template ? $template->meta_description : '') . '</textarea>
          </div>
          <div>
            <p style="font-weight:bold;text-align:center;">Текст</p>
            <textarea name="url_template_page_content[]" tinymce="true">' . ($template ? $template->page_content : '') . '</textarea>
          </div>
          <div>
            <p style="font-weight:bold;text-align:center;">meta robots</p>
            <input name="url_template_meta_robots[]" style="width:100%" value="' . ($template ? $template->meta_robots : '') . '">
          </div>
        </div>
      </div>';
    }

    $str .= '</div>';
    return $str;
  };
?>

<div class="panel_content">
  <?= (empty($items) ? '<h2 class="text-center">На данный момент вы не добавили ни одного элемента</h2>' : '') ?>
  <ul class="data-list drop-down-list flex hstart flex-wrap">
    <?php foreach ($items as $item) { ?>
      <li>
        <?= $item->name ?>
        <i class="fa fa-times" onclick="deleteElement(this, this.closest('li'), <?= $item->product_group_id ?>, '/controlpanel/data/deleteproductgroup/')"></i>
        <i class="fa fa-pencil-alt" onclick="createEditModal(this.nextElementSibling, '/controlpanel/data/updateproductgrouptemp/', 'Редактирование элемента <?= $item->name ?>')"></i>
        <form class="hidden">
          <input type="hidden" name="product_group_id" value="<?=$item->product_group_id ?>">
          <textarea name="title" data-title="Title страницы" value="<?=$item->title?>"><?=$item->title?></textarea>
          <textarea name="meta_title" data-title="meta title"><?= $item->meta_title ?></textarea>
          <textarea name="meta_description" data-title="Description страницы" value="<?=$item->meta_description?>"><?=$item->meta_description?></textarea>
          <textarea name="page_content" tinymce="true" data-title="Текст"><?= $item->page_content ?></textarea>
          <?=$getGroupTemplate($item);?>
        </form>
      </li>
    <?php } ?>
  </ul>
</div>
<script type="text/javascript">
  function toggleVisible(checkbox, e) {
    if (e && checkbox.checked) {
      e.classList.remove('hidden');
    } else if (e) {
      e.classList.add('hidden');
    }
  }
</script>
<script src="https://cdn.tiny.cloud/1/jbupo8lc5k1w0cfke858e2ngwkfxol0ivqpxo331lvlt7swv/tinymce/5/tinymce.min.js" referrerpolicy="origin">
