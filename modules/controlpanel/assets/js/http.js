/**
 options - объект:
  - button - кнопка-инициатор запроса
*/

export let AJAXWrapper = function () {
  const _this = this;

  const __bindListeners = (request, resolve, reject, options) => {
    request.request_options = options;

    if (options.button && options.button instanceof HTMLElement) {
      options.button.setAttribute('disabled', 'disabled');
      options.button.style['pointer-events'] = 'none';
      addPreloader(options.button)
    }

    request.onreadystatechange = (ev) => {
      if (options.button && options.button instanceof HTMLElement) {
        if (ev.target.readyState === 4) {
          options.button.removeAttribute('disabled');
          options.button.style['pointer-events'] = 'unset';
          delPreloader(options.button)
        }
      }
    }

    request.onload = function (ev) {
      if (ev.target.getResponseHeader("Location") !== null) {
        window.location.replace(ev.target.getResponseHeader("Location"));
      } else {
        let data = ev.target.responseText;
        try { data = JSON.parse(data) } catch (e) {}

        for (let er of document.querySelectorAll('.error-wrapper')) {
          er.remove();
        }

        if (this.request_options && this.request_options.withError && this.request_options.form) {
          if (data.errors) {
            for (let i in data.errors) {
              let err = data.errors[i];
              if (Array.isArray(err) && err.length > 0) err = err[0];
              console.log(this.request_options.form.querySelector(`[name="${i}"]`));
              addFieldError(this.request_options.form.querySelector(`[name="${i}"]`), err);
            }
          }
        }

        if (!data.redirect) {
          resolve(data, ev.target.status)
        } else window.location.replace(data.redirect);
      }
    };

    request.onerror = (ev) => {
      if (this.request_options && this.request_options.withError && !this.request_options.noErrorMsg) {
        showError('Во время выполнения запроса возникла ошибка');
      }
      reject(ev.target.responseText, ev.target.status)
    };
  };

  const __checkURL = (url) => {
    if (typeof url != 'string') {
      throw new Error('Ошибка создания GET запроса: неверный URL адрес запроса')
    }
  };

  const __prepareData = (data, options) => {
    let fd = false;
    if (typeof data !== 'undefined') {
      if (data instanceof HTMLElement && data.tagName === 'FORM') {
        options.form = data;
        fd = new FormData(data);

        if (typeof data.files === 'object' && data.files !== null) {
          for (let i in data.files) {
            if (typeof data.files[i] === 'object') {
              for (let o in data.files[i]) {
                const file = data.files[i][o];

                if (file instanceof File || file instanceof Blob) {
                  fd.append(i, file, file.name);
                }
              }
            }
          }
        }
      } else if (data instanceof FormData) {
        r.send(data);
      } else if (typeof data === 'object' && data !== null) {
        fd = new FormData();

        for (let i in data) {
          let val = data[i];

          if (typeof val !== 'string' && Array.isArray(val)) {
            val = JSON.stringify(val);
            fd.append(`${i}[]`, val.substring(1, val.length - 1));
          } else if (typeof val === 'object') {
            fd.append(i, JSON.stringify(val));
          } else fd.append(i, val);
        }
      }
    }
    if (options.extra_data && fd) {
      for (let i in options.extra_data) {
        let val = options.extra_data[i];

        if (typeof val !== 'string' && Array.isArray(val)) {
          val = JSON.stringify(val);
          fd.append(`${i}[]`, val.substring(1, val.length - 1));
        } else if (typeof val === 'object') {
          fd.append(i, JSON.stringify(val));
        } else fd.append(i, val);
      }
    }
    return fd;
  };

  const __prepareRequest = (method, url, options) => {
    const r = new XMLHttpRequest();
    r.open(method, url);

    if (typeof options === 'object' && options !== null) {
      if (options.withCredentials) r.withCredentials = true;

      if (typeof options.headers === 'object' && options.headers !== null) {
        for (let i in options.headers) {
          let val = options.headers[i];
          if (typeof val != 'string') val = String.valueOf(val);
          r.setRequestHeader(i, val);
        }
      }
    }

    return r
  }

  this.get = (url, options = {}) => {
    __checkURL(url);
    if (typeof options !== 'object' || options === null) options = {};

    return new Promise((resolve, reject) => {
      const r = __prepareRequest('GET', url, options);
      __bindListeners(r, resolve, reject, options);
      r.send()
    })
  };

  this.post = (url, options = {}, data) => {
    __checkURL(url);
    if (typeof options !== 'object' || options === null) options = {};

    return new Promise((resolve, reject) => {
      const r = __prepareRequest('POST', url, options);
      const d = __prepareData(data, options);
      __bindListeners(r, resolve, reject, options);

      if (d) {
        r.send(d);
      } else r.send();
    })
  }
}
