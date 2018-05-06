import { DEFAULT_PROFILE_NAME } from '@/config'

export default {
  dbFields (state) {
    return state.data.dbFields
  },
  csvFields (state) {
    return state.data.csvFields
  },
  encodings (state) {
    return state.data.encodings
  },
  profiles (state) {
    return state.data.profiles
  },
  keyFields (state) {
    return state.data.keyFields
  },
  languages (state) {
    return state.data.languages
  },
  stores (state) {
    return state.data.stores
  },
  attributeParsers (state) {
    return state.data.attributeParsers
  },
  statuses (state) {
    return state.data.statuses
  },
  stock_statuses (state) {
    return state.data.stock_statuses
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
  currentProfileName (state) {
    return state.currentProfile
  },
  currentProfile (state) {
    let profileData = state.data.profiles[state.currentProfile]

    if (!profileData) {
      profileData = state.data.profiles[DEFAULT_PROFILE_NAME]
    }

    return profileData
  },
  profile (state) {
    return state.profile
  },
  categoryDelimiter (state) {
    return state.profile.categoryDelimiter
  },
  statusRewrites (state) {
    return state.profile.statusRewrites || {}
  },
  stockStatusRewrites (state) {
    return state.profile.stockStatusRewrites || {}
  },
  submittableData (state) {
    return {
      profile: state.profile,
      data: state.data
    }
  }
}
