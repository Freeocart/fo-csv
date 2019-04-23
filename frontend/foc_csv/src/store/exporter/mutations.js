import commonMutations from '@/store/common/mutations'

export default {
  SET_ATTRIBUTE_ENCODER (state, encoder) {
    Vue.set(state.profile, 'attributeEncoder', encoder)

    if (!encoder) {
      return
    }

    if (!state.profile.attributeEncoderData || !state.profile.attributeEncoderData[encoder]) {
      Vue.set(state.profile, 'attributeEncoderData', {
        [encoder]: {}
      })
    }

    let encoderObj = state.data.attributeEncoders[encoder]

    if (encoderObj.options) {
      for (let key in encoderObj.options) {
        if (!state.profile.attributeEncoderData[encoder][key] && encoderObj.options[key].default) {
          Vue.set(state.profile.attributeEncoderData[encoder], key, encoderObj.options[key].default)
        }
      }
    }
  },
  SET_ATTRIBUTE_ENCODER_DATA (state, [ key, value ]) {
    let encoder = state.profile.attributeEncoder
    Vue.set(state.profile.attributeEncoderData[encoder], key, value)
  },
  ...commonMutations
}
