function openImportAttributesForm(ev, _this, form, heading) {
  ev.preventDefault();
  ev.stopPropagation();
  createEditModal(form, '/controlpanel/data/uploadattributes/', heading);
  const btn = document.querySelector('[name=editForm] .buttons .btn');
  btn.action = '/controlpanel/data/uploadattributes/';
  const file_inp = document.querySelector('[name=editForm] [type=file]');

  file_inp.onchange = function() {
    const file = this.files[0];
    const select = document.querySelector('[name=editForm] [name="sheet_name"]');
    select.innerHTML = "";
    if (!file) {
      return;
    }
    if (file.name.indexOf('.xlsx') > 0) {
      processXLSX(file, 0, true).then(sheets => {
        createSheetsSelect(select, sheets)
      }).catch((ex) => {
        console.log(ex);
        onerror('Что-то пошло не так...');
      });
    } else if (file.name.indexOf('.xls') > 0) {
      processXLS(file, 0).then(sheets => {
        createSheetsSelect(select, sheets)
      }).catch((ex) => {
        console.log(ex);
        onerror('Что-то пошло не так...');
      });
    } else {
      onerror('Неподдерживаемый тип файла');
    }
  };

  btn.removeAttribute('onclick');

  btn.onclick = function (evt) {
    evt.preventDefault();
    evt.stopPropagation();
    const sheet_num = parseInt(document.querySelector('[name=editForm] [name="sheet_name"]').value);
    const file_inp = document.querySelector('[name=editForm] [type=file]');

    if (file_inp.files.length) {
      processFile(file_inp.files[0], file_inp, this, sheet_num, form);
    } else {
      this.origTxt = this.textContent;
      this.textContent = 'Необходимо загрузить файл импорта';
      setTimeout(function () {
        this.textContent = this.origTxt;
      }, 3000)
    }
  };
}

function createSheetsSelect(select, sheets) {
  sheets.map((sheet, index) => {
    select.insertAdjacentHTML("beforeend", `<option value="${index}">${sheet}</option>`)
  })
}

const XLS_OPTS = {
  type: 'binary',
  raw: true,
  blankrows: true,
  skipHeader: true
};

const XLS_PARSE_OPTS = {
  header: 1
};

function processFile(file, _this, btn, sheet_num, form) {
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
    processXLSX(file, sheet_num).then(json => {
      //_this.value = '';
      processJSON(json, _this, btn, form);
    }).catch((ex) => {
      console.log(ex);
      onerror('Что-то пошло не так...');
    });
  } else if (file.name.indexOf('.xls') > 0) {
    processXLS(file, sheet_num).then(json => {
      processJSON(json, _this, btn, form);
    }).catch((ex) => {
      console.log(ex);
      onerror('Что-то пошло не так...');
    });
  } else {
    onerror('Неподдерживаемый тип файла');
  }
}

function processXLS(file, sheet_num, onlySheets = false) {
  return new Promise((resolve, reject) => {
    var reader = new FileReader();

    reader.onload = function (e) {
      var data = e.target.result;
      var cfb = XLS.CFB.read(data, XLS_OPTS);
      var wb = XLS.parse_xlscfb(cfb);
      if (onlySheets) {
        resolve(wb.SheetNames)
      }
      if (wb.SheetNames.length) {
        const json = XLS.utils.sheet_to_row_object_array(wb.Sheets[wb.SheetNames[sheet_num]], XLS_PARSE_OPTS);
        resolve(json, wb.SheetNames[sheet_num]);
      }
    };

    reader.onerror = function (ex) { reject(ex) };
    reader.readAsBinaryString(file);
  });
}

function processXLSX(file, sheet_num, onlySheets = false) {
  return new Promise((resolve, reject) => {
    var reader = new FileReader();
    reader.onload = function (e) {
      var data = e.target.result;
      var workbook = XLSX.read(data, XLS_OPTS);
      if (onlySheets) {
        resolve(workbook.SheetNames)
      }
      if (workbook.SheetNames.length) {
        const json = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[sheet_num]], XLS_PARSE_OPTS);
        resolve(json, workbook.SheetNames[sheet_num]);
      }
    };
    reader.onerror = function (ex) { reject(ex) };
    reader.readAsBinaryString(file);
  });
}

function processJSON(json, _this, btn, form) {
  console.log('Processing json...');
  console.log('processed json', json);
  let val = btn.origVal;
  btn.textContent = 'Обработка данных...';
  const l = json.length;
  let current = 0;
  let to_insert = 0;
  let inserted = 0;

  return new Promise((resolve, reject) => {
    const result = {
      attribute_group_id: form.querySelector('[name="attribute_group_id"]').value,
      values: []
    };

    json.map(item => {
      if (item[0]) {
        result.values.push(item[0]);
      }
    });

    console.log('data before submit', result);

    if (result.values.length > 0) {
      submitData(btn.action, result, current).then(response => {
        resolve(response);
      }).catch(err => {
        console.log(err);
        reject(err)
      });
    } else {
      resolve(null);
    }
  }).then((data) => {
    if (data !== null && data.success) {
      if (!isNaN(data.to_insert)) to_insert = to_insert + data.to_insert;
      if (!isNaN(data.inserted)) inserted = inserted + data.inserted;
      createModal(`<div><p>Найдено записей: ${to_insert}</p><p>Сохранено записей: ${inserted}</p></div>`, 'Результат');
      btn.textContent = val;
      btn.removeAttribute('disabled');
      setTimeout(() => window.location.reload(), 2000);
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
}

function submitData(action, data, current) {
  return new Promise((resolve, reject) => {
    console.log(data);
    let fData = new FormData();
    fData.append('current', current);
    fData.append('attribute_group_id', data.attribute_group_id);
    fData.append('values', JSON.stringify(data.values));
    fData.append(__csrf[0], __csrf[1]);
    const request = new XMLHttpRequest();
    request.open("POST", action, true);
    request.onerror = (e) => { reject(e) };

    request.onload = (e) => {
      const data = parseToObject(e.target.responseText);
      if (data != null && data.success) {
        resolve(data);
      } else {
        reject(data);
      }
    };

    request.send(fData);
  });
}