<template>
<div>
  <div class="form-group">
    <label for="" class="label label-default">{{ $t('Attributes field') }}</label>
    <select class="form-control" v-model="attributesCSVField">
      <option v-for="(csvField, idx) in $store.getters.csvFields" :key="idx" :value="idx">
        {{ csvField }}
      </option>
    </select>
  </div>

  <div class="form-group">
    <label for="" class="label label-default">{{ $t('Attributes parser') }}</label>
    <select class="form-control" v-model="currentAttributeParser">
      <option :value="null">NULL</option>
      <option v-for="(parser, key, idx) in $store.getters.attributeParsers" :key="idx" :value="key">
        {{ parser.title }}
      </option>
    </select>
  </div>

  <div class="form-group" v-if="showAttributeParserSettings" v-for="(option, key) in $store.getters.attributeParserOptions" :key="key">
    <label for="" class="label label-default">{{ option.title }}</label>
    <input type="text"
      :value="$store.getters.attributeParserOptionData[key]"
      @change="$store.dispatch('setAttributeParserData', [key, $event.target.value])"
      class="form-control">
  </div>

  <div class="form-group">
    <label class="label label-default">{{ $t('Default attributes group') }}</label>
    <input type="text" class="form-control" v-model="defaultAttributesGroup">
  </div>
</div>
</template>

<script>
import { mapVuexModels } from '@/helpers'

export default {
  computed: {
    ...mapVuexModels([
      'attributeParser',
      'attributeParserOptions',
      'defaultAttributesGroup',
      'attributesCSVField'
    ]),
    currentAttributeParser: {
      get () {
        return this.$store.getters.currentAttributeParser
      },
      set (newValue) {
        this.$store.dispatch('setAttributeParser', newValue)
      }
    },
    showAttributeParserSettings () {
      let settings = this.$store.getters.attributeParserOptions

      if (!settings || Object.keys(settings).length === 0) {
        return false
      }

      return true
    }
  }
}
</script>
