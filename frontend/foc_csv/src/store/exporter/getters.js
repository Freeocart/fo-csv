import commonGetters from '@/store/common/getters'

export default {
  attributeEncoders (state) {
    return state.data.attributeEncoders
  },
  currentAttributeEncoder (state) {
    return state.profile.attributeEncoder
  },
  attributeEncoderOptions (state) {
    let encoder = state.profile.attributeEncoder

    if (!encoder || !state.data.attributeEncoders[encoder]) {
      return []
    }

    if (state.data.attributeEncoders[encoder].options) {
      return state.data.attributeEncoders[encoder].options
    }

    return []
  },
  attributeEncoderOptionData (state) {
    let encoder = state.profile.attributeEncoder
    return state.profile.attributeEncoderData[encoder] || []
  },
  ...commonGetters
}
