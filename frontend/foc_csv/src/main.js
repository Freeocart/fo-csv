// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import resource from 'vue-resource'
import App from './App'
import router from './router'
import i18n from './i18n'

import store from './store'
import ApiProvider from './api'
import { validateAppConfig } from './helpers'

Vue.use(resource)
Vue.config.productionTip = true

let AppConfig = {}

if (window.FOC_CSV_PARAMS) {
  AppConfig = Object.assign(AppConfig, window.FOC_CSV_PARAMS)
}

if (validateAppConfig(AppConfig.requestConfig)) {
  // configure api provider
  let api = ApiProvider.prepare(AppConfig.requestConfig)
  Vue.use(api)

  // set initial data in vuex modules
  store.dispatch('importer/setInitialData', Object.assign({}, AppConfig.initial.importer, AppConfig.initial.common))
  store.dispatch('exporter/setInitialData', Object.assign({}, AppConfig.initial.exporter, AppConfig.initial.common))

  const language = AppConfig.language || 'en'

  /* eslint-disable no-new */
  new Vue({
    el: '#foc_csv',
    router,
    AppConfig,
    store,
    i18n: i18n(language),
    components: { App },
    template: '<App />'
  })
}
else {
  console.error('Failed to initialize! Wrong AppConfig!')
}
