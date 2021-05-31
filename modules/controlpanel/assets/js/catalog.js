let synonyms = {};

function importCategories(ev, _this) {
  ev.preventDefault();
  ev.stopPropagation();
  createEditModal(_this.nextElementSibling, '/controlpanel/data/uploadcategories/', 'Импортирование наименований запчастей');
  const btn = document.querySelector('[name=editForm] .buttons .btn');
  btn.action = '/controlpanel/data/uploadcategories/';
  btn.removeAttribute('onclick');

  btn.onclick = function (evt) {
    evt.preventDefault();
    evt.stopPropagation();
    let sheet_num = 0;
    let clear = 0;

    const gs = document.querySelector('[name=editForm] [name=clear]');
    if (gs != null && gs.checked) clear = 1;
    const file_inp = document.querySelector('[name=editForm] [type=file]');

    if (file_inp.files.length) {
      processFile(file_inp.files[0], file_inp, this, sheet_num, category_columns, clear);
    } else {
      this.origTxt = this.textContent;
      this.textContent = 'Необходимо загрузить файл импорта';

      setTimeout(function () {
        this.textContent = this.origTxt;
      }, 3000)
    }
  };
}

function processFile(file, _this, btn, sheet_num, columns, clear) {
  let val = btn.textContent;
  btn.origVal = val;
  btn.setAttribute('disabled', true);
  btn.textContent = 'Обработка файла...';

  const onerror = function (txt) {
    alert(txt);
    //_this.value = '';
    btn.removeAttribute('disabled');
    btn.textContent = val;
  };

  if (file.name.indexOf('.xlsx') > 0) {
    processXLSX(file, sheet_num).then((json) => {
      //_this.value = '';
      processJSON(json, _this, btn, columns, clear);
    }).catch((ex) => {
      console.log(ex);
      onerror('Что-то пошло не так...');
    });
  } else if (file.name.indexOf('.xls') > 0) {
    processXLS(file, sheet_num).then((json) => {
      processJSON(json, _this, btn, columns, clear);
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
        resolve(json);
      }
    };

    reader.onerror = function (ex) {
      reject(ex);
    };

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
        resolve(json);
      }
    };

    reader.onerror = function (ex) {
      reject(ex);
    };

    reader.readAsBinaryString(file);
  });
}

function processRow(columns, data) {
  const obj = {};

  for (let j in data) {
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
    data[j] = data[j].trim();

    if (col.indexOf('synonym') >= 0) {
      if (!obj.synonym) {
        obj.synonym = [];
      }
      if (data[j].length > 0) {
        if (obj.name && data[j].toLowerCase() !== obj.name.toLowerCase() && obj.synonym.indexOf(data[j].toLowerCase()) < 0) {
          obj.synonym.push(data[j].toLowerCase());
        }
      }
    } else if (col.indexOf('attributes_required') >= 0) {
      if (!obj[col]) {
        obj.attributes_required = [];
      }
      if (data[j].length > 0 && attribute_groups[j.toLowerCase().trim()]) {
        const attr_group = attribute_groups[j.toLowerCase().trim()];
        if (data[j].trim() === '+') {
          obj[col].push(attr_group.id);
        } else if (attr_group.values && attr_group.values.length > 0) {
          for (let aa of attr_group.values) {
            if (!aa.values || aa.values.length === 0) break;
            let attrFound = false;
            for (let av of aa.values) {
              if (av.toLowerCase().trim() === data[j].toLowerCase().trim()) {
                if (!obj.connected_attributes) {
                  obj.connected_attributes = [];
                }
                if (!obj.parent_name) obj.parent_name = obj.name;
                let split = obj.parent_name.split(' ');
                for (let spl of split) {
                  if (aa.values.some(aaval => aaval.toLowerCase() === spl)) {
                    obj.parent_name = obj.parent_name.replaceAll(new RegExp(spl.toLowerCase().trim(), 'gi'), '');
                    break;
                  }
                }

                obj.connected_attributes.push({
                  attribute_group_id: attr_group.id,
                  attribute_id: aa.attribute_id,
                  value: aa.value,
                  url: aa.url
                });
                attrFound = true;
                break;
              }
            }
            if (attrFound) {
              break;
            }
          }
        }
      }
    } else if (col ===  'partnum_required' || col === 'generation_required') {
      obj[col] = data[j].indexOf('+') >= 0 ? 1 : 0;
    } else {
      obj[col] = data[j];
    }
  }

  if (obj.synonym) {
    obj.synonym.map(s => {
      if (s.toLowerCase() !== obj.name.toLowerCase()) {
        synonyms[s.toLowerCase()] = obj.name
      }
    })
  }
  return obj;
}

function processJSON(json, _this, btn, columns, clear) {
  console.log(json);
  let val = btn.origVal;
  btn.textContent = 'Обработка данных...';
  let l = json.length;
  let current = 0;
  let max = 500;
  let to_insert = 0;
  let to_update = 0;
  let inserted = 0;
  let updated = 0;
  const result = {};

  for (let i = 0; i < l; i++) {
    const obj = processRow(columns, json[i]);
    if (!obj) continue;
    if (Object.keys(obj).length > 0) {
      result[obj.name] = obj;
    }
  }

  console.log(synonyms);
  console.log(result);
  const resultToSend = [];

  for (let i in result) {
    const r = result[i];
    if (!r.name) {
      continue;
    }
    const name = r.name.toLowerCase();
    const catName = synonyms[name];
    if (catName && result[catName]) {
      if (!result[catName].synonym) {
        result[catName].synonym = [];
      }
      if (name.toLowerCase() !== catName.toLowerCase() && result[catName].synonym.indexOf(name) < 0) {
        result[catName].synonym.push(r.name.toLowerCase());
      }
      if (r.synonym) {
        r.synonym.map(s => {
          if (s.toLowerCase() !== catName.toLowerCase() && r.synonym.indexOf(s) < 0) {
            result[catName].synonym.push(s);
          }
        })
      }
    } else {
      if (r.override_parent && result[r.override_parent]) {
        r.parent_name = result[r.override_parent].name;
      }
      const split = r.name.split(' ');

      for (let s of split) {
        const a = attr_group_values[s.toLowerCase().trim()];
        if (a) {
          if (!r.connected_attributes) {
            r.connected_attributes = [];
          }
          if (!r.parent_name) {
            r.parent_name = r.name;
          }
          r.parent_name = r.parent_name.replaceAll(new RegExp(s.toLowerCase().trim(), 'gi'), '');
          r.connected_attributes.push(a);
        }
      }

      if (r.connected_attributes) {
        const cai = [];
        const rca = [];
        for (let ca of r.connected_attributes) {
          if (cai.indexOf(ca.attribute_group_id) < 0) {
            rca.push(ca);
            cai.push(ca.attribute_group_id);
          }
        }
        r.connected_attributes = rca;
      }
      if (r.parent_name === r.name) {
        delete r.parent_name;
      } else if (r.parent_name) {
        r.parent_name = r.parent_name.replaceAll(/[\s]{2,}/g, ' ').trim();
      }
      resultToSend.push(r);
    }
  }

  l = resultToSend.length;
  console.log(resultToSend);

  function sendData(data, current) {
    return new Promise((resolve, reject) => {
      submitData(btn.action, data, clear, current).then((response) => {
        resolve(response);
      }).catch((response) => {
        reject(response);
      });
    });
  }

  const s = function (current, max) {
    if (current + max > l) max = l - current;
    btn.textContent = 'Сохранение...' + Math.floor(((current + max) * 100 / l)) + '%';

    sendData(resultToSend.slice(current, current + max), current).then(data => {
      if (data !== null && data.success) {
        current = current + max;
        if (!isNaN(data.to_insert)) to_insert += data.to_insert;
        if (!isNaN(data.to_update)) to_update += data.to_update;
        if (!isNaN(data.inserted)) inserted += data.inserted;
        if (!isNaN(data.updated)) updated += data.updated;

        if (current >= l) {
          synonyms = {};
          createModal(`<div><p>Новых записей: ${to_insert}</p><p>Вставлено записей: ${inserted}</p></div><div><p>Найдено записей: ${to_update}</p><p>Обновлено записей: ${updated}</p></div>`, 'Результат');
          btn.textContent = val;
          btn.removeAttribute('disabled');

          buildTree(new MouseEvent('click', {
            'view': window,
            'bubbles': true,
            'cancelable': true
          }), document.getElementById('buildTreeBtn'), true);
          window._http.get('/controlpanel/data/updateurlparams/');
        } else {
          s(current, max);
        }
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

function submitData(action, data, clear, current) {
  return new Promise((resolve, reject) => {
    console.log(data);
    let fData = new FormData();
    fData.append('data', JSON.stringify(data));
    fData.append(__csrf[0], __csrf[1]);
    if (!isNaN(clear)) fData.append('clear', clear);
    fData.append('current', current);
    const request = new XMLHttpRequest();
    request.open("POST", action, true);

    request.onerror = (e) => {
      reject(e);
    };

    request.onload = (e) => {
      const data = parseToObject(e.target.responseText);

      if (data != null && data.success) {
        resolve(data);
      } else reject(data);
    };

    request.send(fData);
  });
}

function buildTree(ev, _this, noConfirm = false) {
  ev.preventDefault();
  ev.stopPropagation();

  if (noConfirm || confirm('Вы действительно хотите обновить связи категорий?\nВсе текущие связи будут обновлены')) {
    _this.origText = _this.textContent;
    _this.textContent = 'Подождите';
    _this.setAttribute('disabled', true);

    const request = new XMLHttpRequest();
    request.open("GET", '/controlpanel/data/buildcattree/', true);

    request.onerror = function (e) {
      _this.removeAttribute('disabled');
      _this.textContent = _this.origText;
      alert('При выполнении операции возникла ошибка')
    };

    request.onload = function (e) {
      _this.removeAttribute('disabled');
      _this.textContent = _this.origText;
      const txt = e.target.responseText;

      if (txt === 'ok') {
       window.location.reload();
      } else {
        alert('При выполнении операции возникла ошибка')
      }
    };

    request.send();
  }
}