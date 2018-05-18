/*
  Mutations for exporter module
*/

import Vue from 'vue'
import commonMutations from '@/store/common/mutations'

export default {
  ...commonMutations,
  SET_STOCK_STATUS_REWRITE_RULE (state, { value, id }) {
    if (!state.profile.stockStatusRewrites) {
      Vue.set(state.profile, 'stockStatusRewrites', {})
    }
    Vue.set(state.profile.stockStatusRewrites, id, value)
  },
  SET_STATUS_REWRITE_RULE (state, { value, id }) {
    if (!state.profile.statusRewrites) {
      Vue.set(state.profile, 'statusRewrites', {})
    }
    Vue.set(state.profile.statusRewrites, id, value)
  },
  SET_CSV_FIELD_NAMES (state, fields) {
    Vue.set(state.data, 'csvFields', fields)
  },
  SET_CATEGORY_DELIMITER (state, delimiter) {
    Vue.set(state.profile, 'categoryDelimiter', delimiter)
  },
  SET_DB_TO_CSV_BINDINGS (state, bindings) {
    Vue.set(state.profile, 'bindings', bindings)
  },
  SET_CSV_FILE_REF (state, ref) {
    Vue.set(state.data, 'csvFileRef', ref)
  },
  SET_IMAGES_ZIP_FILE_REF (state, ref) {
    Vue.set(state.data, 'imagesZipFileRef', ref)
  },
  SET_ATTRIBUTE_PARSER (state, parser) {
    Vue.set(state.profile, 'attributeParser', parser)

    if (!parser) {
      return
    }

    if (!state.profile.attributeParserData || !state.profile.attributeParserData[parser]) {
      Vue.set(state.profile, 'attributeParserData', {
        [parser]: {}
      })
    }

    let parserObj = state.data.attributeParsers[parser]

    if (parserObj.options) {
      for (let key in parserObj.options) {
        if (!state.profile.attributeParserData[parser][key] && parserObj.options[key].default) {
          Vue.set(state.profile.attributeParserData[parser], key, parserObj.options[key].default)
        }
      }
    }
  },
  SET_ATTRIBUTE_PARSER_DATA (state, [ key, value ]) {
    let parser = state.profile.attributeParser
    Vue.set(state.profile.attributeParserData[parser], key, value)
  }
}
