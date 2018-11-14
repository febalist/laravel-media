<template>
  <div v-cloak>
    <input type="hidden" :name="name" :value="value_json">

    <div class="">
      <button type="button" class="btn btn-secondary"
              :disabled="progress !== null" @click="select_files">
        Загрузить файлы
      </button>
      <span class="ml-2" v-if="progress !== null">
        {{ progress * 100 }}%
      </span>
    </div>

    <div class="input-group mt-1" v-for="(media, index) in media_array">
      <input type="text" class="form-control" v-model="media.filename" pattern="^[^\\/%?*:|<>&quot;]*$">
      <div class="input-group-append">
        <span class="input-group-text" v-if="media.extension">
          .{{ media.extension }}
        </span>
        <a class="btn btn-outline-secondary" :href="media.view" target="_blank">
          Просмотр
        </a>
        <button class="btn btn-outline-danger" type="button" @click="remove(index)">
          Удалить
        </button>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'model-media-edit',
    props: ['name', 'value'],
    data() {
      let media_array = this.value;
      try {
        media_array = JSON.parse(media_array);
      } catch (e) {
        media_array = [];
      }

      return {
        media_array,
        progress: null,
        timeout: null,
      };
    },
    computed: {
      value_json: function() {
        return JSON.stringify(this.media_array);
      },
    },
    watch: {
      progress: function(value) {
        if (value === 1) {
          this.timeout = setTimeout(() => {
            if (this.progress === 1) {
              this.progress = null;
              this.$forceUpdate();
            }
          }, 1000 * 0.5);
        } else if (this.timeout) {
          clearTimeout(this.timeout);
          this.timeout = null;
        }
      },
    },
    methods: {
      select_files: function() {
        media.select_files().then(files => {
          this.upload_files(files);
        });
      },
      upload_files: function(files) {
        media.upload(files, {
          onprogress: (progress, index, event) => {
            this.progress = parseFloat(progress.toFixed(2));
          },
          onuploaded: (result, error, file) => {
            if (result) {
              this.media_array = this.media_array.concat(result);
            } else {
              console.log(error);
            }
          },
        });
      },
      remove: function(index) {
        this.media_array.splice(index, 1);
      },
    },
    mounted() {
    },
  };
</script>

<style scoped>
  [v-cloak] {
    display: none;
  }

  .truncate {
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
</style>
