const waterfall = require('promise-waterfall');

module.exports = {
  select: function(multiple, mime) {
    return new Promise(resolve => {
      const input = document.createElement('input');
      input.type = 'file';
      input.style.display = 'none';
      input.multiple = !!multiple;
      input.accept = mime || '*';
      input.onchange = () => {
        const files = Array.from(input.files);
        resolve(files);
      };
      document.body.appendChild(input);
      input.click();
    });
  },
  select_file: function(mime) {
    return this.select(false, mime);
  },
  select_files: function(mime) {
    return this.select(true, mime);
  },
  select_image: function() {
    return this.select(false, 'image/*');
  },
  select_images: function() {
    return this.select(true, 'image/*');
  },
  upload: function(files, options = {}) {
    return new Promise(resolve => {
      const sequence = [];
      let result = [];
      let total = 0;
      let uploaded = 0;

      if (!Array.isArray(files)) {
        files = [files];
      }

      files.forEach((file, index) => {
        total += file.size;

        sequence.push(() => {
          return new Promise(resolve => {
            const axios_options = {
              method: 'post',
              url: `/media/upload`,
              params: _.pick(options, ['model_type', 'model_id']),
              data: new FormData(),
              headers: {
                'Content-Type': 'multipart/form-data',
              },
            };

            axios_options.data.append(file.name, file);

            if (options.onprogress) {
              axios_options.onUploadProgress = progressEvent => {
                const loaded = (progressEvent.loaded / progressEvent.total) || 0;
                const progress = (uploaded + file.size * loaded) / total;
                options.onprogress(progress, index, progressEvent);
              };
            }

            axios(axios_options).then(response => {
              if (options.onuploaded) {
                options.onuploaded(response.data, null, file);
              }

              uploaded += file.size;
              result = result.concat(response.data);
              resolve();
            }).catch(error => {
              if (options.onuploaded) {
                options.onuploaded(null, error, file);
              }

              result.push(error);
              resolve();
            });
          });
        });
      });

      waterfall(sequence).then(() => {
        resolve(result);
      });
    });
  },
};
