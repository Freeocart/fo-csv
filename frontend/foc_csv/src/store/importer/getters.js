import commonGetters from '@/store/common/getters'

export default {
  ...commonGetters,
  csvFields (state) {
    return state.data.csvFields
  },
  keyFields (state) {
    return state.data.keyFields
  },
  attributeParsers (state) {
    return state.data.attributeParsers
  },
  currentAttributeParser (state) {
    return state.profile.attributeParser
  },
  attributeParserOptions (state) {
    let parser = state.profile.attributeParser

    if (!parser || !state.data.attributeParsers[parser]) {
      return []
    }

    if (state.data.attributeParsers[parser].options) {
      return state.data.attributeParsers[parser].options
    }

    return []
  },
  attributeParserOptionData (state) {
    let parser = state.profile.attributeParser
    return state.profile.attributeParserData[parser] || []
  },
  categoryDelimiter (state) {
    return state.profile.categoryDelimiter
  },
  submittableData (state) {
    return {
      profile: state.profile,
      data: state.data
    }
  }
}
