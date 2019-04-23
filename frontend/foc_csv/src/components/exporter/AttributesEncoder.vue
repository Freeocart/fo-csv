<template>
<div>
  <div class="form-group">
    <label class="label label-default">{{ $t('Attributes encoder') }}</label>
    <select class="form-control" v-model="currentAttributeEncoderName">
      <option :value="null">{{ $t('Not selected') }}</option>
      <option v-for="(encoder, key, idx) in attributeEncoders" :key="idx" :value="key">
        {{ encoder.title }}
      </option>
    </select>
  </div>

  <div class="form-group" v-if="showAttributeEncoderSettings" v-for="(option, key) in attributeEncoderOptions" :key="key">
    <label class="label label-default">{{ option.title }}</label>
    <component
      :is="getAttributeEncoderControl(option)"
      :value="attributeEncoderOptionData[key]"
      @change="setAttributeEncoderData([key, $event])"
    ></component>
  </div>
</div>
</template>

<script>
import { mapVuexModels } from 'vuex-models'
import { createNamespacedHelpers } from 'vuex'
import AttributeWidgets from './attributeWidgets'

const { mapActions, mapGetters } = createNamespacedHelpers('exporter')

export default {
  components: {
    ...AttributeWidgets
  },
  computed: {
    ...mapVuexModels([
      'attributeEncoder'
    ], 'exporter'),
    ...mapGetters([
      'attributeEncoders',
      'attributeEncoderOptionData',
      'currentAttributeEncoder',
      'attributeEncoderOptions'
    ]),
    currentAttributeEncoderName: {
      get () {
        return this.currentAttributeEncoder
      },
      set (newValue) {
        this.setAttributeEncoder(newValue)
      }
    },
    showAttributeEncoderSettings () {
      let settings = this.attributeEncoderOptions

      if (!settings || Object.keys(settings).length === 0) {
        return false
      }

      return true
    }
  },
  methods: {
    getAttributeEncoderControl (attribute) {
      if (attribute.type) {
        return `${attribute.type}-attribute`
      }
      return 'text-attribute'
    },
    ...mapActions([
      'setAttributeEncoderData',
      'setAttributeEncoder'
    ])
  }
}
</script>
