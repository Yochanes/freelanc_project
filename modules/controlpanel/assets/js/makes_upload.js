const make_columns = {
  'марка': 'make_name',
  'модель': 'model_name',
  'поколение': 'generation_name',
  'год': 'generation_years',
  'версия': 'alt_name',
  'популярность': 'is_popular',
  'тип': 'make_group_id'
};

const make_group_columns = {
  'марка': 'make_name',
  'легковая': 'import_filter_c',
  'грузовик': 'import_filter_d',
  'мотоцикл': 'import_filter_e',
  'автобус': 'import_filter_f',
  'микроавтобус': 'import_filter_g'
};

let makes_to_upload = {};

function processFile(file, _this, btn, sheet_num, columns, group_id) {
  let val = btn.textContent;
  btn.origVal = val;
  btn.setAttribute('disabled', true);
  btn.textContent = 'Обработка файла...';

  const onerror = function (txt) {
    showError(txt);
    //_this.value = '';
    btn.removeAttribute('disabled');
    btn.textContent = val;
  };

  if (file.name.indexOf('.xlsx') > 0) {
    processXLSX(file, sheet_num).then((json, sheetName) => {
      //_this.value = '';
      if (!group_id) {

      }
      processJSON(json, _this, btn, columns, group_id);
    }).catch((ex) => {
      console.log(ex);
      onerror('Что-то пошло не так...');
    });
  } else if (file.name.indexOf('.xls') > 0) {
    processXLS(file, sheet_num).then((json, sheetName) => {
      processJSON(json, _this, btn, columns, group_id);
    }).catch((ex) => {
      console.log(ex);
      onerror('Что-то пошло не так...');
    });
  } else {
    onerror('Неподдерживаемый тип файла');
  }
}

function processXLS(file, sheet_num) {
  return new Promise((resolve, reject) => {
    var reader = new FileReader();

    reader.onload = function (e) {
      var data = e.target.result;
      var cfb = XLS.CFB.read(data, {type: 'binary'});
      var wb = XLS.parse_xlscfb(cfb);

      if (wb.SheetNames.length) {
        const json = XLS.utils.sheet_to_row_object_array(wb.Sheets[wb.SheetNames[sheet_num]]);
        resolve(json, wb.SheetNames[sheet_num]);
      }
    };

    reader.onerror = function (ex) { reject(ex) };
    reader.readAsBinaryString(file);
  });
}

function processXLSX(file, sheet_num) {
  return new Promise((resolve, reject) => {
    var reader = new FileReader();

    reader.onload = function (e) {
      var data = e.target.result;
      var workbook = XLSX.read(data, {type: 'binary'});

      if (workbook.SheetNames.length) {
        const json = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[sheet_num]]);
        resolve(json, workbook.SheetNames[sheet_num]);
      }
    };

    reader.onerror = function (ex) { reject(ex) };
    reader.readAsBinaryString(file);
  });
}

function processJSON(json, _this, btn, columns, group_id) {
  console.log('Processing json...');
  console.log(json);
  let val = btn.origVal;
  btn.textContent = 'Обработка данных...';
  const l = json.length;
  let current = 0;
  let max = 400;
  let to_insert = 0;
  let to_update = 0;
  let inserted = 0;
  let updated = 0;

  function sendData(data, current) {
    return new Promise((resolve, reject) => {
      const result = [];
      const l1 = data.length;

      for (let i = 0; i < l1; i++) {
        const obj = {};

        for (let j in data[i]) {
          let col = j.toLowerCase().trim();
          let col_found = false;

          if (!columns[col]) {
            for (let k in columns) {
              if (col.indexOf(k) >= 0) {
                col = columns[k];
                col_found = true;
                break;
              }
            }
          } else {
            col_found = true;
            col = columns[col];
          }

          if (!col_found) continue;

          if (col === 'image') {
            let images = data[i][j].split(',');
            const il = images.length;
            const arr = [];
            for (let h = 0; h < il; h++) arr.push(images[h].trim());
            obj[col] = arr;
          } else if (col === 'make_group_id') {
            let val = data[i][j].toLowerCase().trim();
            if (make_groups[val]) {
              obj[col] = make_groups[val];
            }
          } else if (col === 'is_popular') {
            let val = data[i][j];
            if (val && val.length > 0 && val.trim().length > 0) {
              obj[col] = 1;
            } else {
              obj[col] = 0;
            }
          } else if (col.indexOf('import_filter') >= 0) {
            if (!obj['import_filter']) obj['import_filter'] = [];
            obj['import_filter'].push(data[i][j].trim());
          } else obj[col] = data[i][j].trim();
        }

        if (!obj.is_popular) obj.is_popular = 0;
        if (Object.keys(obj).length > 0) result.push(obj);
      }

      if (result.length > 0) {
        submitData(btn.action, result, group_id, current).then((response) => {
          resolve(response);
        }).catch((response) => {
          console.log(response);
          reject(response)
        });
      } else resolve(null);
    });
  }

  const s = function (current, max) {
    if (current + max > l) max = l - current;
    btn.textContent = `Сохранение...${Math.floor(((current + max) * 100 / l))}%`;
    console.log(`Отправка данных с ${current} по ${max} из ${l}`);

    sendData(json.slice(current, current + max), current).then((data) => {
      if (data !== null && data.success) {
        current = current + max
        if (!isNaN(data.to_insert)) to_insert = to_insert + data.to_insert;
        if (!isNaN(data.to_update)) to_update = to_update + data.to_update;
        if (!isNaN(data.inserted)) inserted = inserted + data.inserted;
        if (!isNaN(data.updated)) updated = updated + data.updated;

        if (current >= l) {
          createModal(`<div><p>Найдено записей: ${to_update}</p><p>Обновлено записей: ${updated}</p></div>`, 'Результат');
          btn.textContent = val;
          btn.removeAttribute('disabled');
        } else s(current, max);
      } else {
        btn.textContent = val;
        btn.removeAttribute('disabled');
      }
    }).catch((response) => {
      console.log(response);
      alert('Что-то пошло не так...');
      btn.textContent = val;
      btn.removeAttribute('disabled');
    });
  };

  s(current, max);
}

function submitData(action, data, group_id, current) {
  return new Promise((resolve, reject) => {
    console.log(data);
    let fData = new FormData();
    fData.append('data', JSON.stringify(data));
    fData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->getCsrfToken() ?>');
    if (!isNaN(group_id)) fData.append('group_id', group_id);
    const cb = document.querySelector('[name=editForm] [name=clear_makes]');
    if (cb != null) fData.append('clear_makes', cb.checked ? 1 : 0);
    fData.append('current', current);
    const request = new XMLHttpRequest();
    request.open("POST", action, true);
    request.onerror = (e) => { reject(e) };

    request.onload = (e) => {
      const data = parseToObject(e.target.responseText);

      if (data != null && data.success) {
        resolve(data);
      } else reject(data);
    };

    request.send(fData);
  });
}