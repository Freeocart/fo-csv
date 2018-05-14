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
  ADD_PROFILE (state, { name, profile }) {
    Vue.set(state.data.profiles, name, profile)
  },
  SAVE_NEW_PROFILE (state, name) {
    let profileSettings = Object.assign({}, state.profile)
    Vue.set(state.data.profiles, name, profileSettings)
  },
  SET_CSV_FIELD_NAMES (state, fields) {
    state.data.csvFields = fields
  },
  SET_CATEGORY_DELIMITER (state, delimiter) {
    state.profile.categoryDelimiter = delimiter
  },
  BIND_DB_TO_CSV_FIELD (state, [ dbField, csvField ]) {
    Vue.set(state.profile.bindings, dbField, csvField)
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
  },
  DELETE_PROFILE (state, name) {
    // console.log(name, state.data.profiles[name])
    if (state.data.profiles[name]) {
      Vue.delete(state.data.profiles, name)
    }
  },
  SET_PROFILES (state, profiles) {
    Vue.set(state.data, 'profiles', profiles)
  },
  CLEAR_ALL_PROFILES (state) {
    Vue.set(state.data, 'profiles', {})
  }
}
