<template>
<div>
  <div class="form-group">
    <label for="" class="label label-default">{{ $t('Attributes field') }}</label>
    <select class="form-control" v-model="attributesCSVField">
      <option v-for="(csvField, idx) in csvFields" :key="idx" :value="idx">
        {{ csvField }}
      </option>
    </select>
  </div>

  <div class="form-group">
    <label for="" class="label label-default">{{ $t('Attributes parser') }}</label>
    <select class="form-control" v-model="currentAttributeParserName">
      <option :value="null">{{ $t('Not selected') }}</option>
      <option v-for="(parser, key, idx) in attributeParsers" :key="idx" :value="key">
        {{ parser.title }}
      </option>
    </select>
  </div>

  <div class="form-group" v-if="showAttributeParserSettings" v-for="(option, key) in attributeParserOptions" :key="key">
    <label for="" class="label label-default">{{ option.title }}</label>
    <input type="text"
      :value="attributeParserOptionData[key]"
      @change="setAttributeParserData([key, $event.target.value])"
      class="form-control">
  </div>

  <div class="form-group">
    <label class="label label-default">{{ $t('Default attributes group') }}</label>
    <autocomplete
      :url="attributesGroupUrl"
      requestType="get"
      input-class="form-control"
      v-model="defaultAttributesGroup"
    >
    </autocomplete>
  </div>
</div>
</template>

<script>
import { mapVuexModels } from '@/helpers'
import { createNamespacedHelpers } from 'vuex'
import Autocomplete from 'autocomplete-vue'
import { ATTRIBUTES_GROUP_AUTOCOMPLETE_URL } from '@/config'

const { mapActions, mapGetters } = createNamespacedHelpers('importer')

export default {
  components: {
    Autocomplete
  },
  computed: {
    ...mapVuexModels([
      'attributeParser',
      'defaultAttributesGroup',
      'attributesCSVField'
    ], 'importer'),
    ...mapGetters([
      'csvFields',
      'attributeParsers',
      'attributeParserOptionData',
      'currentAttributeParser',
      'attributeParserOptions'
    ]),
    attributesGroupUrl () {
      return this.$store.actionUrl(ATTRIBUTES_GROUP_AUTOCOMPLETE_URL)
    },
    currentAttributeParserName: {
      get () {
        return this.currentAttributeParser
      },
      set (newValue) {
        this.setAttributeParser(newValue)
      }
    },
    showAttributeParserSettings () {
      let settings = this.attributeParserOptions

      if (!settings || Object.keys(settings).length === 0) {
        return false
      }

      return true
    }
  },
  methods: {
    ...mapActions([
      'setAttributeParserData',
      'setAttributeParser'
    ])
  }
}
</script>
