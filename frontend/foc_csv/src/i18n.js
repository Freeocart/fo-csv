import Vue from 'vue'
import VueI18n from 'vue-i18n'

Vue.use(VueI18n)

export default (locale) => {
  return new VueI18n({
    locale,
    messages: {
      en: {},
      ru: require('./i18n/ru.json')
    }
  })
}
