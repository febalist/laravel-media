export function install (Vue) {
  if (install.installed) return;
  install.installed = true;

  const component = require('./components/model-media-edit');
  Vue.component('model-media-edit', component.default || component);
}
